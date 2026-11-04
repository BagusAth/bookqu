<?php

namespace App\Actions\Subscription;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionReminderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SendSubscriptionReminders
{
    public function execute(): int
    {
        $sent = 0;
        // scope-bypass: scheduled platform sweep across tenants; each candidate is revalidated under its tenant lock.
        Subscription::withoutGlobalScopes()->whereIn('status', ['trial', 'active', 'expired'])->orderBy('id')->chunkById(100, function ($subscriptions) use (&$sent): void {
            foreach ($subscriptions as $subscription) {
                $end = SubscriptionState::periodEnd($subscription);
                if ($end === null) {
                    continue;
                }
                $days = (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
                if (! in_array($days, [3, 1, 0], true)) {
                    continue;
                }
                $queued = DB::transaction(function () use ($subscription, $days): bool {
                    ClaimSlots::lockTenant((int) $subscription->idtenant);
                    // scope-bypass: candidate belongs to this stored tenant, reloaded under the tenant-first lock.
                    $current = Subscription::withoutGlobalScopes()->where('idtenant', $subscription->idtenant)->lockForUpdate()->find($subscription->id);
                    if (! $current || DB::table('subscriptions')->where('idtenant', $subscription->idtenant)->where('id', '>', $subscription->id)->exists()) {
                        return false;
                    }
                    $end = SubscriptionState::periodEnd($current);
                    if ($end === null || (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) !== $days) {
                        return false;
                    }
                    $owner = Tenant::find($current->idtenant)?->user;
                    if (! $owner instanceof User) {
                        return false;
                    }
                    $receipt = ['idtenant' => $current->idtenant, 'subscription_id' => $current->id, 'period_end_at' => $end->toDateTimeString(), 'days_before' => $days];
                    if (! DB::table('subscription_reminders')->insertOrIgnore($receipt + ['created_at' => now(), 'updated_at' => now()])) {
                        return false;
                    }
                    DB::afterCommit(function () use ($owner, $current, $days, $end, $receipt): void {
                        try {
                            $owner->notify(new SubscriptionReminderNotification($current, $days, $end->toDateTimeString()));
                        } catch (\Throwable $exception) {
                            DB::table('subscription_reminders')->where($receipt)->delete();
                            Log::warning('Subscription reminder queue failed', ['subscription_id' => $current->id, 'exception_type' => $exception::class]);
                        }
                    });

                    return true;
                });
                $sent += $queued ? 1 : 0;
            }
        });

        return $sent;
    }
}
