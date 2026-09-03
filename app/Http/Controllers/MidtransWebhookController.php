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

        // ── Extract Required Fields ──
        $orderId      = $payload['order_id'] ?? null;
        $statusCode   = $payload['status_code'] ?? null;
        $grossAmount  = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('Midtrans Booking Webhook: Missing required fields', $payload);
            return response()->json(['message' => 'Missing required fields'], 400);
        }

        // ── Find Payment (without TenantScope since context is not set yet) ──
        $payment = Payment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('order_id', $orderId)
            ->first();

        if (!$payment) {
            Log::warning('Midtrans Webhook: Payment not found', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // ── Determine Server Key (Platform vs Owner Tenant) ──
        $serverKey = config('midtrans.server_key');
        $tenant = $payment->tenant;
        if ($tenant && $tenant->payment_mode === 'owner' && $payment->tipe === 'booking') {
            $isProd = $tenant->midtrans_environment === 'production';
            $customKey = $isProd
                ? $tenant->midtrans_prod_server_key
                : $tenant->midtrans_sandbox_server_key;
            if (!empty($customKey)) {
                $serverKey = $customKey;
            }
        }

        // ── Validate Signature ──
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!hash_equals($expectedSignature, $signatureKey)) {
            Log::warning('Midtrans Webhook: Signature mismatch', [
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Set TenantContext based on the payment's tenant so all subsequent queries work correctly
        app(\App\Support\TenantContext::class)->setTenantId($payment->idtenant);

        try {
            // P0-03 & P0-04: Delegate to MidtransPaymentService
            $paymentService = app(\App\Services\MidtransPaymentService::class);
            $paymentService->syncStatus($payment, $payload);
        } finally {
            app(\App\Support\TenantContext::class)->clear();
        }

        return response()->json(['message' => 'OK']);
    }
}
