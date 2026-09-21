<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UsageLog;
use App\Mail\BookingInvoiceMail;
use App\Notifications\NewBookingOwnerNotification;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction as MidtransTransaction;

class MidtransPaymentService
{
    use ClearsBookingCache;

    public function __construct()
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production', false);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    public function configureForPayment(Payment $payment): void
    {
        $tenant = $payment->tenant;
        if ($tenant && $tenant->payment_mode === 'owner' && $payment->tipe === 'booking') {
            $isProd = $tenant->midtrans_environment === 'production';
            $serverKey = $isProd
                ? $tenant->midtrans_prod_server_key
                : $tenant->midtrans_sandbox_server_key;

            if ($serverKey) {
                MidtransConfig::$serverKey = $serverKey;
                MidtransConfig::$isProduction = $isProd;
                MidtransConfig::$isSanitized = true;
                MidtransConfig::$is3ds = true;
                return;
            }
        }

        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production', false);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    /**
     * Lakukan server-side verification ke Midtrans API berdasarkan order_id,
     * kemudian sinkronisasikan hasilnya ke database Bookqu.
     *
     * @param Payment $payment
     * @return array
     */
    public function verifyAndSync(Payment $payment): array
    {
        if (!$payment->order_id) {
            Log::warning('Midtrans verifyAndSync: Payment does not have order_id', ['payment_id' => $payment->id]);
            return [
                'status' => $payment->status,
                'message' => 'Order ID tidak ditemukan.',
                'payment' => $payment,
            ];
        }

        // Fast-path: jika di database status sudah sukses, kembalikan langsung tanpa delay
        if ($payment->status === 'sukses') {
            return [
                'status' => 'sukses',
                'transaction_status' => 'settlement',
                'message' => 'Pembayaran berhasil dikonfirmasi.',
                'payment' => $payment,
            ];
        }

        $this->configureForPayment($payment);

        try {
            $midtransStatus = MidtransTransaction::status($payment->order_id);
            return $this->syncStatus($payment, $midtransStatus);
        } catch (\Exception $e) {
            Log::error('Midtrans verifyAndSync Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
            ]);

            return [
                'status' => 'error',
                'message' => 'Gagal memverifikasi status transaksi ke Midtrans: ' . $e->getMessage(),
                'payment' => $payment,
            ];
        }
    }

