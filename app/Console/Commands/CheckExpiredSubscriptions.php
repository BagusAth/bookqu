<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'app:check-expired-subscriptions';

    protected $description = 'Check and update expired trial and active subscriptions';

    public function handle(): int
    {
        // FS-020: Kirim email notifikasi ke owner yang trialnya akan habis H-1
        $expiringTrials = Subscription::with(['tenant.user'])
            ->where('status', 'trial')
            ->whereNotNull('trial_berakhir')
            ->whereDate('trial_berakhir', now()->addDay()->toDateString())
            ->get();

        $notifKirim = 0;
        foreach ($expiringTrials as $sub) {
            $owner = $sub->tenant?->user;

            if ($owner && $owner->email) {
                try {
                    $owner->notify(new TrialExpiringNotification($sub));
                    $notifKirim++;
                } catch (\Exception $e) {
                    Log::error("Gagal kirim notif trial ke owner tenant {$sub->idtenant}: " . $e->getMessage());
                }
            } else {
                Log::warning("Tenant {$sub->idtenant} tidak memiliki user/email, notif trial dilewati.");
            }
        }

        $this->info("Notifikasi trial H-1 terkirim: {$notifKirim}.");

        // FS-021: Update status trial yang sudah lewat → expired
        $expiredTrials = Subscription::where('status', 'trial')
            ->whereNotNull('trial_berakhir')
            ->where('trial_berakhir', '<', now())
            ->update(['status' => 'expired']);

        // FS-021: Update langganan aktif yang sudah lewat → expired
        $expiredActives = Subscription::where('status', 'active')
            ->whereNotNull('langganan_berakhir')
            ->where('langganan_berakhir', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Berhasil mengupdate {$expiredTrials} trial dan {$expiredActives} langganan aktif menjadi expired.");

        return Command::SUCCESS;
    }
}
