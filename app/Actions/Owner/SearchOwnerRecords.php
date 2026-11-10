<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Domain\Customer\CustomerIdentity;
use App\Models\Booking;
use App\Models\Tenant;
use App\Support\StatusBadge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * Global owner search (BQ-128, FR-SHELL-002): booking code, customer name or phone, within the
 * current business only (INV-TEN-01). Results open the booking or customer panel once their
 * endpoints exist (BQ-135, BQ-137); until then they link to the Booking and Pelanggan lists.
 */
final class SearchOwnerRecords
{
    private const LIMIT = 6;

    public const MIN_LENGTH = 2;

    /**
     * @return array{bookings: list<array<string, mixed>>, customers: list<array<string, mixed>>}
     */
    public function execute(Tenant $tenant, string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < self::MIN_LENGTH) {
            return ['bookings' => [], 'customers' => []];
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%';
        $digits = preg_replace('/\D+/', '', $query) ?? '';

        // scope-bypass: filtered by the server-resolved tenant of the signed-in member
        $matches = Booking::withoutGlobalScopes()
            ->with(['layanan:id,namalayanan'])
            ->where('idtenant', $tenant->id)
            ->where(function ($builder) use ($like, $digits): void {
                $builder->where('booking_code', 'like', $like)
                    ->orWhere('namapelanggan', 'like', $like);
                if (strlen($digits) >= 4) {
                    $builder->orWhere('nomorhp', 'like', '%'.$digits.'%');
                }
            })
            ->orderByDesc('tanggalbooking')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'idlayanan', 'booking_code', 'namapelanggan', 'email', 'nomorhp', 'tanggalbooking', 'jam', 'status']);

        $bookings = $matches->take(self::LIMIT)->map(fn (Booking $booking): array => [
            'code' => (string) $booking->booking_code,
            'customer' => (string) $booking->namapelanggan,
            'service' => (string) ($booking->layanan->namalayanan ?? ''),
            'date' => Carbon::parse((string) $booking->tanggalbooking)->translatedFormat('j M Y'),
            'time' => substr((string) $booking->jam, 0, 5),
            'status' => array_combine(['label', 'tone'], StatusBadge::for('booking', (string) $booking->status)),
            'panel_url' => Route::has('owner.bookings.panel') ? route('owner.bookings.panel', $booking->id) : null,
            'href' => route('owner.bookings', ['katakunci' => $booking->booking_code]),
        ])->values()->all();

        $customers = $matches
            ->filter(fn (Booking $booking): bool => stripos((string) $booking->namapelanggan, $query) !== false
                || (strlen($digits) >= 4 && str_contains(preg_replace('/\D+/', '', (string) $booking->nomorhp) ?? '', $digits)))
            ->unique(fn (Booking $booking): string => CustomerIdentity::forBooking($booking))
            ->take(self::LIMIT)
            ->map(fn (Booking $booking): array => [
                'name' => (string) $booking->namapelanggan,
                'phone' => (string) $booking->nomorhp,
                'panel_url' => route('owner.customers.panel', ['identifier' => CustomerIdentity::forBooking($booking)]),
                'href' => route('owner.customers', ['search' => $booking->namapelanggan]),
            ])->values()->all();

        return ['bookings' => $bookings, 'customers' => $customers];
    }
}
