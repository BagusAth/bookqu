<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Mail\BookingGroupInvoiceMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UsageLog;
use App\Notifications\NewBookingOwnerNotification;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SynchronizePaymentStatus
{
    use ClearsBookingCache;

    /**
     * Single Source of Truth for synchronizing Midtrans transaction status to Payment & Booking / Subscription.
     * Accepts array (from webhook) or object (from MidtransTransaction::status).
     *
     * @param Payment $payment
     * @param array|object $midtransPayload
     * @return array<string, mixed>
     */
    public function syncStatus(Payment $payment, array|object $midtransPayload): array
    {
        if ($payment->idtenant && !app(TenantContext::class)->hasTenant()) {
            app(TenantContext::class)->setTenantId($payment->idtenant);
        }

        $payload = is_object($midtransPayload) ? (array) $midtransPayload : $midtransPayload;

        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus       = $payload['fraud_status'] ?? null;
        $paymentType       = $payload['payment_type'] ?? null;

        Log::info('SynchronizePaymentStatus processing:', [
            'payment_id'         => $payment->id,
            'order_id'           => $payment->order_id,
            'transaction_status' => $transactionStatus,
            'fraud_status'       => $fraudStatus,
            'payment_type'       => $paymentType,
        ]);

        $domainStatus = PaymentState::mapTransactionStatus($transactionStatus, $fraudStatus);

        if ($domainStatus === PaymentState::STATUS_SUKSES) {
            return $this->processSuccess($payment, $paymentType, $transactionStatus);
        }

        if ($domainStatus === PaymentState::STATUS_GAGAL) {
            return $this->processFailed($payment, $paymentType, $transactionStatus);
        }

        if ($domainStatus === PaymentState::STATUS_PENDING) {
            $customMessage = ($transactionStatus === 'capture' && $fraudStatus === 'challenge')
                ? 'Pembayaran sedang dalam review fraud.'
                : null;

            return $this->processPending($payment, $paymentType, $transactionStatus, $customMessage);
        }

        Log::warning('SynchronizePaymentStatus: Unknown transaction status encountered', [
            'payment_id'         => $payment->id,
            'order_id'           => $payment->order_id,
            'transaction_status' => $transactionStatus,
        ]);

        return [
            'status'             => 'unknown',
            'transaction_status' => $transactionStatus,
            'message'            => 'Status transaksi tidak dikenal.',
            'payment'            => $payment,
        ];
    }

    /**
     * Process payment success status idempotently with database locking and transaction.
     *
     * @param Payment $payment
     * @param string|null $paymentType
     * @param string|null $transactionStatus
     * @return array<string, mixed>
     */
    public function processSuccess(
        Payment $payment,
        ?string $paymentType = null,
        ?string $transactionStatus = 'settlement'
    ): array {
        return DB::transaction(function () use ($payment, $paymentType, $transactionStatus) {
            /** @var Payment|null $lockedPayment */
            $lockedPayment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);
            if ($lockedPayment) {
                app(TenantContext::class)->setTenantId($lockedPayment->idtenant);
                $payment = $lockedPayment;
            }

            // P0-05: State Machine check
            if ($payment->status === PaymentState::STATUS_SUKSES) {
                return [
                    'status'             => PaymentState::STATUS_SUKSES,
                    'transaction_status' => $transactionStatus,
                    'message'            => 'Pembayaran sudah dikonfirmasi sebelumnya.',
                    'payment'            => $payment,
                ];
            }

            if ($payment->status === PaymentState::STATUS_GAGAL) {
                Log::warning('Late Settlement: Settlement received for failed/cancelled payment', [
                    'payment_id' => $payment->id,
                    'order_id'   => $payment->order_id,
                ]);

                return [
                    'status'             => PaymentState::STATUS_GAGAL,
                    'transaction_status' => $transactionStatus,
                    'message'            => 'Pembayaran sudah gagal atau dibatalkan sebelumnya dan tidak dapat diubah.',
                    'payment'            => $payment,
                ];
            }

            // 1. Subscription Payment
            if ($payment->tipe === PaymentState::TIPE_SUBSCRIPTION) {
                $payment->update([
                    'status' => PaymentState::STATUS_SUKSES,
                    'metode' => $paymentType ?? $payment->metode ?? PaymentState::METODE_MIDTRANS,
                ]);

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
                        'idtenant'           => $payment->idtenant,
                        'idplan'             => $payment->idplan,
                        'status'             => 'active',
                        'langganan_mulai'    => now(),
                        'langganan_berakhir' => now()->addMonth(),
                    ]);
                }
            }

            // 2. Booking Payment
            if ($payment->tipe === PaymentState::TIPE_BOOKING) {
                $bookings = Booking::withoutGlobalScopes()
                    ->with(['layanan', 'tenant.user', 'payment'])
                    ->lockForUpdate()
                    ->where('idpayment', $payment->id)
                    ->get();

                // State machine check: if all bookings already paid, idempotent return
                $allPaid = $bookings->isNotEmpty() && $bookings->every(fn($b) => in_array($b->status, ['paid', 'completed'], true));
                if ($allPaid && $payment->status === PaymentState::STATUS_SUKSES) {
                    return [
                        'status'             => PaymentState::STATUS_SUKSES,
                        'transaction_status' => $transactionStatus,
                        'message'            => 'Seluruh booking sudah dibayar sebelumnya.',
                        'payment'            => $payment,
                    ];
                }

                // Late webhook / Mixed booking guard: if any booking was already cancelled, do not resurrect
                $hasCancelled = $bookings->contains(fn($b) => $b->status === 'cancelled');
                if ($hasCancelled) {
                    Log::warning('Late Webhook Anomaly: Settlement received for cancelled booking(s) in payment group', [
                        'payment_id'  => $payment->id,
                        'booking_ids' => $bookings->pluck('id')->all(),
                    ]);

                    return [
                        'status'             => PaymentState::STATUS_GAGAL,
                        'transaction_status' => $transactionStatus,
                        'message'            => 'Terdapat booking yang sudah dibatalkan dalam grup reservasi ini. Settlement diabaikan untuk mencegah inkonsistensi.',
                        'payment'            => $payment,
                    ];
                }

                // Update Payment status to sukses
                $payment->update([
                    'status' => PaymentState::STATUS_SUKSES,
                    'metode' => $paymentType ?? $payment->metode ?? PaymentState::METODE_MIDTRANS,
                ]);

                $paidBookings = [];
                foreach ($bookings as $booking) {
                    if ($booking->idtenant === $payment->idtenant && $booking->status === 'pending') {
                        $booking->update(['status' => 'paid']);

                        if (!$booking->booking_code) {
                            $booking->assignManagementTokens();
                            $booking->refresh();
                        }

                        try {
                            UsageLog::record($booking->idtenant, 'booking');
                        } catch (\Exception $e) {
                            Log::error('Gagal catat usage log booking: ' . $e->getMessage());
                        }

                        $paidBookings[] = $booking;
                    } elseif ($booking->status === 'paid') {
                        $paidBookings[] = $booking;
                    }
                }

                if (!empty($paidBookings)) {
                    DB::afterCommit(function () use ($paidBookings, $payment) {
                        $firstBooking = $paidBookings[0];

                        // Send owner notification (1 per group)
                        $owner = $firstBooking->tenant?->user;
                        if ($owner && $owner->email) {
                            try {
                                $owner->notify(new NewBookingOwnerNotification($firstBooking));
                            } catch (\Exception $e) {
                                Log::error('Gagal kirim notif booking ke owner: ' . $e->getMessage());
                            }
                        }

                        // Send customer invoice email (1 per group)
                        $recipientEmail = $firstBooking->email ?: $payment->email_pembayar;
                        if ($recipientEmail) {
                            try {
                                $manageUrl = $payment->getManageUrl();
                                Mail::to($recipientEmail)
                                    ->send(new BookingGroupInvoiceMail($payment, collect($paidBookings), $manageUrl));
                            } catch (\Exception $e) {
                                Log::error('Gagal kirim email invoice grup ke pelanggan: ' . $e->getMessage());
                            }
                        }

                        // Invalidate cache for each slot
                        foreach ($paidBookings as $bk) {
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
                'status'             => PaymentState::STATUS_SUKSES,
                'transaction_status' => $transactionStatus,
                'message'            => 'Pembayaran berhasil dikonfirmasi.',
                'payment'            => $payment,
            ];
        });
    }

    /**
     * Process payment failed / deny / expire / cancel idempotently.
     *
     * @param Payment $payment
     * @param string|null $paymentType
     * @param string|null $transactionStatus
     * @return array<string, mixed>
     */
    public function processFailed(
        Payment $payment,
        ?string $paymentType = null,
        ?string $transactionStatus = 'cancel'
    ): array {
        return DB::transaction(function () use ($payment, $paymentType, $transactionStatus) {
            /** @var Payment|null $lockedPayment */
            $lockedPayment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);
            if ($lockedPayment) {
                app(TenantContext::class)->setTenantId($lockedPayment->idtenant);
                $payment = $lockedPayment;
            }

            if ($payment->status === PaymentState::STATUS_SUKSES || $payment->status === PaymentState::STATUS_GAGAL) {
                return [
                    'status'             => $payment->status,
                    'transaction_status' => $transactionStatus,
                    'message'            => 'Pembayaran gagal/batal atau sudah selesai.',
                    'payment'            => $payment,
                ];
            }

            $payment->update([
                'status' => PaymentState::STATUS_GAGAL,
                'metode' => $paymentType ?? $payment->metode,
            ]);

            $cancelledBookings = [];
            if ($payment->tipe === PaymentState::TIPE_BOOKING) {
                $bookings = Booking::withoutGlobalScopes()
                    ->where('idpayment', $payment->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($bookings as $booking) {
                    if ($booking->idtenant === $payment->idtenant && $booking->status === 'pending') {
                        $booking->update(['status' => 'cancelled']);
                        $cancelledBookings[] = $booking;
                    }
                }

                if (!empty($cancelledBookings)) {
                    DB::afterCommit(function () use ($cancelledBookings) {
                        foreach ($cancelledBookings as $booking) {
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
                'status'             => PaymentState::STATUS_GAGAL,
                'transaction_status' => $transactionStatus,
                'message'            => 'Pembayaran gagal atau dibatalkan.',
                'payment'            => $payment,
            ];
        });
    }

    /**
     * Process pending status idempotently.
     *
     * @param Payment $payment
     * @param string|null $paymentType
     * @param string|null $transactionStatus
     * @param string|null $customMessage
     * @return array<string, mixed>
     */
    public function processPending(
        Payment $payment,
        ?string $paymentType,
        ?string $transactionStatus,
        ?string $customMessage = null
    ): array {
        if ($paymentType && $payment->metode !== $paymentType) {
            $payment->update([
                'metode' => $paymentType,
            ]);
        }

        return [
            'status'             => PaymentState::STATUS_PENDING,
            'transaction_status' => $transactionStatus,
            'message'            => $customMessage ?? 'Pembayaran belum diselesaikan. Silakan selesaikan pembayaran sesuai instruksi.',
            'payment'            => $payment,
        ];
    }
}
