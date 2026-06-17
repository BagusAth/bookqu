<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    /**
     * Handle Midtrans notification webhook for customer booking payments.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans Booking Webhook Received:', $payload);

        // ── Validate Signature ──
        $serverKey   = config('midtrans.server_key');
        $orderId     = $payload['order_id'] ?? null;
        $statusCode  = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('Midtrans Booking Webhook: Missing required fields', $payload);
            return response()->json(['message' => 'Missing required fields'], 400);
        }

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $expectedSignature) {
            Log::warning('Midtrans Booking Webhook: Signature mismatch', [
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // ── Find Payment ──
        $payment = Payment::where('order_id', $orderId)
            ->where('tipe', 'booking')
            ->first();

        if (!$payment) {
            Log::warning('Midtrans Booking Webhook: Payment not found', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // ── Process Transaction Status ──
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus       = $payload['fraud_status'] ?? null;
        $paymentType       = $payload['payment_type'] ?? null;

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                $this->handleSuccess($payment, $paymentType);
            }
            // challenge → do nothing, wait for settlement
        } elseif ($transactionStatus === 'settlement') {
            $this->handleSuccess($payment, $paymentType);
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $this->handleFailure($payment, $paymentType);
        } elseif ($transactionStatus === 'pending') {
            $payment->update([
                'metode' => $paymentType ?? 'midtrans',
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle successful payment: update payment + booking, clear cache.
     */
    private function handleSuccess(Payment $payment, ?string $paymentType): void
    {
        if ($payment->status === 'sukses') {
            return;
        }

        DB::transaction(function () use ($payment, $paymentType) {
            $payment->update([
                'status' => 'sukses',
                'metode' => $paymentType ?? 'midtrans',
            ]);

            if ($payment->idbooking) {
                $booking = Booking::find($payment->idbooking);

                if ($booking) {
                    $booking->update(['status' => 'paid']);

                    // ── Clear relevant caches ──
                    $this->clearBookingCaches(
                        $booking->idtenant,
                        $booking->idlayanan,
                        $booking->tanggalbooking->toDateString()
                    );
                }
            }
        });
    }

    /**
     * Handle failed payment: update payment + cancel booking.
     */
    private function handleFailure(Payment $payment, ?string $paymentType): void
    {
        if ($payment->status === 'gagal') {
            return;
        }

        DB::transaction(function () use ($payment, $paymentType) {
            $payment->update([
                'status' => 'gagal',
                'metode' => $paymentType ?? 'midtrans',
            ]);

            if ($payment->idbooking) {
                $booking = Booking::find($payment->idbooking);

                if ($booking && $booking->status === 'pending') {
                    $booking->update(['status' => 'cancelled']);

                    $this->clearBookingCaches(
                        $booking->idtenant,
                        $booking->idlayanan,
                        $booking->tanggalbooking->toDateString()
                    );
                }
            }
        });
    }

    /**
     * Clear schedule & availability caches after booking status change.
     */
    private function clearBookingCaches(int $tenantId, int $serviceId, string $date): void
    {
        // Clear specific schedule cache for this date
        Cache::forget("tenant:{$tenantId}:service:{$serviceId}:schedules:{$date}");

        // Clear availability caches — use pattern matching via known date ranges
        $minDate = now()->toDateString();
        $maxDate = now()->addDays(30)->toDateString();
        Cache::forget("tenant:{$tenantId}:service:{$serviceId}:availability:{$minDate}:{$maxDate}");
    }
}
