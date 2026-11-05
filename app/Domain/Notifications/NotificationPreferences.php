<?php

namespace App\Domain\Notifications;

use App\Models\User;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Notifications\NewBookingOwnerNotification;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use App\Notifications\PaymentRefundOwedOwnerNotification;
use App\Notifications\RefundTeamNotification;
use App\Notifications\SubscriptionReminderNotification;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Notifications\Notification;

final class NotificationPreferences
{
    public const EVENTS = ['new_booking', 'payment_success', 'cancellation', 'reschedule', 'refund_request', 'new_review', 'payout_status', 'plan_status'];

    public static function allowsMail(User $user, Notification $notification): bool
    {
        $event = match (true) {
            $notification instanceof NewBookingOwnerNotification => 'new_booking',
            $notification instanceof BookingStatusChangedOwnerNotification => match ($notification->action) {
                'paid' => 'payment_success',
                'cancelled' => 'cancellation',
                'rescheduled' => 'reschedule',
                default => null,
            },
            $notification instanceof RefundTeamNotification, $notification instanceof PaymentRefundOwedOwnerNotification => 'refund_request',
            $notification instanceof OwnerPayoutStatusChangedNotification => 'payout_status',
            $notification instanceof SubscriptionReminderNotification, $notification instanceof TrialExpiringNotification => 'plan_status',
            default => null,
        };

        return $event === null || ($user->notification_preferences[$event] ?? true);
    }
}
