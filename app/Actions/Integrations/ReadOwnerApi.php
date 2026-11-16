<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Domain\Customer\CustomerIdentity;
use App\Domain\Schedule\AvailabilityRules;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ReadOwnerApi
{
    /** @return LengthAwarePaginator<int, mixed> */
    public function execute(Tenant $tenant, string $resource): LengthAwarePaginator
    {
        $tenantId = (int) $tenant->id;

        return match ($resource) {
            'services' => Service::where('idtenant', $tenantId)->orderBy('id')
                ->paginate(50, ['id', 'namalayanan', 'durasi', 'harga', 'is_active', 'kapasitas']),
            'bookings' => Booking::where('idtenant', $tenantId)->orderByDesc('id')
                ->paginate(50, ['id', 'booking_code', 'idlayanan', 'idschedule', 'tanggalbooking', 'jam', 'status', 'peserta', 'namapelanggan']),
            'schedules' => Schedule::where('idtenant', $tenantId)->with('layanan')->orderBy('tanggal')->orderBy('jam_mulai')->orderBy('id')
                ->paginate(50)->through(fn (Schedule $slot): array => [
                    'id' => $slot->id,
                    'idlayanan' => $slot->idlayanan,
                    'tanggal' => Carbon::parse($slot->tanggal)->toDateString(),
                    'jam_mulai' => $slot->jam_mulai,
                    'jam_selesai' => $slot->jam_selesai,
                    'available' => AvailabilityRules::refusal($slot, tenantId: $tenantId) === null,
                ]),
            'customers' => $this->customers($tenantId),
            default => abort(404),
        };
    }

    /** @return LengthAwarePaginator<int, mixed> */
    private function customers(int $tenantId): LengthAwarePaginator
    {
        $bookings = DB::table('bookings')->where('idtenant', $tenantId)
            ->select('namapelanggan')->selectRaw(CustomerIdentity::sql().' AS identifier');

        return DB::query()->fromSub($bookings, 'customer_bookings')
            ->selectRaw('identifier, MAX(namapelanggan) AS name')->groupBy('identifier')
            ->orderBy('identifier')->paginate(50);
    }
}
