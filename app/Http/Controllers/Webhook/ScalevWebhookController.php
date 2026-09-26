<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MidtransPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScalevWebhookController extends Controller
{
    /**
     * Handle Scalev payment notification webhook.
     *
     * Scalev akan mengirim HTTP POST ke endpoint ini ketika status pembayaran berubah.
     * Endpoint ini dikecualikan dari CSRF dan auth middleware (dikonfigurasi di bootstrap/app.php).
     *
     * Dokumentasi Scalev: sesuaikan struktur payload dan validasi signature sesuai dokumen resmi.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Scalev Webhook Received:', $payload);

        // ── Validasi field wajib dari payload Scalev ──
        // Sesuaikan nama field ini dengan dokumentasi resmi Scalev
        $orderId   = $payload['reference_id'] ?? $payload['order_id'] ?? null;
        $status    = $payload['status'] ?? null;
        $signature = $request->header('X-Scalev-Signature') ?? $payload['signature'] ?? null;

        if (!$orderId || !$status) {
            Log::warning('Scalev Webhook: Missing required fields', $payload);
            return response()->json(['message' => 'Missing required fields'], 400);
        }

        // ── Cari Payment berdasarkan order_id (tanpa TenantScope) ──
        $payment = Payment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('order_id', $orderId)
            ->first();

        if (!$payment) {
            Log::warning('Scalev Webhook: Payment not found', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // ── Validasi Signature (HMAC SHA-256) ──
        // Ambil secret key yang sesuai: platform atau milik tenant
        $secretKey = config('scalev.secret_key');
        $tenant    = $payment->tenant;

        if ($tenant && $tenant->payment_mode === 'owner' && $payment->tipe === 'booking') {
            if (!empty($tenant->scalev_secret_key)) {
                $secretKey = $tenant->scalev_secret_key;
            }
        }

        // Validasi signature jika secret key tersedia dan Scalev mengirimkan signature
        // Sesuaikan algoritma & format string yang di-hash sesuai dokumentasi resmi Scalev
        if ($signature && $secretKey) {
            $expectedSignature = hash_hmac('sha256', $orderId . $status, $secretKey);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Scalev Webhook: Signature mismatch', ['order_id' => $orderId]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        // ── Set TenantContext agar semua query selanjutnya bekerja dengan benar ──
        app(\App\Support\TenantContext::class)->setTenantId($payment->idtenant);

        try {
            // ── Mapping status Scalev → format syncStatus Bookqu ──
            // Sesuaikan nilai status dengan yang dikembalikan API Scalev
            $statusMap = [
                'PAID'     => 'settlement',
                'SETTLED'  => 'settlement',
                'EXPIRED'  => 'expire',
                'CANCELED' => 'cancel',
                'FAILED'   => 'deny',
                'PENDING'  => 'pending',
            ];

            $mappedTransactionStatus = $statusMap[strtoupper($status)] ?? 'pending';

            // Reuse logika idempoten sukses/gagal dari MidtransPaymentService
            $paymentService = app(MidtransPaymentService::class);
            $paymentService->syncStatus($payment, [
                'transaction_status' => $mappedTransactionStatus,
                'payment_type'       => 'scalev',
                'fraud_status'       => 'accept', // Scalev tidak menggunakan fraud_status
            ]);

            Log::info('Scalev Webhook: Processed successfully', [
                'order_id'   => $orderId,
                'status'     => $status,
                'mapped_to'  => $mappedTransactionStatus,
                'payment_id' => $payment->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Scalev Webhook: Processing failed', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            // Kembalikan 500 agar Scalev melakukan retry
            return response()->json(['message' => 'Internal server error'], 500);
        } finally {
            app(\App\Support\TenantContext::class)->clear();
        }

        return response()->json(['message' => 'OK'], 200);
    }
}
