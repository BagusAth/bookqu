<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Payment\CreateSubscriptionPayment;
use App\Actions\Payment\OutcomeIntakeResult;
use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Payment\PaymentOutcomeIntake;
use App\Domain\Payment\PaymentTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\ProcessSubscriptionCheckoutRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OwnerCheckoutController extends Controller
{
    /**
     * Resolve tenant dari session / auth user.
     */
    use ResolvesOwnerTenant;

    /**
     * Halaman 1: Review Pesanan & Data Kontak.
     */
    public function showCheckout(Plan $plan)
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();

        if (! $tenant) {
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
    public function processCheckout(ProcessSubscriptionCheckoutRequest $request, CreateSubscriptionPayment $createSubscriptionPayment)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            return redirect()->route('owner.subscription')
                ->with('pesan', 'Tenant tidak ditemukan.');
        }

        $plan = Plan::findOrFail($request->validated()['plan_id']);

        try {
            $payment = $createSubscriptionPayment->execute(
                $tenant,
                $plan,
                [
                    'nama_pembayar' => $request->nama_pembayar,
                    'email_pembayar' => $request->email_pembayar,
                    'hp_pembayar' => $request->hp_pembayar,
                    'catatan' => $request->catatan,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Owner checkout error: '.$e->getMessage());

            return redirect()->route('owner.subscription')
                ->with('pesan', 'Gagal memproses pembayaran. Silakan coba lagi. Error: '.$e->getMessage());
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
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(403, 'Unauthorized');
        }

        // Jika sudah sukses, redirect ke invoice
        if ($payment->status === 'sukses') {
            return redirect()->route('owner.checkout.invoice', $payment);
        }

        // Jika expired, kembali ke subscription
        if ($payment->isExpired() && $payment->status === 'pending') {
            app(PaymentLifecycle::class)->transition($payment, PaymentTransition::Expired);

            return redirect()->route('owner.subscription')
                ->with('pesan', 'Waktu pembayaran telah habis. Silakan buat pesanan baru.');
        }

        $plan = $payment->plan;

        $provider = $payment->provider ?: $payment->metode ?: 'midtrans';

        return view('owner.checkout-payment', [
            'tenant' => $tenant,
            'payment' => $payment,
            'plan' => $plan,
            'provider' => $provider,
            'paymentUrl' => $payment->payment_url,
            'snapToken' => $payment->snap_token,
            'clientKey' => ($provider === 'midtrans') ? config('payment.drivers.midtrans.client_key') : null,
            'snapUrl' => ($provider === 'midtrans') ? config('payment.drivers.midtrans.snap_url') : null,
        ]);
    }

    /**
     * Cek status pembayaran ke gateway provider aktif.
     */
    public function checkPaymentStatus(Payment $payment, PaymentOutcomeIntake $intake)
    {
        $tenant = $this->resolveTenant();

        if (! $tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $this->statusResponse($intake->check($payment), $payment);
    }

    /**
     * Halaman 3: Invoice & Sukses.
     */
    public function showInvoice(Payment $payment)
    {
        $tenant = $this->resolveTenant();

        if (! $tenant || $payment->idtenant !== $tenant->id) {
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
     * Callback dari gateway (client-side). The browser's payload is never trusted:
     * the callback only triggers a server-side status query (BQ-070).
     */
    public function handleCallback(Payment $payment, PaymentOutcomeIntake $intake)
    {
        $tenant = $this->resolveTenant();

        if (! $tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $this->statusResponse($intake->check($payment), $payment);
    }

    private function statusResponse(OutcomeIntakeResult $result, Payment $payment): JsonResponse
    {
        return match ($result->status()) {
            'sukses' => response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => route('owner.checkout.invoice', $payment),
            ]),
            'gagal' => response()->json([
                'status' => 'gagal',
                'message' => 'Pembayaran gagal, dibatalkan, atau waktu pembayaran telah habis.',
            ]),
            default => response()->json([
                'status' => 'pending',
                'message' => 'Menunggu pembayaran diselesaikan...',
            ]),
        };
    }
}
