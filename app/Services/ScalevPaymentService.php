<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScalevPaymentService
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('scalev.api_key');
        $this->apiUrl = config('scalev.api_url');
    }

    /**
     * Konfigurasi credentials khusus Tenant (jika fitur multi-tenant aktif)
     */
    public function configureForPayment(Payment $payment): void
    {
        $tenant = $payment->tenant;
        if ($tenant && $tenant->payment_mode === 'owner' && $payment->tipe === 'booking') {
            if ($tenant->scalev_api_key) {
                $this->apiKey = $tenant->scalev_api_key;
                
                // Gunakan base url sesuai environment tenant
                $this->apiUrl = $tenant->scalev_environment === 'production' 
                    ? 'https://api.scalev.id/v1' 
                    : 'https://api.sandbox.scalev.id/v1';
            }
        }
    }

    /**
     * Membuat Invoice / Link Checkout ke API Scalev
     */
    public function createInvoice(Payment $payment, array $customerDetails, array $itemDetails): string
    {
        $this->configureForPayment($payment);

        // Contoh Payload untuk API Scalev (Sesuaikan dengan dokumentasi resmi Scalev)
        $payload = [
            'reference_id' => $payment->order_id,
            'amount' => (int) $payment->jumlah,
            'customer' => [
                'name' => $customerDetails['first_name'] ?? $payment->nama_pembayar,
                'email' => $customerDetails['email'] ?? $payment->email_pembayar,
                'phone' => $customerDetails['phone'] ?? $payment->hp_pembayar,
            ],
            'items' => $itemDetails,
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->apiUrl}/invoices", $payload);

            if ($response->successful()) {
                // Asumsi respon sukses Scalev mengembalikan 'checkout_url'
                $checkoutUrl = $response->json('data.checkout_url'); 
                
                // Kita bisa menyimpan URL ini di kolom snap_token yang sudah ada
                $payment->update(['snap_token' => $checkoutUrl]);
                
                return $checkoutUrl;
            }

            Log::error('Scalev API Error: ' . $response->body());
            throw new \Exception('Gagal membuat invoice Scalev: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Scalev Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Memverifikasi pembayaran dan sinkronisasi ke MidtransPaymentService yang sama
     */
    public function verifyAndSync(Payment $payment): array
    {
        // ... Logika untuk mengecek status via API Get Invoice Scalev
        // Kemudian teruskan ke logika idempoten sukses/gagal di sistem Bookqu
        // Kita bisa reuse fungsi syncStatus dari MidtransPaymentService karena logika state DB-nya sama
        $midtransService = app(MidtransPaymentService::class);

        if ($payment->status === 'sukses') {
            return $midtransService->verifyAndSync($payment);
        }

        $this->configureForPayment($payment);

        try {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->apiUrl}/invoices/{$payment->order_id}");
                
            if ($response->successful()) {
                $status = $response->json('data.status'); // misal: PAID, PENDING, EXPIRED

                // Mapping status Scalev ke format Bookqu/Midtrans
                $mappedStatus = 'pending';
                if ($status === 'PAID') $mappedStatus = 'settlement';
                if (in_array($status, ['EXPIRED', 'CANCELED'])) $mappedStatus = 'cancel';

                return $midtransService->syncStatus($payment, [
                    'transaction_status' => $mappedStatus,
                    'payment_type' => 'scalev',
                ]);
            }

            throw new \Exception('Scalev API status failed.');
        } catch (\Exception $e) {
            Log::error('Scalev verify error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Gagal memverifikasi ke Scalev.',
                'payment' => $payment,
            ];
        }
    }
}
