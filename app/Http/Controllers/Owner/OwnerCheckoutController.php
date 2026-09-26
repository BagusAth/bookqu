<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Payment\CreateSubscriptionPayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\MidtransPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OwnerCheckoutController extends Controller
{
    /**
     * Resolve tenant dari session / auth user.
     */
    use \App\Traits\ResolvesOwnerTenant;

    /**
     * Generate unique order ID format: BQ-YYYYMMDD-XXXX
     */
    private function generateOrderId(): string
    {
        $prefix = 'BQ-' . now()->format('Ymd') . '-';
        $lastPayment = Payment::where('order_id', 'like', $prefix . '%')
            ->orderByDesc('order_id')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->order_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Halaman 1: Review Pesanan & Data Kontak.
     */
    public function showCheckout(Plan $plan)
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();

        if (!$tenant) {
            return redirect()->route('owner.subscription')
                ->with('pesan', 'Silakan lengkapi profil bisnis terlebih dahulu.');
        }

        // Cek apakah sudah ada pending payment untuk plan ini
        $existingPayment = Payment::where('idtenant', $tenant->id)
            ->where('idplan', $plan->id)
            ->where('status', 'pending')
            ->where('expired_at', '>', now())
            ->first();

        return view('owner.checkout', [
            'tenant' => $tenant,
            'plan' => $plan,
            'user' => $user,
            'existingPayment' => $existingPayment,
            'biayaPlatform' => 0, // Biaya layanan platform, bisa diubah nanti
        ]);
    }

    /**
     * Proses checkout: buat Payment record & generate Snap token via CreateSubscriptionPayment action.
     */
    public function processCheckout(\App\Http\Requests\Subscription\ProcessSubscriptionCheckoutRequest $request, CreateSubscriptionPayment $createSubscriptionPayment)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            return redirect()->route('owner.subscription')
                ->with('pesan', 'Tenant tidak ditemukan.');
        }

        $plan = Plan::findOrFail($request->validated()['plan_id']);

        try {
            $payment = $createSubscriptionPayment->execute(
                $tenant,
                $plan,
                [
                    'nama_pembayar'  => $request->nama_pembayar,
                    'email_pembayar' => $request->email_pembayar,
                    'hp_pembayar'    => $request->hp_pembayar,
                    'catatan'        => $request->catatan,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Owner checkout error: ' . $e->getMessage());

            return redirect()->route('owner.subscription')
                ->with('pesan', 'Gagal memproses pembayaran. Silakan coba lagi. Error: ' . $e->getMessage());
        }

        return redirect()->route('owner.checkout.payment', $payment);
    }

    /**
     * Halaman 2: Pembayaran (Midtrans Snap Wrapper).
     */
    public function showPayment(Payment $payment)
    {
        $tenant = $this->resolveTenant();

        // Security: pastikan payment milik tenant yang sedang login
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            abort(403, 'Unauthorized');
        }

        // Jika sudah sukses, redirect ke invoice
        if ($payment->status === 'sukses') {
            return redirect()->route('owner.checkout.invoice', $payment);
        }

        // Jika expired, kembali ke subscription
        if ($payment->isExpired() && $payment->status === 'pending') {
            $payment->update(['status' => 'gagal']);
            return redirect()->route('owner.subscription')
                ->with('pesan', 'Waktu pembayaran telah habis. Silakan buat pesanan baru.');
        }

        $plan = $payment->plan;

        return view('owner.checkout-payment', [
            'tenant' => $tenant,
            'payment' => $payment,
            'plan' => $plan,
            'snapToken' => $payment->snap_token,
            'clientKey' => config('midtrans.client_key'),
            'snapUrl' => config('midtrans.snap_url'),
        ]);
    }

    /**
     * Cek status pembayaran secara manual ke Midtrans.
     */
    public function checkPaymentStatus(Payment $payment, MidtransPaymentService $paymentService)
    {
        $tenant = $this->resolveTenant();

        if (!$tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fast-path: jika database sudah sukses
        if ($payment->status === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => route('owner.checkout.invoice', $payment),
            ]);
        }

        $syncResult = $paymentService->verifyAndSync($payment);

        if ($syncResult['status'] === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => route('owner.checkout.invoice', $payment),
            ]);
        }

        if ($syncResult['status'] === 'gagal') {
            return response()->json([
                'status' => 'gagal',
                'message' => $syncResult['message'] ?? 'Pembayaran gagal atau dibatalkan.',
            ]);
        }

        if ($syncResult['status'] === 'error') {
            $payment->refresh();
            if ($payment->status === 'sukses') {
                return response()->json([
                    'status' => 'sukses',
                    'message' => 'Pembayaran berhasil dikonfirmasi!',
                    'redirect' => route('owner.checkout.invoice', $payment),
                ]);
            }

            return response()->json([
                'status' => 'pending',
                'message' => 'Sedang memverifikasi dengan payment gateway...',
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => $syncResult['message'] ?? 'Menunggu pembayaran diselesaikan...',
        ]);
    }

    /**
     * Halaman 3: Invoice & Sukses.
     */
    public function showInvoice(Payment $payment)
    {
        $tenant = $this->resolveTenant();

        if (!$tenant || $payment->idtenant !== $tenant->id) {
            abort(403, 'Unauthorized');
        }

        // Load relations
        $payment->load('plan');

        // Cari subscription yang diaktifkan
        $subscription = Subscription::where('idtenant', $tenant->id)
            ->where('idplan', $payment->idplan)
            ->where('status', 'active')
            ->latest()
            ->first();

        return view('owner.checkout-invoice', [
            'tenant' => $tenant,
            'payment' => $payment,
            'plan' => $payment->plan,
            'subscription' => $subscription,
        ]);
    }



    /**
     * Handle callback dari Midtrans Snap (client-side).
     * Dipanggil setelah user selesai di pop-up Midtrans.
     */
    public function handleCallback(Payment $payment, Request $request, MidtransPaymentService $paymentService)
    {
        $tenant = $this->resolveTenant();

        if (!$tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fast-path: jika database sudah sukses
        if ($payment->status === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => route('owner.checkout.invoice', $payment),
            ]);
        }

        // Lakukan server-side verification ke Midtrans
        $syncResult = $paymentService->verifyAndSync($payment);

        if ($syncResult['status'] === 'error') {
            $result = $request->input('result');
            if ($result) {
                $syncResult = $paymentService->syncStatus($payment, $result);
            }
        }

        if ($syncResult['status'] === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => route('owner.checkout.invoice', $payment),
            ]);
        }

        if ($syncResult['status'] === 'pending') {
            return response()->json([
                'status' => 'pending',
                'message' => $syncResult['message'] ?? 'Pembayaran pending. Silakan selesaikan pembayaran.',
            ]);
        }

        return response()->json([
            'status' => 'gagal',
            'message' => $syncResult['message'] ?? 'Pembayaran gagal.',
        ]);
    }
}

