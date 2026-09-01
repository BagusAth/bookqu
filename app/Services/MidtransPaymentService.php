<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UsageLog;
use App\Notifications\BookingPaidNotification;
use App\Notifications\NewBookingOwnerNotification;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
    private function processSuccess(Payment $payment, ?string $paymentType, ?string $transactionStatus): array
    {
        return DB::transaction(function () use ($payment, $paymentType, $transactionStatus) {
            // P0-06: Concurrency & Idempotency
            $payment = Payment::lockForUpdate()->find($payment->id);

            // P0-05: State Machine check
            if ($payment->status === 'sukses') {
                return [
                    'status' => 'sukses',
                    'transaction_status' => $transactionStatus,
                    'message' => 'Pembayaran sudah dikonfirmasi sebelumnya.',
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
                // P0-06: Lock the booking to prevent race condition with other hooks or users
                $booking = Booking::with(['layanan', 'tenant.user', 'payment'])
                    ->lockForUpdate()
                    ->where('idpayment', $payment->id)
                    ->first();

                // P0-09: Ensure Tenant Relationship consistency
                if ($booking && $booking->idtenant === $payment->idtenant) {
                    $wasPaid = ($booking->status === 'paid' || $booking->status === 'completed');

                    if (!$wasPaid) {
                        $booking->update(['status' => 'paid']);

                        // Kirim notifikasi email ke owner bisnis
                        $owner = $booking->tenant?->user;
                        if ($owner && $owner->email) {
                            try {
                                $owner->notify(new NewBookingOwnerNotification($booking));
                            } catch (\Exception $e) {
                                Log::error('Gagal kirim notif booking ke owner: ' . $e->getMessage());
                            }
                        }

                        // Catat penggunaan booking ke usage_logs
                        try {
                            UsageLog::record($booking->idtenant, 'booking');
                        } catch (\Exception $e) {
                            Log::error('Gagal catat usage log booking: ' . $e->getMessage());
                        }

                        // Kirim notifikasi email ke pelanggan
                        if ($payment->email_pembayar) {
                            try {
                                Notification::route('mail', $payment->email_pembayar)
                                    ->notify(new BookingPaidNotification($booking));
                            } catch (\Exception $e) {
                                Log::error('Gagal kirim notif booking ke pelanggan: ' . $e->getMessage());
                            }
                        }

                        // Invalidate cache ketersediaan jadwal
                        if ($booking->idlayanan && $booking->tanggalbooking) {
                            $tanggal = is_string($booking->tanggalbooking) 
                                ? $booking->tanggalbooking 
                                : $booking->tanggalbooking->format('Y-m-d');
                            $this->clearScheduleCache($booking->idtenant, $booking->idlayanan, [$tanggal]);
                        }
                        // Invalidate availability cache for the service
                        $this->clearAvailabilityCache($booking->idtenant, $booking->idlayanan);
                    }
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
    private function processFailed(Payment $payment, ?string $paymentType, ?string $transactionStatus): array
    {
        return DB::transaction(function () use ($payment, $paymentType, $transactionStatus) {
            $payment = Payment::lockForUpdate()->find($payment->id);

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
                $booking = Booking::lockForUpdate()->where('idpayment', $payment->id)->first();
                if ($booking && $booking->status !== 'cancelled' && $booking->idtenant === $payment->idtenant) {
                    $booking->update(['status' => 'cancelled']);

                    // Invalidate cache agar jadwal kembali tersedia bagi pelanggan lain
                    if ($booking->idlayanan && $booking->tanggalbooking) {
                        $tanggal = is_string($booking->tanggalbooking) 
                            ? $booking->tanggalbooking 
                            : $booking->tanggalbooking->format('Y-m-d');
                        $this->clearScheduleCache($booking->idtenant, $booking->idlayanan, [$tanggal]);
                    }
                    // Invalidate availability cache after cancellation
                    $this->clearAvailabilityCache($booking->idtenant, $booking->idlayanan);
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
