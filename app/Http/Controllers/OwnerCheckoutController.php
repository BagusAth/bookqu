<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\MidtransPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

class OwnerCheckoutController extends Controller
{
    public function __construct()
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized = config('midtrans.is_sanitized');
        MidtransConfig::$is3ds = config('midtrans.is_3ds');
        
        // P0-17: SSL Hardening
        $curlOptions = [CURLOPT_HTTPHEADER => []];
        if (app()->environment('local')) {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = 0;
        } else {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
        }
        MidtransConfig::$curlOptions = $curlOptions;
    }

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

        return view('owner.owner-checkout', [
            'tenant' => $tenant,
            'plan' => $plan,
            'user' => $user,
            'existingPayment' => $existingPayment,
            'biayaPlatform' => 0, // Biaya layanan platform, bisa diubah nanti
        ]);
    }

    /**
     * Proses checkout: buat Payment record & generate Snap token.
     */
    public function processCheckout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'nama_pembayar' => 'required|string|max:100',
            'email_pembayar' => 'required|email|max:100',
            'hp_pembayar' => 'required|string|max:20',
            'catatan' => 'nullable|string|max:500',
        ]);

        $tenant = $this->resolveTenant();
        if (!$tenant) {
            return redirect()->route('owner.subscription')
                ->with('pesan', 'Tenant tidak ditemukan.');
        }

        $plan = Plan::findOrFail($request->plan_id);
        $biayaPlatform = 0;
        $totalBayar = $plan->hargabulanan + $biayaPlatform;

        // Generate order ID unik
        $orderId = $this->generateOrderId();

        // Buat record payment
        $payment = Payment::create([
            'idtenant' => $tenant->id,
            'idplan' => $plan->id,
            'tipe' => 'subscription',
            'jumlah' => $totalBayar,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => $orderId,
            'expired_at' => now()->addHour(),
            'nama_pembayar' => $request->nama_pembayar,
            'email_pembayar' => $request->email_pembayar,
            'hp_pembayar' => $request->hp_pembayar,
            'catatan' => $request->catatan,
        ]);

        // Params untuk Midtrans Snap
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $totalBayar,
            ],
            'customer_details' => [
                'first_name' => $request->nama_pembayar,
                'email' => $request->email_pembayar,
                'phone' => $request->hp_pembayar,
            ],
            'item_details' => [
                [
                    'id' => 'PLAN-' . $plan->id,
                    'price' => (int) $plan->hargabulanan,
                    'quantity' => 1,
                    'name' => 'Subscription Plan ' . ucfirst($plan->namapaket) . ' (1 Bulan)',
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hour',
                'duration' => 1,
            ],
        ];

        // Tambahkan biaya platform ke item_details jika ada
        if ($biayaPlatform > 0) {
            $params['item_details'][] = [
                'id' => 'PLATFORM-FEE',
                'price' => (int) $biayaPlatform,
                'quantity' => 1,
                'name' => 'Biaya Layanan Platform',
            ];
        }

        try {
            $snapToken = Snap::getSnapToken($params);
            $payment->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error: ' . $e->getMessage());

            $payment->update(['status' => 'gagal']);

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

        return view('owner.owner-checkout-payment', [
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

        $syncResult = $paymentService->verifyAndSync($payment);

        if ($syncResult['status'] === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil!',
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
            return response()->json([
                'status' => 'error',
                'message' => $syncResult['message'] ?? 'Gagal memeriksa status. Silakan coba beberapa saat lagi.',
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => $syncResult['message'] ?? 'Pembayaran belum diterima. Silakan selesaikan pembayaran.',
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

        return view('owner.owner-checkout-invoice', [
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
