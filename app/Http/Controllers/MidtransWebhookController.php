<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmedMail;

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

        if (!hash_equals($expectedSignature, $signatureKey)) {
            Log::warning('Midtrans Webhook: Signature mismatch', [
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // ── Find Payment ──
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('Midtrans Webhook: Payment not found', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // P0-03 & P0-04: Delegate to MidtransPaymentService
        $paymentService = app(\App\Services\MidtransPaymentService::class);
        $paymentService->syncStatus($payment, $payload);

        return response()->json(['message' => 'OK']);
    }
}
