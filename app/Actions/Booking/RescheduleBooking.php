<?php

namespace App\Actions\Booking;

use App\Actions\CustomerAccess\RequestManagementDelivery;
use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Actions\Schedule\ClaimSlots;
use App\Actions\Schedule\SlotClaimRefused;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Domain\Schedule\SlotRefusal;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RescheduleBooking
{
    use ClearsBookingCache;

    /**
     * Reschedule a booking to a new schedule slot.
     *
     * @param  string  $actor  'customer'|'owner'
     * @param  string|null  $targetDate  Optional date string (required for customer flow validation)
     * @return array{success: bool, error?: string, error_code?: string, booking?: Booking}
     */
    public function execute(
        Booking $booking,
        int $newScheduleId,
        string $actor = 'customer',
        ?string $reason = null,
        ?string $targetDate = null,
        ?AccessContext $access = null
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
            if (! BookingRules::canReschedule($booking)) {
                return ['success' => false, 'error' => 'Waktu reschedule telah habis.'];
            }
        } else {
            if ($booking->status === BookingState::STATUS_CANCELLED) {
                return ['success' => false, 'error' => 'Booking dengan status "'.$booking->status.'" tidak dapat dijadwalkan ulang.'];
            }
        }

        $oldDate = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $oldTime = substr($booking->jam, 0, 5);
        $oldScheduleId = (int) $booking->idschedule;

        if ($oldScheduleId === $newScheduleId) {
            return ['success' => false, 'error' => 'Slot jadwal yang dipilih sama dengan jadwal booking saat ini.'];
        }

        $tenant = $booking->tenant;
        $service = $booking->layanan;

        if (! $tenant instanceof Tenant || ! $service) {
            return ['success' => false, 'error' => 'Data tenant atau layanan tidak valid.'];
        }

        try {
            DB::transaction(function () use ($booking, $newScheduleId, $tenant, $service, $oldDate, $oldTime, $oldScheduleId, $actor, $reason, $targetDate, $access) {
                ClaimSlots::lockTenant((int) $booking->idtenant);
                if ($access !== null) {
                    app(ResolveCustomerAccess::class)->underLock($access, AccessOperation::Reschedule, $booking);
                    $booking->refresh();
                    abort_unless(! $booking->isMultiSlot() && BookingRules::canReschedule($booking), 422);
                }
                $wib = 'Asia/Jakarta';
                $nowWib = Carbon::now($wib);

                // Claim the new slot with the rule every booking path uses (ADR-0005)
                try {
                    $schedule = app(ClaimSlots::class)->execute(
                        $tenant,
                        (int) $service->id,
                        [$newScheduleId],
                        $actor === 'customer' && $targetDate ? Carbon::parse($targetDate)->toDateString() : null,
                        (int) $booking->id,
                    )->first();
                } catch (SlotClaimRefused $refused) {
                    throw new Exception($refused->refusal === SlotRefusal::Occupied ? 'SLOT_TAKEN' : 'SLOT_NOT_FOUND');
                }

                // Reload the source booking after synchronization; it may have changed meanwhile
                $booking->refresh();
                if ($booking->status !== BookingState::STATUS_PAID && $booking->status !== BookingState::STATUS_PENDING) {
                    throw new Exception('SLOT_NOT_FOUND');
                }

                $newDate = $schedule->tanggal instanceof Carbon
                    ? $schedule->tanggal->toDateString()
                    : Carbon::parse($schedule->tanggal)->toDateString();
                $newTime = substr($schedule->jam_mulai, 0, 5);

                // Update booking
                $newJam = $actor === 'customer' ? substr($schedule->jam_mulai, 0, 5) : $schedule->jam_mulai;
                $booking->update([
                    'idschedule' => $newScheduleId,
                    'tanggalbooking' => $newDate,
                    'jam' => $newJam,
                    'rescheduled_from_date' => $oldDate,
                    'rescheduled_from_time' => $oldTime,
                    'rescheduled_from_schedule' => $oldScheduleId,
                ]);

                // Record BookingLog
                $logMessage = $actor === 'owner'
                    ? 'Jadwal diubah oleh Owner (Walk-in / On-site): '.($reason ?? 'Permintaan langsung customer walk-in di studio')
                    : 'Jadwal booking diubah oleh customer.';

                BookingLog::record(
                    $booking->id,
                    'rescheduled',
                    $logMessage,
                    [
                        'from_date' => $oldDate,
                        'from_time' => $oldTime,
                        'from_schedule' => $oldScheduleId,
                        'to_date' => $newDate,
                        'to_time' => $schedule->jam_mulai,
                        'to_schedule' => $newScheduleId,
                        'actor' => $actor === 'owner' ? (auth()->user()->namalengkap ?? 'Owner') : 'customer',
                        'role' => $actor,
                    ]
                );

                app(RequestManagementDelivery::class)->execute($booking, 'rescheduled', $access);

                // Clear caches for old and new dates
                $this->clearBookingAvailabilityCache((int) $tenant->id, (int) $booking->idlayanan, $oldDate);
                $this->clearBookingAvailabilityCache((int) $tenant->id, (int) $booking->idlayanan, $newDate);
            });

            try {
                $booking->loadMissing(['tenant.user', 'layanan']);
                $owner = $booking->tenant?->user ?? ($tenant->iduser ? User::find($tenant->iduser) : null);
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
                            'exception_type' => $mailException::class, 'booking_id' => $booking->id,
                        ]);
                        $owner->notifyNow($notification, ['database']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('RescheduleBooking: Failed to notify owner', ['exception_type' => $e::class, 'booking_id' => $booking->id]);
            }

            return ['success' => true, 'booking' => $booking];

        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($msg === 'SLOT_NOT_FOUND' || $msg === 'SLOT_TAKEN') {
                $userMsg = $actor === 'owner'
                    ? ($msg === 'SLOT_NOT_FOUND' ? 'Slot jadwal baru tidak ditemukan atau statusnya tidak tersedia.' : 'Slot jadwal yang dipilih sudah terisi oleh pelanggan lain.')
                    : 'Slot waktu yang dipilih tidak tersedia. Silakan pilih waktu lain.';

                return ['success' => false, 'error' => $userMsg, 'error_code' => $msg];
            }

            Log::error('RescheduleBooking: Exception', [
                'booking_id' => $booking->id,
                'exception_type' => $e::class, 'exception_file' => $e->getFile(), 'exception_line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan. Silakan coba lagi.',
            ];
        }
    }
}
