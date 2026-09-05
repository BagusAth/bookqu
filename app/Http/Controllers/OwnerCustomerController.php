<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CustomerNote;
use App\Models\Payment;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerCustomerController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Customer list page with server-side search & pagination.
     *
     * Customers are derived from bookings — there is no separate customer table.
     * A "customer" is uniquely identified per tenant by their normalized email
     * (or phone number when email is absent).
     */
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search   = trim($request->input('search', ''));
        $perPage  = 20;
        $idtenant = $tenant->id;

        // ── Aggregate customer data directly in the database ──
        $customerQuery = DB::table('bookings')
            ->select([
                DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id)))) AS identifier"),
                DB::raw("MAX(namapelanggan) AS name"),
                DB::raw("MAX(email) AS email"),
                DB::raw("MAX(nomorhp) AS phone"),
                DB::raw("COUNT(*) AS total_bookings"),
                DB::raw("MAX(tanggalbooking) AS last_booking_date"),
                DB::raw("MIN(created_at) AS first_seen"),
            ])
            ->where('idtenant', $idtenant)
            ->groupBy(DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id))))"));

        // Apply server-side search before aggregation — filter on raw bookings rows
        if ($search !== '') {
            $like = "%{$search}%";
            $customerQuery->where(function ($q) use ($like) {
                $q->where('namapelanggan', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('nomorhp', 'like', $like);
            });
        }

        // Paginate
        $customers = $customerQuery
            ->orderByDesc('last_booking_date')
            ->paginate($perPage)
            ->withQueryString();

        // ── Enrich each customer record with spending & upcoming booking ──
        // Batch-fetch for current page only — avoids N+1
        $identifiers = collect($customers->items())->pluck('identifier')->all();

        // Total spending from payments (correct source of truth: status='sukses', tipe='booking')
        $spendingMap = [];
        if (!empty($identifiers)) {
            $spending = DB::table('payments')
                ->join('bookings', 'payments.idbooking', '=', 'bookings.id')
                ->select([
                    DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(bookings.email), ''), NULLIF(TRIM(bookings.nomorhp), ''), CONCAT('guest-', bookings.id)))) AS identifier"),
                    DB::raw("SUM(payments.jumlah) AS total_spent"),
                ])
                ->where('payments.idtenant', $idtenant)
                ->where('payments.tipe', 'booking')
                ->where('payments.status', 'sukses')
                ->whereIn(
                    DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(bookings.email), ''), NULLIF(TRIM(bookings.nomorhp), ''), CONCAT('guest-', bookings.id))))"),
                    $identifiers
                )
                ->groupBy(DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(bookings.email), ''), NULLIF(TRIM(bookings.nomorhp), ''), CONCAT('guest-', bookings.id))))"))
                ->get();

            foreach ($spending as $row) {
                $spendingMap[$row->identifier] = (float) $row->total_spent;
            }
        }

        // Upcoming bookings for current page
        $today       = Carbon::today()->toDateString();
        $upcomingMap = [];
        if (!empty($identifiers)) {
            $upcomingRows = DB::table('bookings')
                ->select([
                    DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id)))) AS identifier"),
                    DB::raw("MIN(tanggalbooking) AS upcoming_date"),
                    'jam AS upcoming_time',
                ])
                ->where('idtenant', $idtenant)
                ->where('tanggalbooking', '>=', $today)
                ->whereIn('status', ['paid', 'pending'])
                ->whereIn(
                    DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id))))"),
                    $identifiers
                )
                ->groupBy(DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id))))"), 'jam')
                ->orderBy('upcoming_date')
                ->get()
                ->groupBy('identifier')
                ->map->first();

            foreach ($upcomingRows as $identifier => $row) {
                $upcomingMap[$identifier] = [
                    'date' => Carbon::parse($row->upcoming_date)->format('d M Y'),
                    'time' => substr($row->upcoming_time, 0, 5),
                ];
            }
        }

        // Attach enriched data to each customer on the current page
        $customers->getCollection()->transform(function ($c) use ($spendingMap, $upcomingMap) {
            $totalSpent          = $spendingMap[$c->identifier] ?? 0;
            $upcoming            = $upcomingMap[$c->identifier] ?? null;
            $c->total_spent      = $totalSpent;
            $c->formatted_spent  = 'Rp ' . number_format($totalSpent, 0, ',', '.');
            $c->upcoming_booking = $upcoming
                ? $upcoming['date'] . ' ' . $upcoming['time']
                : null;
            $c->last_booking     = $c->last_booking_date
                ? Carbon::parse($c->last_booking_date)->format('d M Y')
                : '-';
            $c->first_seen       = $c->first_seen
                ? Carbon::parse($c->first_seen)->format('d M Y')
                : '-';
            return $c;
        });

        // ── Summary stats — always full tenant scope, ignoring search ──
        $totalCustomers = (int) DB::table('bookings')
            ->where('idtenant', $idtenant)
            ->select(DB::raw("COUNT(DISTINCT LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id))))) AS cnt"))
            ->value('cnt');

        $totalSpentAll    = (float) Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->sum('jumlah');

        $totalBookingsAll = Booking::where('idtenant', $idtenant)->count();

        return view('owner.owner-customers', compact(
            'tenant',
            'customers',
            'totalCustomers',
            'totalSpentAll',
            'totalBookingsAll',
            'search',
        ));
    }

    /**
     * Customer detail — JSON endpoint for the drawer panel.
     * IDOR-protected: identifier must belong to this tenant's bookings.
     */
    public function show(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $identifier = strtolower(trim($request->input('identifier', '')));
        if ($identifier === '') {
            abort(400, 'Customer identifier diperlukan.');
        }

        $idtenant = $tenant->id;

        // ── IDOR guard ──
        $exists = DB::table('bookings')
            ->where('idtenant', $idtenant)
            ->where(function ($q) use ($identifier) {
                $q->whereRaw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id)))) = ?", [$identifier]);
            })
            ->exists();

        if (!$exists) {
            abort(404, 'Customer tidak ditemukan.');
        }

        // ── Load full booking history for this identifier ──
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

        // ── Spending from payment state machine ──
        $paidBookingIds = $bookings
            ->filter(fn($b) => in_array($b->status, ['paid', 'completed']))
            ->pluck('id');

        $totalSpent = (float) Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->whereIn('idbooking', $paidBookingIds)
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

        // ── Payment history (tenant-scoped) ──
        $payments = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->whereIn('idbooking', $bookings->pluck('id'))
            ->with('booking.layanan')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'order_id'     => $p->order_id ?? $p->external_id ?? ('PAY-' . $p->id),
                'booking_code' => $p->booking?->booking_code ?? '-',
                'service'      => $p->booking?->layanan?->namalayanan ?? '-',
                'jumlah'       => 'Rp ' . number_format((float) $p->jumlah, 0, ',', '.'),
                'status'       => $p->status,
                'date'         => $p->created_at?->format('d M Y'),
            ]);

        $bookingHistory = $bookings->map(fn($b) => [
            'id'      => $b->id,
            'code'    => $b->booking_code ?? ('BKQ-' . $b->id),
            'service' => $b->layanan?->namalayanan ?? '-',
            'price'   => 'Rp ' . number_format((float) ($b->payment?->jumlah ?? $b->layanan?->harga ?? 0), 0, ',', '.'),
            'date'    => $b->tanggalbooking ? $b->tanggalbooking->format('d M Y') : '-',
            'time'    => $b->jam ? substr($b->jam, 0, 5) : '-',
            'status'  => $b->status,
            'notes'   => $b->catatan ?: null,
        ])->values();

        $paidCount = $paidBookingIds->count();

        return response()->json([
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
        ]);
    }

    /**
     * Save or update an internal owner note for a customer.
     * Tenant-scoped — IDOR protected before write.
     */
    public function saveNote(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'customer_identifier' => 'required|string|max:190',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $identifier = strtolower(trim($validated['customer_identifier']));

        // ── IDOR guard before write ──
        $exists = DB::table('bookings')
            ->where('idtenant', $tenant->id)
            ->where(function ($q) use ($identifier) {
                $q->whereRaw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id)))) = ?", [$identifier]);
            })
            ->exists();

        if (!$exists) {
            abort(403, 'Akses tidak diizinkan.');
        }

        CustomerNote::updateOrCreate(
            [
                'idtenant'            => $tenant->id,
                'customer_identifier' => $identifier,
            ],
            [
                'notes' => $validated['notes'] ?? '',
            ]
        );

        return response()->json(['success' => true, 'message' => 'Catatan berhasil disimpan.']);
    }
}
