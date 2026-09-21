<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\MidtransPaymentService;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireBookingPayments extends Command
{
    use ClearsBookingCache;

    protected $signature = 'bookings:expire-payments
                            {--dry-run : Show what would be expired without making changes}';

    protected $description = 'Cancel pending bookings whose payment has expired, and invalidate availability cache for freed slots.';

    public function handle(MidtransPaymentService $paymentService): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? '[DRY RUN] Checking expired booking payments...' : 'Checking expired booking payments...');

        // Find all pending payments of type=booking that have passed expired_at across all tenants
        $expiredPayments = Payment::withoutGlobalScopes()
            ->where('tipe', 'booking')
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->get();

        if ($expiredPayments->isEmpty()) {
            $this->info('No expired booking payments found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredPayments->count()} expired payment(s).");

        $processed    = 0;
        $cacheCleared = [];

        foreach ($expiredPayments as $payment) {
            app(\App\Support\TenantContext::class)->setTenantId($payment->idtenant);

            // Load all pending bookings linked to this payment
            $bookings = Booking::withoutGlobalScopes()
                ->where('idpayment', $payment->id)
                ->where('status', 'pending')
                ->get();

            if ($isDryRun) {
                $this->line(" [DRY] Would cancel Payment #{$payment->id} (order: {$payment->order_id}) and {$bookings->count()} booking(s)");
                continue;
            }

            try {
                // Verify with Midtrans first to ensure we don't cancel a successful payment
                // whose webhook failed to reach us
                $syncResult = $paymentService->verifyAndSync($payment);
                
                // Refresh payment after sync
                $payment->refresh();
                
                // If it became successful after sync, skip cancelling
                if ($payment->status === 'sukses') {
                    $this->line("  Payment #{$payment->id} verified as successful from Midtrans. Skipped cancellation.");
                    continue;
                }

                // Delegate to centralized expirePayment for atomic transaction, row locking, and cache invalidation
                $paymentService->expirePayment($payment);

                $this->line("  Cancelled Payment #{$payment->id} and {$bookings->count()} Booking(s)");
                $processed++;

            } catch (\Throwable $e) {
                Log::error("ExpireBookingPayments: Failed to expire Payment #{$payment->id}: " . $e->getMessage());
                $this->error("  Failed to expire Payment #{$payment->id}: " . $e->getMessage());
            }
        }

        if (!$isDryRun) {
            $this->info("Done. Cancelled {$processed} expired booking payment(s).");
            $this->info('Cache cleared for ' . count($cacheCleared) . ' slot(s).');
        }

        app(\App\Support\TenantContext::class)->clear();

        return Command::SUCCESS;
    }
}
