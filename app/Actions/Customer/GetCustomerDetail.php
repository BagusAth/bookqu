<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Booking;
use App\Models\CustomerNote;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetCustomerDetail
{
    /**
     * Retrieve detailed customer profile, metrics, booking history, and payments.
     * IDOR-protected: identifier must belong to this tenant's bookings.
     *
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, string $customerIdentifier): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $identifier = strtolower(trim($customerIdentifier));
        if ($identifier === '') {
            abort(400, 'Customer identifier diperlukan.');
        }

        $idtenant = $tenant->id;

        // IDOR guard
        $exists = DB::table('bookings')
            ->where('idtenant', $idtenant)
            ->where(function ($q) use ($identifier) {
                $q->whereRaw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id)))) = ?", [$identifier]);
            })
            ->exists();

        if (!$exists) {
            abort(404, 'Customer tidak ditemukan.');
        }

        // Load full booking history for this identifier
        $bookings = Booking::where('idtenant', $idtenant)
            ->where(function ($q) use ($identifier) {
                $q->whereRaw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id)))) = ?", [$identifier]);
            })
            ->with(['layanan', 'payment'])
            ->orderByDesc('tanggalbooking')
            ->orderByDesc('jam')
            ->get();

        if ($bookings->isEmpty()) {
            abort(404, 'Customer tidak ditemukan.');
        }

        $first = $bookings->first();

        // Spending from payment state machine
        $paidBookingIds = $bookings
            ->filter(fn($b) => in_array($b->status, ['paid', 'completed']))
            ->pluck('id');

        $paidPaymentIds = $bookings
            ->filter(fn($b) => in_array($b->status, ['paid', 'completed']))
            ->pluck('idpayment')
            ->filter()
            ->unique();

        // Also include payments referenced by idbooking for legacy
        $legacyPaidPaymentIds = Payment::where('idtenant', $idtenant)
            ->whereIn('idbooking', $paidBookingIds)
            ->pluck('id');

        $allPaidPaymentIds = $paidPaymentIds->concat($legacyPaidPaymentIds)->unique()->values();

        $totalSpent = (float) Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->whereIn('id', $allPaidPaymentIds)
            ->sum('jumlah');

        $today           = Carbon::today()->toDateString();
        $lastBooking     = $bookings->first();
        $upcomingBooking = $bookings
            ->filter(fn($b) => $b->tanggalbooking && $b->tanggalbooking->toDateString() >= $today && in_array($b->status, ['paid', 'pending']))
            ->sortBy('tanggalbooking')
            ->first();

        $servicesUsed = $bookings
            ->map(fn($b) => $b->layanan?->namalayanan)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $note = CustomerNote::where('idtenant', $idtenant)
            ->where('customer_identifier', $identifier)
            ->first();

        // Payment history (tenant-scoped)
        $allPaymentIds = $bookings->pluck('idpayment')
            ->concat(Payment::where('idtenant', $idtenant)->whereIn('idbooking', $bookings->pluck('id'))->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $payments = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->whereIn('id', $allPaymentIds)
            ->with(['bookings.layanan', 'booking.layanan'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {
                $groupBookings = $p->bookings->isNotEmpty() ? $p->bookings : ($p->booking ? collect([$p->booking]) : collect());
                $serviceNames = $groupBookings->map(fn($b) => $b->layanan?->namalayanan)->filter()->unique()->implode(', ');
                $slots = $groupBookings->sortBy('jam')->map(fn($b) => substr($b->jam, 0, 5))->implode(', ');

                return [
                    'order_id'     => $p->order_id ?? $p->external_id ?? ('PAY-' . $p->id),
                    'booking_code' => $groupBookings->pluck('booking_code')->filter()->implode(', ') ?: '-',
                    'service'      => $serviceNames ?: '-',
                    'slots'        => $slots,
                    'jumlah'       => 'Rp ' . number_format((float) $p->jumlah, 0, ',', '.'),
                    'status'       => $p->status,
                    'date'         => $p->created_at?->format('d M Y'),
                ];
            });

        $bookingHistory = $bookings->map(fn($b) => [
            'id'      => $b->id,
            'code'    => $b->booking_code ?? ('BKQ-' . $b->id),
            'service' => $b->layanan?->namalayanan ?? '-',
            'price'   => 'Rp ' . number_format((float) ($b->schedule?->harga_override ?? $b->layanan?->harga ?? 0), 0, ',', '.'),
            'date'    => $b->tanggalbooking ? $b->tanggalbooking->format('d M Y') : '-',
            'time'    => $b->jam ? substr($b->jam, 0, 5) : '-',
            'status'  => $b->status,
            'notes'   => $b->catatan ?: null,
        ])->values();

        $paidCount = $paidBookingIds->count();

        return [
            'identifier'       => $identifier,
            'name'             => $first->namapelanggan ?: 'Customer',
            'email'            => $first->email ?: '-',
            'phone'            => $first->nomorhp ?: '-',
            'first_seen'       => $bookings->sortBy('created_at')->first()?->created_at?->format('d M Y') ?? '-',
            'total_bookings'   => $bookings->count(),
            'total_spent'      => $totalSpent,
            'formatted_spent'  => 'Rp ' . number_format($totalSpent, 0, ',', '.'),
            'avg_transaction'  => $paidCount > 0
                ? 'Rp ' . number_format($totalSpent / $paidCount, 0, ',', '.')
                : 'Rp 0',
            'last_booking'     => $lastBooking?->tanggalbooking ? $lastBooking->tanggalbooking->format('d M Y') : '-',
            'upcoming_booking' => $upcomingBooking
                ? $upcomingBooking->tanggalbooking->format('d M Y') . ' ' . substr($upcomingBooking->jam, 0, 5)
                : null,
            'services_used'    => $servicesUsed,
            'notes'            => $note?->notes ?? '',
            'bookings'         => $bookingHistory,
            'payments'         => $payments,
        ];
    }
}
