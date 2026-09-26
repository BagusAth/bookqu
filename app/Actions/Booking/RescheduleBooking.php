<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Mail\BookingRescheduledMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RescheduleBooking
{
    use ClearsBookingCache;

    /**
     * Reschedule a booking to a new schedule slot.
     *
     * @param Booking $booking
     * @param int $newScheduleId
     * @param string $actor 'customer'|'owner'
     * @param string|null $reason
     * @param string|null $targetDate Optional date string (required for customer flow validation)
     * @return array{success: bool, error?: string, error_code?: string, booking?: Booking}
     */
    public function execute(
        Booking $booking,
        int $newScheduleId,
        string $actor = 'customer',
        ?string $reason = null,
        ?string $targetDate = null
    ): array {
        // Guard 1: multi-slot booking cannot be rescheduled per slot
        if ($booking->isMultiSlot()) {
            $msg = $actor === 'owner'
                ? 'Booking multi-slot tidak dapat dijadwalkan ulang per slot secara individual.'
                : 'Booking multi-slot tidak dapat dibatalkan atau dijadwalkan ulang per slot secara individual. Silakan hubungi pengelola bisnis.';
            return ['success' => false, 'error' => $msg];
        }

        // Guard 2: status check
        if ($actor === 'customer') {
            if ($booking->status !== BookingState::STATUS_PAID) {
                return ['success' => false, 'error' => 'Booking tidak dapat dijadwalkan ulang.'];
            }
            if (!BookingRules::canReschedule($booking)) {
                return ['success' => false, 'error' => 'Waktu reschedule telah habis.'];
            }
        } else {
            if (in_array($booking->status, [BookingState::STATUS_CANCELLED, BookingState::STATUS_REFUNDED], true)) {
                return ['success' => false, 'error' => 'Booking dengan status "' . $booking->status . '" tidak dapat dijadwalkan ulang.'];
            }
        }

        $oldDate       = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $oldTime       = substr($booking->jam, 0, 5);
        $oldScheduleId = (int) $booking->idschedule;

        if ($oldScheduleId === $newScheduleId) {
            return ['success' => false, 'error' => 'Slot jadwal yang dipilih sama dengan jadwal booking saat ini.'];
        }

        $tenant  = $booking->tenant;
        $service = $booking->layanan;

        if (!$tenant || !$service) {
            return ['success' => false, 'error' => 'Data tenant atau layanan tidak valid.'];
        }

        try {
            DB::transaction(function () use ($booking, $newScheduleId, $tenant, $service, $oldDate, $oldTime, $oldScheduleId, $actor, $reason, $targetDate) {
                $wib = 'Asia/Jakarta';
                $nowWib = Carbon::now($wib);

                // Lock and validate new schedule slot
                $scheduleQuery = Schedule::where('id', $newScheduleId)
                    ->where('idtenant', $tenant->id)
                    ->where('status', 'tersedia')
                    ->lockForUpdate();

                if ($actor === 'customer') {
                    $scheduleQuery->where('idlayanan', $service->id);
                    if ($targetDate) {
                        $scheduleQuery->whereDate('tanggal', $targetDate);
                    }
                }

                $schedule = $scheduleQuery->first();

                if (!$schedule) {
                    throw new Exception('SLOT_NOT_FOUND');
                }

                $newDate = $schedule->tanggal instanceof Carbon
                    ? $schedule->tanggal->toDateString()
                    : Carbon::parse($schedule->tanggal)->toDateString();
                $newTime = substr($schedule->jam_mulai, 0, 5);

                $slotDateTime = Carbon::parse($newDate . ' ' . $schedule->jam_mulai, $wib);
                if ($slotDateTime->lessThanOrEqualTo($nowWib)) {
                    throw new Exception('SLOT_NOT_FOUND');
                }

                // Check conflict with other active bookings (excluding this booking)
                $slotTaken = BookingRules::isSlotOccupied($newScheduleId, $booking->id);

                if ($slotTaken) {
                    throw new Exception('SLOT_TAKEN');
                }

                // Update booking
                $newJam = $actor === 'customer' ? substr($schedule->jam_mulai, 0, 5) : $schedule->jam_mulai;
                $booking->update([
                    'idschedule'                => $newScheduleId,
                    'tanggalbooking'            => $newDate,
                    'jam'                       => $newJam,
                    'rescheduled_from_date'     => $oldDate,
                    'rescheduled_from_time'     => $oldTime,
                    'rescheduled_from_schedule' => $oldScheduleId,
                ]);

                // Record BookingLog
                $logMessage = $actor === 'owner'
                    ? 'Jadwal diubah oleh Owner (Walk-in / On-site): ' . ($reason ?? 'Permintaan langsung customer walk-in di studio')
                    : 'Jadwal booking diubah oleh customer.';

                BookingLog::record(
                    $booking->id,
                    'rescheduled',
                    $logMessage,
                    [
                        'from_date'     => $oldDate,
                        'from_time'     => $oldTime,
                        'from_schedule' => $oldScheduleId,
                        'to_date'       => $newDate,
                        'to_time'       => $schedule->jam_mulai,
                        'to_schedule'   => $newScheduleId,
                        'actor'         => $actor === 'owner' ? (auth()->user()->namalengkap ?? 'Owner') : 'customer',
                        'role'          => $actor,
                    ]
                );

                // Clear caches for old and new dates
                $this->clearBookingAvailabilityCache((int) $tenant->id, (int) $booking->idlayanan, $oldDate);
                $this->clearBookingAvailabilityCache((int) $tenant->id, (int) $booking->idlayanan, $newDate);
            });

            // Post-transaction side effects
            if ($booking->email) {
                try {
                    $booking->refresh()->load(['tenant.user', 'layanan']);
                    Mail::to($booking->email)->send(new BookingRescheduledMail($booking));
                } catch (\Throwable $e) {
                    Log::warning('RescheduleBooking: Failed to send reschedule email', ['error' => $e->getMessage()]);
                }
            }

            try {
                $booking->loadMissing(['tenant.user', 'layanan']);
                $owner = $booking->tenant?->user ?? ($tenant?->iduser ? User::find($tenant->iduser) : null);
                if ($owner) {
                    $notification = new BookingStatusChangedOwnerNotification(
                        $booking,
                        'rescheduled',
                        [
                            'old_date' => $oldDate,
                            'old_time' => $oldTime,
                            'new_date' => $booking->tanggalbooking instanceof Carbon ? $booking->tanggalbooking->toDateString() : (string) $booking->tanggalbooking,
                            'new_time' => $booking->jam,
                            'updated_by' => $actor,
                        ]
                    );

                    try {
                        $owner->notify($notification);
                    } catch (\Throwable $mailException) {
                        Log::warning('RescheduleBooking: Mail notification to owner failed, ensuring database notification', [
                            'error' => $mailException->getMessage(),
                        ]);
                        $owner->notifyNow($notification, ['database']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('RescheduleBooking: Failed to notify owner', ['error' => $e->getMessage()]);
            }

            return ['success' => true, 'booking' => $booking];

        } catch (Exception $e) {
            $msg = $e->getMessage();
            if ($msg === 'SLOT_NOT_FOUND' || $msg === 'SLOT_TAKEN') {
                $userMsg = $actor === 'owner'
                    ? ($msg === 'SLOT_NOT_FOUND' ? 'Slot jadwal baru tidak ditemukan atau statusnya tidak tersedia.' : 'Slot jadwal yang dipilih sudah terisi oleh pelanggan lain.')
                    : 'Slot waktu yang dipilih tidak tersedia. Silakan pilih waktu lain.';
                return ['success' => false, 'error' => $userMsg, 'error_code' => $msg];
            }

            Log::error('RescheduleBooking: Exception', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $actor === 'owner' ? $e->getMessage() : 'Terjadi kesalahan. Silakan coba lagi.',
            ];
        }
    }
}
