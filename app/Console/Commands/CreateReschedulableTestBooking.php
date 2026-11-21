<?php

namespace App\Console\Commands;

use App\Actions\Booking\CreateWalkInBooking;
use App\Domain\Booking\BookingRules;
use App\Models\Booking;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateReschedulableTestBooking extends Command
{
    protected $signature = 'booking:test-reschedule
        {schedule : ID slot tersedia untuk booking uji}
        {--tenant=brama-digital : Slug tenant uji}';

    protected $description = 'Buat satu booking paid yang masih bisa dijadwal ulang (local)';

    public function handle(CreateWalkInBooking $createWalkIn, TenantContext $context): int
    {
        if (! app()->environment('local')) {
            $this->error('Perintah ini hanya tersedia di local.');

            return self::FAILURE;
        }

        $scheduleId = filter_var($this->argument('schedule'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $tenant = Tenant::query()->where('slug', $this->option('tenant'))->first();
        if ($scheduleId === false || $tenant === null) {
            $this->error('Periksa ID slot dan slug tenant uji.');

            return self::FAILURE;
        }

        $previousTenantId = $context->getTenantId();
        $context->setTenantId((int) $tenant->id);
        try {
            $booking = DB::transaction(function () use ($createWalkIn, $tenant, $scheduleId): Booking {
                $booking = $createWalkIn->execute($tenant, [
                    'idschedule' => $scheduleId,
                    'namapelanggan' => 'Uji Ubah Jadwal',
                    'nomorhp' => '080000000000',
                    'catatan' => 'Booking uji lokal untuk ubah jadwal.',
                ]);

                if ($booking === null || ! BookingRules::canReschedule($booking)) {
                    throw ValidationException::withMessages(['schedule' => 'Slot tidak memenuhi syarat booking uji.']);
                }

                return $booking;
            });
        } catch (ValidationException) {
            $this->error('Pilih slot tersedia milik tenant uji dengan paket aktif, kuota cukup, dan tenggat ubah jadwal yang masih terbuka.');

            return self::FAILURE;
        } finally {
            $context->setTenantId($previousTenantId);
        }

        $this->line((string) $booking->id);

        return self::SUCCESS;
    }
}
