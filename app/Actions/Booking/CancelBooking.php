<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Refund;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CancelBooking
{
    use ClearsBookingCache;

    /**
     * Cancel a booking (shared between Customer and Owner).
     *
     * @param Booking $booking
     * @param string $actor 'customer'|'owner'
     * @param string|null $reason
     * @return array{success: bool, error?: string, booking?: Booking}
     */
    public function execute(Booking $booking, string $actor = 'customer', ?string $reason = null): array
    {
        // Guard 1: multi-slot booking cannot be cancelled individually
        if ($booking->isMultiSlot()) {
            $msg = $actor === 'owner'
                ? 'Booking ini merupakan bagian dari multi-slot booking dan tidak dapat dibatalkan per slot secara individual.'
                : 'Booking multi-slot tidak dapat dibatalkan per slot secara individual. Silakan hubungi pengelola bisnis.';
            return ['success' => false, 'error' => $msg];
        }

        // Guard 2: check valid status transition
        if (!BookingState::canTransition($booking->status, BookingState::STATUS_CANCELLED)) {
            $msg = $actor === 'owner'
                ? "Status tidak dapat diubah dari '{$booking->status}' ke 'cancelled'."
                : "Booking ini tidak dapat dibatalkan (status: {$booking->status}).";
            return ['success' => false, 'error' => $msg];
        }

        // Guard 3: Customer policy check (minimal X hours before slot)
        if ($actor === 'customer' && !BookingRules::canCancel($booking)) {
            $cancelHours = $booking->tenant?->cancel_before_hours ?? 24;
            return [
                'success' => false,
                'error'   => "Booking tidak dapat dibatalkan. Pembatalan hanya diizinkan minimal {$cancelHours} jam sebelum jadwal.",
            ];
        }

        try {
            DB::transaction(function () use ($booking, $actor, $reason) {
                // 1. Update status
                $booking->update(['status' => BookingState::STATUS_CANCELLED]);

                // 2. Create refund record if customer cancelled and payment was successful
                if ($actor === 'customer' && $booking->payment && $booking->payment->status === 'sukses') {
                    Refund::create([
                        'booking_id' => $booking->id,
                        'payment_id' => $booking->payment->id,
                        'jumlah'     => $booking->payment->jumlah,
                        'status'     => 'pending',
                        'catatan'    => $reason ?? 'Refund otomatis dari pembatalan booking oleh customer.',
                    ]);
                }

                // 3. Audit log
                BookingLog::record(
                    $booking->id,
                    'cancelled',
                    $reason ?? ($actor === 'owner' ? 'Booking dibatalkan oleh owner.' : 'Booking dibatalkan oleh customer.'),
                    [
                        'actor'        => $actor,
                        'cancelled_at' => now()->toIso8601String(),
                    ]
                );

                // 4. Clear availability cache
                $dateStr = $booking->tanggalbooking instanceof Carbon
                    ? $booking->tanggalbooking->toDateString()
                    : Carbon::parse($booking->tanggalbooking)->toDateString();

                $this->clearBookingAvailabilityCache(
                    (int) $booking->idtenant,
                    (int) $booking->idlayanan,
                    $dateStr
                );
            });

            // 5. Send cancellation email to customer
            if ($booking->email) {
                try {
                    $booking->refresh()->load(['tenant.user', 'layanan', 'payment', 'refund']);
                    Mail::to($booking->email)->send(new BookingCancelledMail($booking));
                } catch (\Throwable $e) {
                    Log::warning('CancelBooking: Failed to send cancellation email', ['error' => $e->getMessage()]);
                }
            }

            // 6. Notify owner (only if cancelled by customer)
            if ($actor !== 'owner') {
                try {
                    $owner = $booking->tenant?->user;
                    if ($owner) {
                        $owner->notify(new BookingStatusChangedOwnerNotification(
                            $booking,
                            BookingState::STATUS_CANCELLED,
                            ['updated_by' => $actor]
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::warning('CancelBooking: Failed to notify owner', ['error' => $e->getMessage()]);
                }
            }

            return ['success' => true, 'booking' => $booking];

        } catch (\Throwable $e) {
            Log::error('CancelBooking failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => 'Terjadi kesalahan saat membatalkan booking. Silakan coba lagi.',
            ];
        }
    }
}
