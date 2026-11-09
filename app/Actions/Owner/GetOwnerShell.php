<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Actions\Schedule\GetSlotRunway;
use App\Actions\Team\GetVisibleNotifications;
use App\Actions\Team\ResolveTenantMembership;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\SubscriptionState;
use App\Domain\Team\TenantPermission;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CustomerBookingRoutes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Everything the owner app shell shows, prepared once per request (BQ-128, owner-portal.md §4):
 * navigation from the single definition (ADR-0021), menu badges, the signed-in member, the
 * storefront link and the global actions. Blade only renders this; it runs no queries.
 */
final class GetOwnerShell
{
    /** Menus of the phone bottom bar besides "+ Booking" and "Lainnya" (owner-portal.md §4.3). */
    public const PHONE_PRIMARY = ['dashboard', 'calendar', 'bookings'];

    public function __construct(
        private GetOwnerNavigation $navigation,
        private ResolveTenantMembership $membership,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(?User $user, ?string $currentRoute): array
    {
        $member = $user ? $this->membership->execute($user) : null;
        if ($user === null || $member === null || $member->tenant === null) {
            return ['ready' => false];
        }

        $tenant = $member->tenant;
        $role = (string) $member->role;
        // scope-bypass: the subscription of the signed-in member's own business
        $subscription = Subscription::withoutGlobalScopes()->with('plan')->where('idtenant', $tenant->id)->latest('id')->first();

        $items = $this->navigation->execute($user);
        $activeId = $this->activeItem($items, (string) $currentRoute);
        $badges = $this->badges($items, $tenant, $role, $subscription);

        $menus = array_map(fn (array $item): array => [
            'id' => $item['id'],
            'label' => $item['label'],
            'href' => $item['url'],
            'icon' => $item['icon'],
            'group' => $item['group'],
            'locked' => (bool) $item['locked'],
            'active' => $item['id'] === $activeId,
            'badge' => $badges[$item['id']] ?? null,
        ], $items);

        $sections = [];
        foreach ($menus as $menu) {
            $key = $menu['group'] ?? '';
            $sections[$key] ??= ['title' => $menu['group'], 'items' => []];
            $sections[$key]['items'][] = $menu;
        }

        $canWalkIn = TenantPermission::allowsRole($role, 'bookings.walkin') && EntitlementRules::hasEffectivePlan($subscription);

        return [
            'ready' => true,
            'user' => [
                'name' => (string) ($user->namalengkap ?: $user->email),
                'initial' => mb_strtoupper(mb_substr((string) ($user->namalengkap ?: $user->email), 0, 1)),
                'role' => match ($role) {
                    'pemilik' => 'Pemilik',
                    'manajer' => 'Manajer',
                    'kasir' => 'Kasir',
                    default => ucfirst($role),
                },
            ],
            'business' => (string) $tenant->namabisnis,
            'storefrontUrl' => $tenant->slug ? CustomerBookingRoutes::url('customer.booking.program', $tenant->slug) : null,
            'sections' => array_values($sections),
            'phonePrimary' => array_values(array_filter($menus, fn (array $menu): bool => in_array($menu['id'], self::PHONE_PRIMARY, true))),
            'phoneMore' => array_values(array_filter($menus, fn (array $menu): bool => ! in_array($menu['id'], self::PHONE_PRIMARY, true))),
            'unreadCount' => app(GetVisibleNotifications::class)->execute($user)->whereNull('read_at')->count(),
            'canWalkIn' => $canWalkIn,
            'canSearch' => TenantPermission::allowsRole($role, 'bookings.view'),
            'canManageAccount' => TenantPermission::allowsRole($role, 'account.manage'),
        ];
    }

    /**
     * The one menu marked active: the longest `match` prefix of the current route name.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function activeItem(array $items, string $currentRoute): ?string
    {
        $best = null;
        $bestLength = 0;
        foreach ($items as $item) {
            foreach ((array) ($item['match'] ?? [$item['route']]) as $prefix) {
                $matches = $currentRoute === $prefix || str_starts_with($currentRoute, $prefix.'.');
                if ($matches && strlen($prefix) > $bestLength) {
                    $best = (string) $item['id'];
                    $bestLength = strlen($prefix);
                }
            }
        }

        return $best;
    }

    /**
     * Menu badges of owner-portal.md §4.1, computed only for menus the member sees.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array<string, array{text: string, tone: string, label: string}>
     */
    private function badges(array $items, Tenant $tenant, string $role, ?Subscription $subscription): array
    {
        $visible = array_column($items, 'id');
        $badges = [];

        if (in_array('bookings', $visible, true)) {
            $count = 0;
            if (TenantPermission::allowsRole($role, 'refunds.decide')) {
                // scope-bypass: open refund requests of the member's own business
                $count += RefundRequest::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('status', 'open')->count();
            }
            if (TenantPermission::allowsRole($role, 'bookings.cancel')) {
                $count += DB::table('booking_cancellation_requests')->where('idtenant', $tenant->id)->where('status', 'pending')->count();
            }
            if ($count > 0) {
                $badges['bookings'] = ['text' => (string) $count, 'tone' => 'warning', 'label' => $count.' booking perlu tindakan'];
            }
        }

        if (in_array('reviews', $visible, true)) {
            // scope-bypass: reviews of the member's own business
            $unanswered = Review::withoutGlobalScopes()->where('idtenant', $tenant->id)->whereNull('balasan')->count();
            if ($unanswered > 0) {
                $badges['reviews'] = ['text' => (string) $unanswered, 'tone' => 'info', 'label' => $unanswered.' ulasan belum dibalas'];
            }
        }

        if (in_array('schedule', $visible, true)) {
            $runway = app(GetSlotRunway::class)->execute($tenant);
            if (collect($runway)->contains(fn (array $service): bool => $service['warning'])) {
                $badges['schedule'] = ['text' => '!', 'tone' => 'warning', 'label' => 'Slot terbuka kurang dari 7 hari'];
            }
        }

        if (in_array('subscription', $visible, true) && $subscription !== null
            && $subscription->status === SubscriptionState::STATUS_TRIAL && EntitlementRules::hasEffectivePlan($subscription)) {
            $end = SubscriptionState::periodEnd($subscription);
            if ($end !== null) {
                $days = max(0, (int) ceil(Carbon::now()->diffInDays($end, false)));
                $badges['subscription'] = ['text' => 'Sisa '.$days.' hari', 'tone' => 'info', 'label' => 'Masa uji coba tersisa '.$days.' hari'];
            }
        }

        return $badges;
    }
}
