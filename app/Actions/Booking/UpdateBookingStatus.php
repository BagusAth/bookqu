<?php

namespace App\Actions\Booking;

use App\Actions\Payment\PaymentLifecycle;
use App\Domain\Booking\BookingState;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBookingStatus
{
    use ClearsBookingCache;

    public function __construct(
        protected CancelBooking $cancelBooking,
        protected PaymentLifecycle $paymentLifecycle
    ) {}

    /**
     * Update booking status (used primarily by owner).
     *
     * @param  string  $newStatus  'paid'|'completed'|'cancelled'
     * @param  string  $actor  'owner'
     * @return array{success: bool, error?: string, booking?: Booking}
     */
    public function execute(Booking $booking, string $newStatus, string $actor = 'owner'): array
    {
        $statusLama = $booking->status;

        // Check if transition is valid
        if (! BookingState::canTransition($statusLama, $newStatus)) {
            return [
                'success' => false,
                'error' => "Status tidak dapat diubah dari '{$statusLama}' ke '{$newStatus}'.",
            ];
        }

        // If cancelling, delegate to CancelBooking
        if ($newStatus === BookingState::STATUS_CANCELLED) {
            return $this->cancelBooking->execute($booking, $actor);
        }

        // Marking paid or completed records an open payment as settled by the owner for the
        // whole payment group (ADR-0004 decision 6); a slot of a group is never settled alone.
        $payment = $booking->payment;
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

        $booking->update(['status' => $newStatus]);

        if (in_array($newStatus, [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED], true) && ! $booking->booking_code) {
            $booking->assignBookingCode();
        }

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

        return ['success' => true, 'booking' => $booking];
    }
}
