<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Actions\Owner\GetOwnerNavigation;
use App\Actions\Schedule\GetSlotRunway;
use App\Actions\Team\ResolveTenantMembership;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Booking\BookingTimeRange;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\SubscriptionState;
use App\Domain\Subscription\SubscriptionUsage;
use App\Domain\Team\TenantPermission;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use App\Support\CustomerBookingRoutes;
use App\Support\StatusBadge;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Beranda (owner-portal.md §8.1, FR-DASH-008): what the member must know and do today. Everything is
 * prepared here as plain values; the view and the live refresh render them as text only (BQ-089).
 * No charts or trends (AC-BRD-07): those belong to Laporan.
 */
class GetBeranda
{
    /** The status banner, in priority order; only the first that applies is shown (AC-BRD-01). */
    public const BANNERS = ['subscription-ended', 'trial-ending', 'slot-runway', 'payout-account', 'booking-limit'];

    public function __construct(
        private GetSlotRunway $getSlotRunway,
        private GetBookingTimeRanges $getBookingTimeRanges,
        private GetOwnerNavigation $getOwnerNavigation,
    ) {}

    /**
     * @return array{
     *     banner: array<string, mixed>|null,
     *     guide: array<string, mixed>|null,
     *     live: array<string, mixed>,
     *     link: array{url: string, share_text: string}|null,
     *     quickActions: list<array<string, string>>,
     * }
     */
    public function execute(Tenant $tenant, User $user): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $role = $this->role($user);
        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        $guide = $this->guide($tenant);
        $storefront = $tenant->slug ? CustomerBookingRoutes::url('customer.booking.program', $tenant->slug) : null;

