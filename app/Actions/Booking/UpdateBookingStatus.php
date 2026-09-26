<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBookingStatus
{
    use ClearsBookingCache;

    public function __construct(
        protected CancelBooking $cancelBooking
    ) {}

    /**
     * Update booking status (used primarily by owner).
     *
     * @param Booking $booking
     * @param string $newStatus 'paid'|'completed'|'cancelled'
     * @param string $actor 'owner'
     * @return array{success: bool, error?: string, booking?: Booking}
     */
    public function execute(Booking $booking, string $newStatus, string $actor = 'owner'): array
    {
        $statusLama = $booking->status;

        // Check if transition is valid
        if (!BookingState::canTransition($statusLama, $newStatus)) {
            return [
                'success' => false,
                'error'   => "Status tidak dapat diubah dari '{$statusLama}' ke '{$newStatus}'.",
            ];
        }

        // If cancelling, delegate to CancelBooking
        if ($newStatus === BookingState::STATUS_CANCELLED) {
            return $this->cancelBooking->execute($booking, $actor);
        }

        // Multi-slot check on completed if applicable
        $booking->update(['status' => $newStatus]);

        // If marked as paid or completed, ensure management tokens exist and payment status is updated
        if (in_array($newStatus, [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED], true)) {
            if (!$booking->booking_code) {
                $booking->assignManagementTokens();
            }
            if ($booking->payment && $booking->payment->status !== 'sukses') {
                $booking->payment->update(['status' => 'sukses']);
            }
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
            Log::warning('UpdateBookingStatus: Notification failed: ' . $e->getMessage());
        }

        return ['success' => true, 'booking' => $booking];
    }
}
