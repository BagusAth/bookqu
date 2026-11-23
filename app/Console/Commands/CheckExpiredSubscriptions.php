<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Schedule\ClaimSlots;
use App\Actions\Subscription\SendSubscriptionReminders;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Subscription;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'app:check-expired-subscriptions';

    protected $description = 'Check and update expired trial and active subscriptions';

    public function handle(SendSubscriptionReminders $reminders): int
    {
        $this->info('Pengingat paket dijadwalkan: '.$reminders->execute().'.');
        $expired = 0;
        // scope-bypass: scheduled platform sweep; statuses are tidied only through SubscriptionState's date and transition rules.
        Subscription::withoutGlobalScopes()->whereIn('status', ['trial', 'active'])->select(['id', 'idtenant'])->eachById(function (Subscription $candidate) use (&$expired): void {
            $tenantId = (int) $candidate->idtenant;
            $changed = app(TenantContext::class)->run($tenantId, fn (): bool => DB::transaction(function () use ($tenantId, $candidate): bool {
                ClaimSlots::lockTenant($tenantId);
                $subscription = Subscription::lockForUpdate()->find($candidate->id);

                return $subscription !== null && SubscriptionState::isExpired($subscription)
                    && SubscriptionState::canTransition((string) $subscription->status, SubscriptionState::STATUS_EXPIRED)
                    && $subscription->update(['status' => SubscriptionState::STATUS_EXPIRED]);
            }));
            $expired += (int) $changed;
        });
        $this->info('Periode kedaluwarsa diperbarui: '.$expired.'.');

        return Command::SUCCESS;
    }
}
