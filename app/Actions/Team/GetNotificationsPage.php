<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Domain\Team\TenantPermission;
use App\Models\User;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Notifications\NewBookingOwnerNotification;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use App\Notifications\PaymentRefundOwedOwnerNotification;
use App\Notifications\RefundTeamNotification;
use App\Notifications\SubscriptionReminderNotification;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The notification centre (owner-portal.md §8.16): the member's visible notifications with an
 * unread filter and a type filter. Types the role may not see are neither listed nor offered as a
 * filter (AC-NTF-02, through GetVisibleNotifications and TenantPermission).
 */
final class GetNotificationsPage
{
    /** @var array<string, array{label: string, types: list<class-string>}> */
    public const CATEGORIES = [
        'booking' => ['label' => 'Booking', 'types' => [NewBookingOwnerNotification::class, BookingStatusChangedOwnerNotification::class]],
        'refund' => ['label' => 'Refund', 'types' => [RefundTeamNotification::class, PaymentRefundOwedOwnerNotification::class]],
        'pencairan' => ['label' => 'Pencairan', 'types' => [OwnerPayoutStatusChangedNotification::class]],
        'paket' => ['label' => 'Paket', 'types' => [TrialExpiringNotification::class, SubscriptionReminderNotification::class]],
    ];

    public function __construct(private GetVisibleNotifications $getVisibleNotifications) {}

    /**
     * @return array{notifications: LengthAwarePaginator<int, array<string, mixed>>, filter: string, type: string|null, typeFilters: array<string, string>, unreadCount: int}
     */
    public function execute(User $user, ?string $filter, ?string $type): array
    {
        $member = app(ResolveTenantMembership::class)->execute($user);
        $role = (string) ($member->role ?? '');
        $allowedTypes = TenantPermission::allowsRole($role, 'payouts.manage') ? null : TenantPermission::notificationTypes($role);

        $typeFilters = [];
        foreach (self::CATEGORIES as $key => $category) {
            if ($allowedTypes === null || array_intersect($category['types'], $allowedTypes) !== []) {
                $typeFilters[$key] = $category['label'];
            }
        }

        $filter = $filter === 'unread' ? 'unread' : 'all';
        $type = array_key_exists((string) $type, $typeFilters) ? (string) $type : null;

        $notifications = $this->getVisibleNotifications->execute($user)
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($type !== null, fn ($query) => $query->whereIn('type', self::CATEGORIES[$type]['types']))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification): array => $this->row($notification));

        return [
            'notifications' => $notifications,
            'filter' => $filter,
            'type' => $type,
            'typeFilters' => $typeFilters,
            'unreadCount' => $this->getVisibleNotifications->execute($user)->whereNull('read_at')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = (array) $notification->data;
        $category = collect(self::CATEGORIES)->search(fn (array $item): bool => in_array($notification->type, $item['types'], true));

        return [
            'id' => (string) $notification->id,
            'title' => (string) ($data['title'] ?? 'Pemberitahuan'),
            'message' => (string) ($data['message'] ?? ''),
            'time' => $notification->created_at?->diffForHumans(),
            'category' => $category !== false ? self::CATEGORIES[$category]['label'] : 'Lainnya',
            'unread' => $notification->read_at === null,
            'readUrl' => route('owner.notifications.read', $notification->id),
            'destroyUrl' => route('owner.notifications.destroy', $notification->id),
        ];
    }
}
