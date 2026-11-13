<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Actions\Booking\GetInvoiceAmounts;
use App\Actions\CustomerAccess\CancelCheckoutPayment;
use App\Actions\Payment\OutcomeIntakeResult;
use App\Actions\Payment\PaymentOutcomeIntake;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\CustomerBookingRoutes;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class BookingPaymentController extends StorefrontBookingController
{
    public function showPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        if ($payment->status === 'sukses') {
            return CustomerBookingRoutes::route('customer.booking.invoice', [$slug_usaha, $payment]);
        }

        $payment->load(['bookings.layanan']);
        $timeRanges = app(GetBookingTimeRanges::class)->execute($payment->bookings);

        $provider = $payment->provider ?: ($payment->metode === 'singapay' ? 'singapay' : config('payment.default', 'singapay'));
        $isMidtrans = $provider === 'midtrans';
        $clientKey = $isMidtrans ? config('payment.drivers.midtrans.client_key') : null;
        $snapUrl = $isMidtrans ? config('payment.drivers.midtrans.snap_url') : null;

        if ($payment->status === 'gagal') {
            return view('customer.booking.payment', $timeRanges + [
                'tenant' => $tenant,
                'payment' => $payment,
                'provider' => $provider,
                'paymentState' => 'failed',
                'paymentUrl' => null,
                'snapToken' => null,
                'clientKey' => $clientKey,
                'snapUrl' => $snapUrl,
            ]);
        }

        if ($payment->isExpired()) {
            return view('customer.booking.payment', $timeRanges + [
                'tenant' => $tenant,
                'payment' => $payment,
                'provider' => $provider,
                'paymentState' => 'expired',
                'paymentUrl' => null,
                'snapToken' => null,
                'clientKey' => $clientKey,
                'snapUrl' => $snapUrl,
            ]);
        }

        return view('customer.booking.payment', $timeRanges + [
            'tenant' => $tenant,
            'payment' => $payment,
            'provider' => $provider,
            'paymentState' => 'pending',
            'paymentUrl' => $payment->payment_url,
            'snapToken' => $payment->snap_token,
            'clientKey' => $clientKey,
            'snapUrl' => $snapUrl,
        ]);
    }

    public function cancelPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        try {
            app(CancelCheckoutPayment::class)->execute(request()->attributes->get('customer_access'), $payment);
        } catch (HttpException $refused) {
            // Paid or expired meanwhile: a form submit returns to the payment page, which shows the real state.
            if ($refused->getStatusCode() !== 422 || request()->expectsJson()) {
                throw $refused;
            }

            return CustomerBookingRoutes::route('customer.booking.payment', [$slug_usaha, $payment->order_id])
                ->with('error', 'Pesanan tidak dapat dibatalkan karena pembayarannya sudah diterima atau sudah berakhir.');
        }

        $this->drafts->clear();

        return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
            ->with('info', 'Transaksi berhasil dibatalkan. Anda dapat memilih layanan atau jadwal baru.');
    }

    public function checkPaymentStatus(string $slug_usaha, Payment $payment, PaymentOutcomeIntake $intake)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        return $this->paymentStatusResponse($intake->check($payment, request()->attributes->get('customer_access')), $slug_usaha, $payment);
    }

    public function handleCallback(string $slug_usaha, Payment $payment, PaymentOutcomeIntake $intake)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        return $this->paymentStatusResponse($intake->check($payment, request()->attributes->get('customer_access')), $slug_usaha, $payment);
    }

    private function paymentStatusResponse(OutcomeIntakeResult $result, string $slug_usaha, Payment $payment)
    {
        $current = $result->payment ?? $payment;

        $status = $result->status();

        return match ($status) {
            'sukses' => response()->json([
                'status' => $status,
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => CustomerBookingRoutes::url('customer.booking.invoice', [$slug_usaha, $payment]),
            ]),
            'gagal' => response()->json(array_filter([
                'status' => $status,
                'is_expired' => $current->isExpired() ?: null,
                'message' => $current->isExpired()
                    ? 'Waktu pembayaran telah habis. Silakan buat reservasi baru.'
                    : 'Pembayaran gagal atau dibatalkan.',
            ], fn ($value) => $value !== null)),
            default => response()->json([
                'status' => 'pending',
                'message' => 'Menunggu pembayaran diselesaikan...',
            ]),
        };
    }

    public function showInvoice(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        // Section 10: Invoice hanya boleh diakses jika payment sukses
        if ($payment->status === 'pending') {
            return CustomerBookingRoutes::route('customer.booking.payment', [$slug_usaha, $payment->order_id])
                ->with('error', 'Silakan selesaikan pembayaran terlebih dahulu untuk melihat invoice.');
        }

        if ($payment->status !== 'sukses') {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
                ->with('error', 'Invoice hanya dapat diakses untuk pembayaran yang telah berhasil.');
        }

        $bookings = Booking::with('layanan')
            ->where('idpayment', $payment->id)
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->get();

        if ($bookings->isEmpty()) {
            abort(404, 'Data booking tidak ditemukan.');
        }

        $booking = $bookings->first();

        return view('customer.booking.invoice', app(GetInvoiceAmounts::class)->execute($payment) + app(GetBookingTimeRanges::class)->execute($bookings) + compact('tenant', 'payment', 'bookings', 'booking'));
    }
}