        return [
            'banner' => $this->banner($tenant, $subscription, $role),
            'guide' => $guide,
            'live' => $guide === null ? $this->live($tenant, $user) : [],
            'link' => $storefront === null ? null : [
                'url' => $storefront,
                'share_text' => 'Booking '.($tenant->namabisnis ?: 'kami').' secara online: '.$storefront,
            ],
            'quickActions' => $this->quickActions($user, $role, $subscription),
        ];
    }

    /**
     * Sections 2–4, also served by the live refresh: figures, needs action, today and tomorrow.
     *
     * @return array{figures: list<array<string, string>>, actions: list<array<string, mixed>>, today: list<array<string, mixed>>, tomorrow: array<string, mixed>, next: array<string, string>|null}
     */
    public function live(Tenant $tenant, User $user): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $role = $this->role($user);
        $canViewRevenue = TenantPermission::allowsRole($role, 'revenue.view');
        $today = Carbon::today();
        $now = Carbon::now();

        $todayBookings = Booking::where('idtenant', $tenant->id)
            ->with(['layanan:id,namalayanan', 'payment:id,status'])
            ->whereDate('tanggalbooking', $today)
            ->where('status', '!=', BookingState::STATUS_CANCELLED)
            ->orderBy('jam')
            ->get();
        $ranges = $todayBookings->isEmpty() ? [] : $this->getBookingTimeRanges->execute($todayBookings)['sessionRanges'];

        return [
            'figures' => $this->figures($tenant, $todayBookings->count(), $canViewRevenue),
            'actions' => $this->actions($tenant, $user, $role, $todayBookings, $ranges, $now),
            'today' => $this->todayRows($todayBookings, $ranges, $role, $now),
            'tomorrow' => $this->tomorrow($tenant),
            'next' => $todayBookings->isEmpty() ? $this->nextBooking($tenant) : null,
        ];
    }

    /**
     * Records that the booking link was copied or shared (guide step 4).
     */
    public function markLinkShared(Tenant $tenant): void
    {
        if ($tenant->booking_link_shared_at === null) {
            $tenant->forceFill(['booking_link_shared_at' => Carbon::now()])->save();
        }
    }

    private function role(User $user): string
    {
        return (string) (app(ResolveTenantMembership::class)->execute($user)->role ?? '');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function banner(Tenant $tenant, ?Subscription $subscription, string $role): ?array
    {
        $canRenew = TenantPermission::allowsRole($role, 'subscription.manage');

        if (! EntitlementRules::hasEffectivePlan($subscription)) {
            return [
                'kind' => 'subscription-ended', 'tone' => 'danger',
                'message' => 'Langganan telah berakhir. Booking yang sudah ada tetap dapat dikelola.',
                'action' => $canRenew ? ['label' => 'Perpanjang langganan', 'url' => route('owner.subscription')] : null,
                'note' => $canRenew ? null : 'Hubungi Pemilik bisnis untuk memperpanjang paket.',
            ];
        }

        $trialDays = SubscriptionState::getRemainingTrialDays($subscription);
        if ($subscription?->status === SubscriptionState::STATUS_TRIAL && $trialDays <= 3) {
            return [
                'kind' => 'trial-ending', 'tone' => 'warning',
                'message' => 'Masa uji coba berakhir dalam '.$trialDays.' hari. Pilih paket agar booking online tetap berjalan.',
                'action' => $canRenew ? ['label' => 'Pilih paket', 'url' => route('owner.subscription')] : null,
                'note' => null,
            ];
        }

        $withoutSlots = collect($this->getSlotRunway->execute($tenant))->where('warning', true);
        if ($withoutSlots->isNotEmpty()) {
            return [
                'kind' => 'slot-runway', 'tone' => 'warning',
                'message' => $withoutSlots->count() === 1
                    ? 'Layanan "'.$withoutSlots->first()['service'].'" belum punya slot terbuka untuk 7 hari ke depan.'
                    : $withoutSlots->count().' layanan belum punya slot terbuka untuk 7 hari ke depan.',
                'action' => TenantPermission::allowsRole($role, 'schedule.manage') ? ['label' => 'Buka slot', 'url' => route('owner.schedule')] : null,
                'note' => null,
            ];
        }

        if (TenantPermission::allowsRole($role, 'payouts.manage') && ! TenantPayoutAccount::where('idtenant', $tenant->id)->exists()) {
            return [
                'kind' => 'payout-account', 'tone' => 'info',
                'message' => 'Simpan rekening pencairan agar pendapatan booking online bisa dicairkan.',
                'action' => Route::has('owner.payments') ? ['label' => 'Simpan rekening', 'url' => route('owner.payments', ['tab' => 'rekening'])] : null,
                'note' => null,
            ];
        }

        $quota = EntitlementRules::canCreateBooking($subscription, 0, 0);
        if (! $quota['isUnlimited'] && $quota['maxBooking'] > 0) {
            $used = SubscriptionUsage::countMonthlyBookings((int) $tenant->id);
            if ($used * 100 >= $quota['maxBooking'] * 80) {
                return [
                    'kind' => 'booking-limit', 'tone' => 'warning',
                    'message' => 'Booking bulan ini sudah '.$used.' dari '.$quota['maxBooking'].'. Naikkan paket agar booking baru tetap diterima.',
                    'action' => $canRenew ? ['label' => 'Lihat paket', 'url' => route('owner.subscription')] : null,
                    'note' => null,
                ];
            }
        }

        return null;
    }

    /**
     * The new owner guide (AC-BRD-05): null once all four steps were true, recorded so it never returns.
     *
     * @return array<string, mixed>|null
     */
    private function guide(Tenant $tenant): ?array
    {
        if ($tenant->owner_guide_completed_at !== null) {
            return null;
        }

        $profileDone = collect(['namabisnis', 'jenisbisnis', 'nomorhp', 'alamat'])
            ->every(fn (string $field): bool => trim((string) $tenant->{$field}) !== '');
        $serviceDone = Service::where('idtenant', $tenant->id)->exists();
        $slotsDone = OpeningHour::where('idtenant', $tenant->id)->exists()
            && Schedule::where('idtenant', $tenant->id)->whereDate('tanggal', '>=', Carbon::today())->exists();
        $linkDone = $tenant->booking_link_shared_at !== null;

        if ($profileDone && $serviceDone && $slotsDone && $linkDone) {
            $tenant->forceFill(['owner_guide_completed_at' => Carbon::now()])->save();

            return null;
        }

        $steps = [
            ['key' => 'profile', 'label' => 'Lengkapi profil bisnis', 'hint' => 'Nama bisnis, jenis bisnis, nomor HP dan alamat.', 'done' => $profileDone, 'event' => 'open-complete-profile', 'url' => null],
            ['key' => 'service', 'label' => 'Tambah layanan pertama', 'hint' => 'Apa yang dipesan pelanggan, dengan harga dan durasinya.', 'done' => $serviceDone, 'event' => null, 'url' => route('owner.services')],
            ['key' => 'slots', 'label' => 'Atur jam buka dan buka slot', 'hint' => 'Pelanggan hanya bisa memilih slot yang terbuka.', 'done' => $slotsDone, 'event' => null, 'url' => route('owner.schedule')],
            ['key' => 'link', 'label' => 'Bagikan link booking', 'hint' => 'Salin atau bagikan link dari kartu di bawah.', 'done' => $linkDone, 'event' => null, 'url' => null],
        ];

        return ['steps' => $steps, 'done' => count(array_filter(array_column($steps, 'done')))];
    }

    /**
     * @return list<array<string, string>>
     */
    private function figures(Tenant $tenant, int $bookingsToday, bool $canViewRevenue): array
    {
        $waiting = BookingRules::applyOccupiesSlotCondition(
            Booking::where('idtenant', $tenant->id)->where('status', BookingState::STATUS_PENDING),
        )->count();

        $slotsToday = Schedule::where('idtenant', $tenant->id)->whereDate('tanggal', Carbon::today());
        $totalSlots = (clone $slotsToday)->count();
        $occupiedSlots = (clone $slotsToday)->whereExists(function ($bookings): void {
            $bookings->selectRaw('1')->from('bookings')->whereColumn('bookings.idschedule', 'schedules.id');
            BookingRules::applyOccupiesSlotCondition($bookings);
        })->count();

        $figures = [
            ['key' => 'bookings', 'label' => 'Booking hari ini', 'value' => (string) $bookingsToday, 'hint' => 'Online dan walk-in'],
            ['key' => 'waiting', 'label' => 'Menunggu pembayaran', 'value' => (string) $waiting, 'hint' => 'Booking yang slotnya masih ditahan'],
        ];

        if ($canViewRevenue) {
            // AC-BRD-02: successful booking payments created today.
            $revenue = (float) Payment::where('idtenant', $tenant->id)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [Carbon::today(), Carbon::today()->endOfDay()])
                ->sum('jumlah');
            $figures[] = ['key' => 'revenue', 'label' => 'Pendapatan hari ini', 'value' => 'Rp '.number_format($revenue, 0, ',', '.'), 'hint' => 'Dari pembayaran berhasil'];
        }

        $figures[] = [
            'key' => 'occupancy', 'label' => 'Slot terisi hari ini',
            'value' => $totalSlots > 0 ? (int) round($occupiedSlots / $totalSlots * 100).'%' : '–',
            'hint' => $totalSlots > 0 ? $occupiedSlots.' dari '.$totalSlots.' slot' : 'Belum ada slot hari ini',
        ];

        return $figures;
    }

    /**
     * "Perlu tindakan" (AC-BRD-04): only items with a count, each linking to where it is resolved.
     *
     * @param  Collection<int, Booking>  $todayBookings
     * @param  array<int, BookingTimeRange>  $ranges
     * @return list<array<string, mixed>>
     */
    private function actions(Tenant $tenant, User $user, string $role, Collection $todayBookings, array $ranges, Carbon $now): array
    {
        $items = [];

        if (TenantPermission::allowsRole($role, 'refunds.decide')) {
            $items[] = ['key' => 'refunds', 'label' => 'Permintaan refund menunggu keputusan', 'url' => route('owner.bookings'),
                'count' => RefundRequest::where('idtenant', $tenant->id)->where('status', 'open')->count()];
        }

        if (TenantPermission::allowsRole($role, 'bookings.cancel')) {
            $items[] = ['key' => 'cancellations', 'label' => 'Permintaan pembatalan dari Kasir', 'url' => route('owner.bookings').'#permintaan-pembatalan',
                'count' => DB::table('booking_cancellation_requests')->where('idtenant', $tenant->id)->where('status', 'pending')->count()];
        }

        if (TenantPermission::allowsRole($role, 'bookings.complete')) {
            $endedEarlier = Booking::where('idtenant', $tenant->id)->where('status', BookingState::STATUS_PAID)
                ->whereDate('tanggalbooking', '<', Carbon::today())->count();
            $endedToday = $todayBookings->filter(fn (Booking $booking): bool => $booking->status === BookingState::STATUS_PAID
                && $this->hasEnded($ranges[(int) $booking->id] ?? null, $now))->count();
            $items[] = ['key' => 'ended', 'label' => 'Booking selesai yang belum ditandai', 'url' => route('owner.bookings', ['status' => 'paid']),
                'count' => $endedEarlier + $endedToday];
        }

        if (TenantPermission::allowsRole($role, 'reviews.manage')) {
            $items[] = ['key' => 'reviews', 'label' => 'Ulasan bintang 3 atau kurang belum dibalas', 'url' => route('owner.reviews'),
                'count' => Review::where('idtenant', $tenant->id)->where('rating', '<=', 3)->whereNull('balasan')->count()];
        }

        if (TenantPermission::allowsRole($role, 'payouts.manage') && Route::has('owner.payments')) {
            $items[] = ['key' => 'payouts', 'label' => 'Perubahan status pencairan', 'url' => route('owner.payments', ['tab' => 'saldo']),
                'count' => $user->unreadNotifications()->where('type', OwnerPayoutStatusChangedNotification::class)->count()];
        }

        return array_values(array_filter($items, fn (array $item): bool => $item['count'] > 0));
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @param  array<int, BookingTimeRange>  $ranges
     * @return list<array<string, mixed>>
     */
    private function todayRows(Collection $bookings, array $ranges, string $role, Carbon $now): array
    {
        $canComplete = TenantPermission::allowsRole($role, 'bookings.complete');

        return $bookings->map(function (Booking $booking) use ($ranges, $now, $canComplete): array {
            $range = $ranges[(int) $booking->id] ?? null;
            $start = $range?->startTime() ?? substr((string) $booking->jam, 0, 5);
            $end = $range?->endTime();
            $ended = $this->hasEnded($range, $now);

            return [
                'id' => (int) $booking->id,
                'code' => (string) ($booking->booking_code ?: 'BKQ-'.$booking->id),
                'time' => $range?->label() ?? $start,
                'start' => $start,
                'customer' => (string) $booking->namapelanggan,
                'service' => (string) ($booking->layanan->namalayanan ?? '-'),
                'status' => array_combine(['label', 'tone'], StatusBadge::for('booking', (string) $booking->status)),
                'payment' => $booking->payment instanceof Payment
                    ? array_combine(['label', 'tone'], StatusBadge::for('payment', (string) $booking->payment->status))
                    : ['label' => 'Tanpa pembayaran online', 'tone' => 'neutral'],
                'in_progress' => $end !== null && $start <= $now->format('H:i') && $now->format('H:i') < $end
                    && in_array($booking->status, [BookingState::STATUS_PAID, BookingState::STATUS_PENDING], true),
                'panel_url' => Route::has('owner.bookings.panel') ? route('owner.bookings.panel', $booking->id) : null,
                'href' => route('owner.bookings', ['katakunci' => $booking->booking_code ?: $booking->id]),
                'complete_url' => $canComplete && $booking->status === BookingState::STATUS_PAID && $ended
                    ? route('owner.bookings.status', $booking->id) : null,
            ];
        })->values()->all();
    }

    private function hasEnded(?BookingTimeRange $range, Carbon $now): bool
    {
        $end = $range?->endTime();

        return $end !== null && $end <= $now->format('H:i');
    }

    /**
     * @return array{count: int, first_time: string|null, first_customer: string|null}
     */
    private function tomorrow(Tenant $tenant): array
    {
        $bookings = Booking::where('idtenant', $tenant->id)
            ->whereDate('tanggalbooking', Carbon::tomorrow())
            ->where('status', '!=', BookingState::STATUS_CANCELLED)
            ->orderBy('jam')
            ->get(['id', 'jam', 'namapelanggan']);
        $first = $bookings->first();

        return [
            'count' => $bookings->count(),
            'first_time' => $first ? substr((string) $first->jam, 0, 5) : null,
            'first_customer' => $first ? (string) $first->namapelanggan : null,
        ];
    }

    /**
     * The next booking after today, for the empty-day message.
     *
     * @return array{date: string, time: string, customer: string}|null
     */
    private function nextBooking(Tenant $tenant): ?array
    {
        $next = Booking::where('idtenant', $tenant->id)
            ->whereDate('tanggalbooking', '>', Carbon::today())
            ->where('status', '!=', BookingState::STATUS_CANCELLED)
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->first(['id', 'tanggalbooking', 'jam', 'namapelanggan']);

        return $next === null ? null : [
            'date' => Carbon::parse((string) $next->tanggalbooking)->translatedFormat('l, j M'),
            'time' => substr((string) $next->jam, 0, 5),
            'customer' => (string) $next->namapelanggan,
        ];
    }

    /**
     * Quick actions of §8.1 point 6, shown only where the member's role and plan allow them.
     *
     * @return list<array<string, string>>
     */
    private function quickActions(User $user, string $role, ?Subscription $subscription): array
    {
        $actions = [];
        if (TenantPermission::allowsRole($role, 'bookings.walkin') && EntitlementRules::hasEffectivePlan($subscription)) {
            $actions[] = ['key' => 'walkin', 'label' => 'Booking', 'event' => 'owner-walkin-dialog', 'url' => ''];
        }
        if (TenantPermission::allowsRole($role, 'services.manage')) {
            $actions[] = ['key' => 'service', 'label' => 'Layanan', 'event' => '', 'url' => route('owner.services')];
        }
        $promos = collect($this->getOwnerNavigation->execute($user))->firstWhere('id', 'promos');
        if ($promos !== null && ! $promos['locked']) {
            $actions[] = ['key' => 'promo', 'label' => 'Promo', 'event' => '', 'url' => (string) $promos['url']];
        }
        if (TenantPermission::allowsRole($role, 'team.manage') && EntitlementRules::teamLimit($subscription) > 0 && Route::has('owner.team.index')) {
            $actions[] = ['key' => 'team', 'label' => 'Anggota tim', 'event' => '', 'url' => route('owner.team.index')];
        }

        return $actions;
    }
}
