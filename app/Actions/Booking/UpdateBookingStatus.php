<?php

namespace App\Actions\Booking;

use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateBookingStatus
{
    use ClearsBookingCache;

    public function __construct(
        protected CancelBooking $cancelBooking,
        protected PaymentLifecycle $paymentLifecycle,
        protected MarkBookingNoShow $markBookingNoShow,
    ) {}

    /**
     * Update booking status (used primarily by owner).
     *
     * @param  string  $newStatus  'paid'|'completed'|'cancelled'|'no_show'
     * @param  string  $actor  'owner'
     * @return array{success: bool, error?: string, booking?: Booking}
     */
    public function execute(Booking $booking, string $newStatus, string $actor = 'owner', ?string $reason = null): array
    {
        if ($newStatus === BookingState::STATUS_CANCELLED) {
            return $this->cancelBooking->execute($booking, $actor, $reason);
        }
        if ($newStatus === BookingState::STATUS_NO_SHOW) {
            return $this->markBookingNoShow->execute($booking);
        }

        return DB::transaction(function () use ($booking, $newStatus, $actor): array {
            ClaimSlots::lockTenant((int) $booking->idtenant);
            $booking->refresh();

            return $this->updateLocked($booking, $newStatus, $actor);
        });
    }

    /** @return array{success: bool, error?: string, booking?: Booking} */
    private function updateLocked(Booking $booking, string $newStatus, string $actor): array
    {
        if (! BookingState::canTransition($booking->status, $newStatus)) {
            return ['success' => false, 'error' => "Status tidak dapat diubah dari '{$booking->status}' ke '{$newStatus}'."];
        }
        $payment = $booking->payment;
        if ($payment instanceof Payment) {
            if ((int) $payment->idtenant !== (int) $booking->idtenant) {
                return ['success' => false, 'error' => 'Pembayaran booking tidak tersedia.'];
            }
            $holdEnd = BookingRules::onlinePaymentHoldEnd($booking, $payment);
            if ($holdEnd !== null) {
                return ['success' => false, 'error' => 'Menunggu pembayaran online, berakhir '.$holdEnd->setTimezone('Asia/Jakarta')->format('H:i').' WIB.'];
            }
            if (PaymentRules::hasProviderLink($payment)
                && ($booking->status === BookingState::STATUS_PENDING || $payment->status !== PaymentState::STATUS_SUKSES)) {
                return ['success' => false, 'error' => 'Pembayaran online tidak dapat dicatat lunas secara manual.'];
            }
        }

        // Marking paid or completed records an open payment as settled by the owner for the
        // whole payment group (ADR-0004 decision 6); a slot of a group is never settled alone.
        if (in_array($newStatus, [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED], true)
            && $payment instanceof Payment && $payment->status !== PaymentState::STATUS_SUKSES) {
            $settlement = $this->paymentLifecycle->transition(
                $payment,
                PaymentTransition::SettledByOwner,
                PaymentState::METODE_CASH
            );

            if ($settlement->status() !== PaymentState::STATUS_SUKSES) {
                return [
                    'success' => false,
                    'error' => 'Pembayaran untuk booking ini tidak dapat dicatat lunas.',
                ];
            }

            $booking->refresh();
        }

        if ($booking->status !== $newStatus) {
            $booking->update(['status' => $newStatus]);
            BookingLog::record($booking->id, $newStatus, 'Status booking diperbarui.');
        }

        if (in_array($newStatus, [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED], true) && ! $booking->booking_code) {
            $booking->assignBookingCode();
        }

        DB::afterCommit(function () use ($booking, $newStatus, $actor): void {
            // Invalidate availability cache when a completion frees/affects a slot
            if ($newStatus === BookingState::STATUS_COMPLETED && $booking->idlayanan && $booking->tanggalbooking) {
                $tanggal = $booking->tanggalbooking instanceof Carbon
                    ? $booking->tanggalbooking->toDateString()
                    : Carbon::parse($booking->tanggalbooking)->toDateString();

                $this->clearBookingAvailabilityCache(
                    (int) $booking->idtenant,
                    (int) $booking->idlayanan,
                    $tanggal
                );
            }

            // Notifikasi perubahan status
            try {
                $booking->load(['tenant.user', 'layanan']);
                $owner = $booking->tenant?->user;
                if ($owner) {
                    $owner->notify(new BookingStatusChangedOwnerNotification(
                        $booking,
                        $newStatus,
                        ['updated_by' => $actor]
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning('UpdateBookingStatus: Notification failed', ['exception_type' => $e::class, 'booking_id' => $booking->id]);
            }
        });

        return ['success' => true, 'booking' => $booking];
    }
}
