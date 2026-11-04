<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Subscription\SendSubscriptionReminders;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Subscription;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'app:check-expired-subscriptions';

    protected $description = 'Check and update expired trial and active subscriptions';

    public function handle(SendSubscriptionReminders $reminders): int
    {
        $this->info('Pengingat paket dijadwalkan: '.$reminders->execute().'.');
        $expired = 0;
        // scope-bypass: scheduled platform sweep; statuses are tidied only through SubscriptionState's date and transition rules.
        Subscription::withoutGlobalScopes()->whereIn('status', ['trial', 'active'])->chunkById(100, function ($subscriptions) use (&$expired): void {
            foreach ($subscriptions as $subscription) {
                if (SubscriptionState::isExpired($subscription)
                    && SubscriptionState::canTransition((string) $subscription->status, 'expired')) {
                    $subscription->update(['status' => 'expired']);
                    $expired++;
                }
            }
        });
        $this->info('Periode kedaluwarsa diperbarui: '.$expired.'.');

        return Command::SUCCESS;
    }
}
