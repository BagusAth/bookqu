<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'app:check-expired-subscriptions';

    protected $description = 'Check and update expired trial and active subscriptions';

    public function handle(): int
    {
        $expiredTrials = Subscription::where('status', 'trial')
            ->whereNotNull('trial_berakhir')
            ->where('trial_berakhir', '<', now())
            ->update(['status' => 'expired']);

        $expiredActives = Subscription::where('status', 'active')
            ->whereNotNull('langganan_berakhir')
            ->where('langganan_berakhir', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Berhasil mengupdate {$expiredTrials} trial dan {$expiredActives} langganan aktif menjadi expired.");

        return Command::SUCCESS;
    }
}
