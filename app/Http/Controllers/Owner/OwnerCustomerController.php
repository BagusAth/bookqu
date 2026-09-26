<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Customer\GetCustomerDetail;
use App\Actions\Customer\SaveCustomerNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SaveCustomerNoteRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
    public function index(Request $request): View
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search   = trim((string) $request->input('search', ''));
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
            $spendingRows = DB::table('bookings')
                ->join('payments', function ($join) {
                    $join->on('bookings.idpayment', '=', 'payments.id')
                         ->orOn('payments.idbooking', '=', 'bookings.id');
                })
                ->select([
                    DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(bookings.email), ''), NULLIF(TRIM(bookings.nomorhp), ''), CONCAT('guest-', bookings.id)))) AS identifier"),
                    'payments.id AS payment_id',
                    'payments.jumlah AS payment_amount',
                ])
                ->where('payments.idtenant', $idtenant)
                ->where('payments.tipe', 'booking')
                ->where('payments.status', 'sukses')
                ->whereIn(
                    DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(bookings.email), ''), NULLIF(TRIM(bookings.nomorhp), ''), CONCAT('guest-', bookings.id))))"),
                    $identifiers
                )
                ->distinct()
                ->get()
                ->groupBy('identifier');

            foreach ($spendingRows as $identifier => $rows) {
                $spendingMap[$identifier] = (float) $rows->unique('payment_id')->sum('payment_amount');
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

        return view('owner.customers', compact(
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
    public function show(Request $request, GetCustomerDetail $getCustomerDetail): JsonResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $identifier = (string) $request->input('identifier', '');
        $data = $getCustomerDetail->execute($tenant, $identifier);

        return response()->json($data);
    }

    /**
     * Save or update an internal owner note for a customer.
     * Tenant-scoped — IDOR protected before write.
     */
    public function saveNote(SaveCustomerNoteRequest $request, SaveCustomerNote $saveCustomerNote): JsonResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validated();
        $saveCustomerNote->execute(
            $tenant,
            $validated['customer_identifier'],
            $validated['notes'] ?? null
        );

        return response()->json(['success' => true, 'message' => 'Catatan berhasil disimpan.']);
    }
}
