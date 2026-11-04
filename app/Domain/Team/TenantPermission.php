<?php

namespace App\Domain\Team;

use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Notifications\NewBookingOwnerNotification;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use App\Notifications\PaymentRefundOwedOwnerNotification;
use App\Notifications\SubscriptionReminderNotification;
use App\Notifications\TrialExpiringNotification;

final class TenantPermission
{
    private const COUNTER = ['dashboard.view', 'calendar.view', 'bookings.view', 'bookings.walkin', 'bookings.complete', 'bookings.no_show', 'bookings.reschedule', 'bookings.request-cancel', 'customers.view', 'customers.manage', 'account.manage', 'notifications.view', 'help.view'];

    private const MANAGEMENT = ['bookings.cancel', 'refunds.decide', 'services.manage', 'schedule.manage', 'booking-page.manage', 'promos.manage', 'reviews.manage', 'reports.view', 'settings.manage', 'integrations.manage', 'revenue.view', 'units.manage'];

    private const OWNER = ['payouts.manage', 'subscription.manage', 'team.manage', 'business.delete', 'bookings.settle'];

    public static function isOwnerRole(string $role): bool
    {
        return $role === 'pemilik';
    }

    public static function allowsRole(string $role, string $ability): bool
    {
        $abilities = match ($role) {
            'pemilik' => [...self::COUNTER, ...self::MANAGEMENT, ...self::OWNER],
            'manajer' => [...self::COUNTER, ...self::MANAGEMENT],
            'kasir' => self::COUNTER,
            default => [],
        };

        return in_array($ability, $abilities, true);
    }

    public static function routeAbility(string $name, string $method, mixed $status = null): ?string
    {
        $route = preg_replace('/^owner\./', '', $name);
        if ($route === 'bookings.status') {
            return match ($status) {
                'cancelled' => 'bookings.cancel', 'paid' => 'bookings.settle', 'completed' => 'bookings.complete', 'no_show' => 'bookings.no_show', default => null
            };
        }
        if ($route === 'settings.account.delete') {
            return 'business.delete';
        }
        if ($route === 'settings.account') {
            return 'account.manage';
        }
        if ($route === 'settings' && $method === 'GET') {
            return 'account.manage';
        }
        $prefix = explode('.', (string) $route)[0];

        return match ($prefix) {
            'dashboard' => 'dashboard.view', 'calendar' => 'calendar.view',
            'bookings' => match ($route) {
                'bookings.walkin' => 'bookings.walkin','bookings.reschedule' => 'bookings.reschedule',default => 'bookings.view'
            },
            'customers' => $method === 'GET' ? 'customers.view' : 'customers.manage',
            'programs','services','categories','staff','staff-resources','resources','additional-items' => 'services.manage',
            'schedule' => 'schedule.manage','schedule-report','analytics' => 'reports.view',
            'vouchers' => 'promos.manage','reviews' => 'reviews.manage','landing-page' => 'booking-page.manage',
            'payouts','payout-accounts' => 'payouts.manage','checkout','subscription' => 'subscription.manage',
            'notifications' => 'notifications.view','help' => 'help.view','team' => 'team.manage',
            'settings' => match ($route) {
                'settings.balance','settings.payments','settings.payment','settings.payment-setting' => 'payouts.manage',
                'settings.integrations' => 'integrations.manage', default => 'settings.manage',
            },
            'units' => 'units.manage', 'promos' => 'promos.manage', 'booking-page' => 'booking-page.manage',
            'payments' => 'payouts.manage', 'reports' => 'reports.view', 'api' => 'integrations.manage',
            'integrations' => 'integrations.manage', 'refunds' => 'refunds.decide',
            'profile' => 'settings.manage',default => null,
        };
    }

    public static function notificationAbility(string $event): ?string
    {
        return match ($event) {
            'new_booking','cancelled','rescheduled','completed','status_updated','status_change' => 'bookings.view',
            'payout_requested','payout_approved','payout_rejected','refund_owed' => 'payouts.manage',
            'trial_expiring','subscription_expiring','subscription' => 'subscription.manage',
            default => null,
        };
    }

    /** @return array<string, string> */
    public static function notificationMap(): array
    {
        return [
            NewBookingOwnerNotification::class => 'bookings.view',
            BookingStatusChangedOwnerNotification::class => 'bookings.view',
            OwnerPayoutStatusChangedNotification::class => 'payouts.manage',
            PaymentRefundOwedOwnerNotification::class => 'payouts.manage',
            TrialExpiringNotification::class => 'subscription.manage',
            SubscriptionReminderNotification::class => 'subscription.manage',
        ];
    }

    /** @return list<string> */
    public static function notificationTypes(string $role): array
    {
        return array_keys(array_filter(self::notificationMap(), fn (string $ability) => self::allowsRole($role, $ability)));
    }
}