    /**
     * Single Source of Truth untuk sinkronisasi status transaksi Midtrans ke status Payment & Booking.
     * Dapat menerima array (dari webhook) atau object (dari MidtransTransaction::status).
     *
     * @param Payment $payment
     * @param array|object $midtransPayload
     * @return array
     */
    public function syncStatus(Payment $payment, array|object $midtransPayload): array
    {
        if ($payment->idtenant && !app(\App\Support\TenantContext::class)->hasTenant()) {
            app(\App\Support\TenantContext::class)->setTenantId($payment->idtenant);
        }

        $payload = is_object($midtransPayload) ? (array) $midtransPayload : $midtransPayload;

        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;

        Log::info('Midtrans syncStatus processing:', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => $paymentType,
        ]);

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                return $this->processSuccess($payment, $paymentType, $transactionStatus);
            } elseif ($fraudStatus === 'challenge') {
                return $this->processPending($payment, $paymentType, $transactionStatus, 'Pembayaran sedang dalam review fraud.');
            }
            return $this->processPending($payment, $paymentType, $transactionStatus);
        }

        if ($transactionStatus === 'settlement') {
            return $this->processSuccess($payment, $paymentType, $transactionStatus);
        }

        if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            return $this->processFailed($payment, $paymentType, $transactionStatus);
        }

        if ($transactionStatus === 'pending') {
            return $this->processPending($payment, $paymentType, $transactionStatus);
        }

        // Status tidak dikenal: log dan jangan ubah database secara sembarangan
        Log::warning('Midtrans syncStatus: Unknown transaction status encountered', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'transaction_status' => $transactionStatus,
        ]);

        return [
            'status' => 'unknown',
            'transaction_status' => $transactionStatus,
            'message' => 'Status transaksi tidak dikenal.',
            'payment' => $payment,
        ];
    }

    /**
     * Proses status sukses / settlement / capture+accept secara idempoten.
     */
    public function processSuccess(Payment $payment, ?string $paymentType = null, ?string $transactionStatus = 'settlement'): array
    {
        return DB::transaction(function () use ($payment, $paymentType, $transactionStatus) {
            // P0-06: Concurrency & Idempotency
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);
            if ($payment) {
                app(\App\Support\TenantContext::class)->setTenantId($payment->idtenant);
            }

            // P0-05: State Machine check
            if ($payment->status === 'sukses') {
                return [
                    'status' => 'sukses',
                    'transaction_status' => $transactionStatus,
                    'message' => 'Pembayaran sudah dikonfirmasi sebelumnya.',
                    'payment' => $payment,
                ];
            }

            if ($payment->status === 'gagal') {
                Log::warning('Late Settlement: Settlement received for failed/cancelled payment', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                ]);
                return [
                    'status' => 'gagal',
                    'transaction_status' => $transactionStatus,
                    'message' => 'Pembayaran sudah gagal atau dibatalkan sebelumnya dan tidak dapat diubah.',
                    'payment' => $payment,
                ];
            }

            // 1. Update Payment status
            $payment->update([
                'status' => 'sukses',
                'metode' => $paymentType ?? $payment->metode ?? 'midtrans',
            ]);

            // 2. Jika tipe pembayaran adalah subscription
            if ($payment->tipe === 'subscription') {
                $hasActiveSub = Subscription::where('idtenant', $payment->idtenant)
                    ->where('idplan', $payment->idplan)
                    ->where('status', 'active')
                    ->where('created_at', '>=', $payment->created_at)
                    ->exists();

                if (!$hasActiveSub) {
                    Subscription::where('idtenant', $payment->idtenant)
                        ->whereIn('status', ['trial', 'active'])
                        ->update(['status' => 'expired']);

                    Subscription::create([
                        'idtenant' => $payment->idtenant,
                        'idplan' => $payment->idplan,
                        'status' => 'active',
                        'langganan_mulai' => now(),
                        'langganan_berakhir' => now()->addMonth(),
                    ]);
                }
            }

            // 3. Jika tipe pembayaran adalah booking
            if ($payment->tipe === 'booking') {
                // Lock ALL bookings linked to this payment
                $bookings = Booking::with(['layanan', 'tenant.user', 'payment'])
                    ->lockForUpdate()
                    ->where('idpayment', $payment->id)
                    ->get();

                // Late webhook guard: if any booking was already cancelled, do not resurrect
                $hasCancelled = $bookings->contains(fn($b) => $b->status === 'cancelled');
                if ($hasCancelled) {
                    Log::warning('Late Webhook: Settlement received for cancelled booking(s)', [
                        'payment_id' => $payment->id,
                        'booking_ids' => $bookings->pluck('id')->all(),
                    ]);
                }

                $paidBookings = [];
                foreach ($bookings as $booking) {
                    if ($booking->idtenant === $payment->idtenant && $booking->status === 'pending') {
                        $booking->update(['status' => 'paid']);

                        if (!$booking->booking_code) {
                            $booking->assignManagementTokens();
                            $booking->refresh();
                        }

                        // Catat penggunaan booking ke usage_logs (inside transaction)
                        try {
                            UsageLog::record($booking->idtenant, 'booking');
                        } catch (\Exception $e) {
                            Log::error('Gagal catat usage log booking: ' . $e->getMessage());
                        }

                        $paidBookings[] = $booking;
                    }
                }

                if (!empty($paidBookings)) {
                    DB::afterCommit(function () use ($paidBookings, $payment) {
                        $firstBooking = $paidBookings[0];
                        // Kirim notifikasi email ke owner bisnis
                        $owner = $firstBooking->tenant?->user;
                        if ($owner && $owner->email) {
                            try {
                                $owner->notify(new NewBookingOwnerNotification($firstBooking));
                            } catch (\Exception $e) {
                                Log::error('Gagal kirim notif booking ke owner: ' . $e->getMessage());
                            }
                        }

                        // Kirim email invoice ke pelanggan untuk setiap booking & invalidate cache
                        foreach ($paidBookings as $bk) {
                            if ($bk->email) {
                                try {
                                    Mail::to($bk->email)
                                        ->send(new BookingInvoiceMail($bk));
                                } catch (\Exception $e) {
                                    Log::error('Gagal kirim email invoice ke pelanggan: ' . $e->getMessage());
                                }
                            }

                            // Invalidate cache ketersediaan jadwal
                            if ($bk->idlayanan && $bk->tanggalbooking) {
                                $tanggal = is_string($bk->tanggalbooking) 
                                    ? $bk->tanggalbooking 
                                    : $bk->tanggalbooking->format('Y-m-d');
                                $this->clearScheduleCache($bk->idtenant, $bk->idlayanan, [$tanggal]);
                                $this->clearAvailabilityCache($bk->idtenant, $bk->idlayanan);
                            }
                        }
                    });
                }
            }

            return [
                'status' => 'sukses',
                'transaction_status' => $transactionStatus,
                'message' => 'Pembayaran berhasil dikonfirmasi.',
                'payment' => $payment,
            ];
        });
    }

    /**
     * Proses status gagal / deny / expire / cancel secara idempoten.
     */
    public function processFailed(Payment $payment, ?string $paymentType = null, ?string $transactionStatus = 'cancel'): array
    {
        return DB::transaction(function () use ($payment, $paymentType, $transactionStatus) {
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);
            if ($payment) {
                app(\App\Support\TenantContext::class)->setTenantId($payment->idtenant);
            }

            // Jika sudah sukses sebelumnya, jangan ubah menjadi gagal
            if ($payment->status === 'sukses' || $payment->status === 'gagal') {
                return [
                    'status' => $payment->status,
                    'transaction_status' => $transactionStatus,
                    'message' => 'Pembayaran gagal/batal atau sudah selesai.',
                    'payment' => $payment,
                ];
            }

            $payment->update([
                'status' => 'gagal',
                'metode' => $paymentType ?? $payment->metode ?? 'midtrans',
            ]);

            if ($payment->tipe === 'booking') {
                $bookings = Booking::lockForUpdate()->where('idpayment', $payment->id)->get();
                $cancelledBookings = [];

                foreach ($bookings as $booking) {
                    if ($booking->status !== 'cancelled' && $booking->idtenant === $payment->idtenant) {
                        $booking->update(['status' => 'cancelled']);
                        $cancelledBookings[] = $booking;
                    }
                }

                if (!empty($cancelledBookings)) {
                    DB::afterCommit(function () use ($cancelledBookings) {
                        foreach ($cancelledBookings as $booking) {
                            // Invalidate cache agar jadwal kembali tersedia bagi pelanggan lain
                            if ($booking->idlayanan && $booking->tanggalbooking) {
                                $tanggal = is_string($booking->tanggalbooking) 
                                    ? $booking->tanggalbooking 
                                    : $booking->tanggalbooking->format('Y-m-d');
                                $this->clearScheduleCache($booking->idtenant, $booking->idlayanan, [$tanggal]);
                                $this->clearAvailabilityCache($booking->idtenant, $booking->idlayanan);
                            }
                        }
                    });
                }
            }

            return [
                'status' => 'gagal',
                'transaction_status' => $transactionStatus,
                'message' => 'Pembayaran gagal atau dibatalkan.',
                'payment' => $payment,
            ];
        });
    }

    /**
     * Proses status pending secara idempoten.
     */
    private function processPending(Payment $payment, ?string $paymentType, ?string $transactionStatus, ?string $customMessage = null): array
    {
        if ($paymentType && $payment->metode !== $paymentType) {
            $payment->update([
                'metode' => $paymentType,
            ]);
        }

        return [
            'status' => 'pending',
            'transaction_status' => $transactionStatus,
            'message' => $customMessage ?? 'Pembayaran belum diselesaikan. Silakan selesaikan pembayaran sesuai instruksi.',
            'payment' => $payment,
        ];
    }
}
