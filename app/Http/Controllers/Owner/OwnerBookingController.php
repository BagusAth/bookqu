<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Booking\CreateWalkInBooking;
use App\Actions\Booking\ExportOwnerBookings;
use App\Actions\Booking\GetBookingTimeRanges;
use App\Actions\Booking\GetOwnerBookingPanel;
use App\Actions\Booking\ListOwnerBookings;
use App\Actions\Booking\QuoteReschedule;
use App\Actions\Booking\RescheduleBooking;
use App\Actions\Booking\UpdateBookingStatus;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\Booking\BookingRules;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Team\TenantPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\QuoteBookingRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\TenantMember;
use App\Support\CustomerBookingRoutes;
use App\Traits\ClearsBookingCache;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerBookingController extends Controller
{
    use ClearsBookingCache;
    use ResolvesOwnerTenant;

    /**
     * Halaman daftar booking.
     */
    public function index(Request $request, ListOwnerBookings $list, GetBookingTimeRanges $ranges): View
    {
        $tenant = $this->resolveTenant();
        $filters = $this->listFilters($request);
        $tab = $this->selectedTab($filters);
        $member = $request->attributes->get('tenant_member');
        $can = fn (string $ability): bool => $member instanceof TenantMember && TenantPermission::allowsRole($member->role, $ability);
        $canViewRevenue = $can('revenue.view');
        $canReview = $can('bookings.cancel');
        $canWalkIn = $can('bookings.walkin') && EntitlementRules::hasEffectivePlan($tenant?->subscription);
        $base = $list->query($tenant, $filters);
        $tabs = $this->taskTabs();
        foreach ($tabs as $key => $label) {
            $tabs[$key] = $label.' ('.$list->tab(clone $base, $key, $canReview)->count().')';
        }
        $daftarbooking = $list->tab(clone $base, $tab, $canReview)
            ->orderBy('tanggalbooking', $filters['sort'] ?? 'desc')->orderBy('jam', $filters['sort'] ?? 'desc')
            ->orderBy('bookings.id')->paginate(15)->withQueryString();
        $timeRanges = $ranges->execute($daftarbooking->items())['sessionRanges'];
        $rows = $daftarbooking->getCollection()->mapWithKeys(function (Booking $booking) use ($list, $timeRanges): array {
            $payment = $booking->payment;
            $hold = $payment instanceof Payment ? BookingRules::onlinePaymentHoldEnd($booking, $payment) : null;
            $amount = $list->paidAmount($booking);

            return [$booking->id => [
                'time' => $timeRanges[$booking->id]->label(),
                'amount' => $amount === null ? '—' : 'Rp '.number_format($amount, 0, ',', '.'),
                'payment_status' => $payment instanceof Payment ? $payment->status : 'Belum tercatat',
                'waiting' => $hold ? 'Menunggu pembayaran online, berakhir '.$hold->setTimezone('Asia/Jakarta')->format('H:i').' WIB' : null,
            ]];
        });
        $services = $tenant ? Service::where('idtenant', $tenant->id)->orderBy('namalayanan')->get() : collect();
        $cancellationRequests = $canReview && $tenant !== null && in_array($tab, ['all', 'action'], true)
            ? DB::table('booking_cancellation_requests')->where('idtenant', $tenant->id)->where('status', 'pending')->orderBy('id')->paginate(10, pageName: 'permintaan')->withQueryString()
            : null;
        $exportUrl = $can('revenue.view') && EntitlementRules::canAccessFeature($tenant?->subscription, 'analytics.export')['allowed']
            ? route('owner.bookings.export', ['tab' => $tab] + $filters)
            : null;
        $storefrontUrl = $tenant?->slug && ! Booking::where('idtenant', $tenant->id)->exists()
            ? CustomerBookingRoutes::url('customer.booking.program', $tenant->slug)
            : null;
        $emptyTitle = match ($tab) {
            'action' => 'Tidak ada booking yang perlu ditangani',
            'upcoming' => 'Tidak ada booking mendatang',
            'pending' => 'Tidak ada booking menunggu pembayaran',
            'completed' => 'Tidak ada booking selesai',
            'cancelled' => 'Tidak ada booking dibatalkan',
            default => 'Tidak ada booking yang cocok',
        };

        return view('owner.bookings', compact(
            'tenant', 'daftarbooking', 'filters', 'tab', 'tabs', 'rows', 'services', 'canWalkIn',
            'cancellationRequests', 'exportUrl', 'storefrontUrl', 'emptyTitle', 'canViewRevenue',
        ));
    }

    public function panel(Request $request, int $booking, GetOwnerBookingPanel $panel): JsonResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404);
        $member = $request->attributes->get('tenant_member');
        abort_unless($member instanceof TenantMember, 403);

        return response()->json($panel->execute($tenant, $booking, $member));
    }

    public function export(Request $request, ListOwnerBookings $list, ExportOwnerBookings $export): StreamedResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404);
        $member = $request->attributes->get('tenant_member');
        abort_unless($member instanceof TenantMember && TenantPermission::allowsRole($member->role, 'revenue.view'), 403);
        abort_unless(EntitlementRules::canAccessFeature($tenant->subscription, 'analytics.export')['allowed'], 403);
        $filters = $this->listFilters($request);
        $query = $list->tab($list->query($tenant, $filters, streaming: true), $this->selectedTab($filters), TenantPermission::allowsRole($member->role, 'bookings.cancel'));

        return $export->execute($query->orderBy('tanggalbooking', $filters['sort'] ?? 'desc')
            ->orderBy('jam', $filters['sort'] ?? 'desc')->orderBy('bookings.id'));
    }

    /** @return array<string, mixed> */
    private function listFilters(Request $request): array
    {
        return Validator::make($request->query(), [
            'katakunci' => ['nullable', 'string', 'max:150'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'service' => ['nullable', 'integer', 'min:1'],
            'source' => ['nullable', 'in:online,walkin'],
            'sort' => ['nullable', 'in:asc,desc'],
            'tab' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ])->validate();
    }

    /** @param array<string, mixed> $filters */
    private function selectedTab(array $filters): string
    {
        $tab = $filters['tab'] ?? $filters['status'] ?? 'all';

        return isset($this->taskTabs()[$tab]) || in_array($tab, ['paid', 'today'], true) ? $tab : 'all';
    }

    /** @return array<string, string> */
    private function taskTabs(): array
    {
        return [
            'action' => 'Perlu tindakan', 'upcoming' => 'Mendatang', 'pending' => 'Menunggu bayar',
            'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'all' => 'Semua',
        ];
    }

    /**
     * FS-010: Update status booking oleh owner.
     * Transisi status yang diizinkan:
     *   paid    → completed | cancelled
     *   pending → paid | completed | cancelled
     */
    public function updateStatus(UpdateBookingStatusRequest $request, $booking, UpdateBookingStatus $updateBookingStatus)
    {
        $tenant = $this->resolveTenant();

        if (! $booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        // Pastikan booking milik tenant yang sedang login
        if (! $tenant || $booking->idtenant !== $tenant->id) {
            abort(404, 'Booking tidak ditemukan.');
        }

        $validated = $request->validated();
        $statusBaru = $validated['status'];

        $result = $updateBookingStatus->execute($booking, $statusBaru, 'owner', $validated['reason'] ?? null);

        if (! $result['success']) {
            return back()->withErrors([
                'error' => $result['error'],
            ]);
        }

        $label = match ($statusBaru) {
            'paid' => 'lunas / dikonfirmasi',
            'completed' => 'selesai',
            'cancelled' => 'dibatalkan',
            default => $statusBaru,
        };

        return back()->with('sukses', "Booking atas nama {$booking->namapelanggan} berhasil ditandai sebagai {$label}.");
    }

    /**
     * Walk-in booking creation by owner (directly from Calendar / Booking module)
     */
    public function walkinStore(Request $request, CreateWalkInBooking $createWalkInBooking)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $request->all();
        if (! isset($data['idschedule']) && isset($data['schedule_id'])) {
            $data['idschedule'] = $data['schedule_id'];
        }

        $validator = Validator::make($data, [
            'idschedule' => ['required', 'integer', Rule::exists('schedules', 'id')->where('idtenant', $tenant->id)],
            'namapelanggan' => ['required', 'string', 'max:150'],
            'nomorhp' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'metode' => ['nullable', 'string', 'in:cash,transfer,manual,qris'],
            // Room for the participants is decided by the claim (FR-BOOKING-021)
            'peserta' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ] + QuoteBookingRequest::addOnRules());

        $validated = $validator->validate();

        $booking = $createWalkInBooking->execute($tenant, $validated);

        if (! $booking) {
            return back()->withErrors(['error' => 'Slot waktu yang dipilih tidak tersedia atau sudah terisi.']);
        }

        return back()->with('sukses', "Walk-in booking atas nama {$booking->namapelanggan} berhasil dibuat!");
    }

    /**
     * Dapatkan daftar slot jadwal yang tersedia untuk reschedule booking ini oleh Owner.
     */
    public function getAvailableSlots(Request $request, $booking)
    {
        $tenant = $this->resolveTenant();
        if (! $booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        if (! $tenant || $booking->idtenant !== $tenant->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        $tanggal = $request->query('tanggal');
        if (! $tanggal) {
            $tanggal = Carbon::today()->toDateString();
        } else {
            $tanggal = Carbon::parse($tanggal)->toDateString();
        }

        $service = $booking->layanan;
        $slots = app(GetAvailableSchedules::class)
            ->getSlotsForReschedule($tenant, (int) $booking->idlayanan, $tanggal, (int) $booking->id);

        $slotPrices = $service instanceof Service
            ? app(QuoteReschedule::class)->execute($booking, $tenant, $service, $tanggal, $slots->pluck('schedule')) : [];

        $mappedSlots = $slots->map(function ($item) use ($slotPrices) {
            $slot = $item['schedule'];
            $price = $slotPrices[(int) $slot->id]['price'] ?? null;

            return [
                'id' => $slot->id,
                'jam_mulai' => $item['jam_mulai'],
                'jam_selesai' => $item['jam_selesai'],
                'formatted_time' => $item['jam_mulai'].' - '.$item['jam_selesai'],
                'price' => $price,
                'formatted_price' => $price === null ? '—' : 'Rp '.number_format($price, 0, ',', '.'),
                'price_difference' => $slotPrices[(int) $slot->id]['price_difference'] ?? null,
                'is_available' => $item['is_available'],
                'is_current' => $item['is_current'],
            ];
        });

        return response()->json([
            'success' => true,
            'tanggal' => $tanggal,
            'service_name' => $service?->namalayanan ?? 'Layanan',
            'current_slot_id' => $booking->idschedule,
            'slots' => $mappedSlots,
        ]);
    }

    /**
     * Reschedule / Ubah Jadwal Booking oleh Owner (Walk-in & On-site Direct Reschedule)
     * Tidak memerlukan token dari pelanggan.
     */
    public function reschedule(RescheduleBookingRequest $request, $booking, RescheduleBooking $rescheduleBooking)
    {
        $tenant = $this->resolveTenant();
        if (! $booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        if (! $tenant || $booking->idtenant !== $tenant->id) {
            abort(404, 'Booking tidak ditemukan.');
        }

        $validated = $request->validated();
        $newScheduleId = (int) $validated['schedule_id'];
        $alasan = $validated['alasan'] ?? 'Permintaan langsung customer walk-in di studio';

        $result = $rescheduleBooking->execute($booking, $newScheduleId, 'owner', $alasan, $validated['tanggal'] ?? null);

        if (! $result['success']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['error']], 422);
            }

            return back()->withErrors(['error' => $result['error']]);
        }

        $tglFormatted = Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y');
        $jamFormatted = substr($booking->jam, 0, 5).' WIB';
        $successMsg = "Jadwal booking #{$booking->booking_code} ({$booking->namapelanggan}) berhasil diubah ke {$tglFormatted} pukul {$jamFormatted}!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'booking' => $booking->fresh()->load('layanan'),
            ]);
        }

        return back()->with('sukses', $successMsg);
    }
}
