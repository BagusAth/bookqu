<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payment\PaymentOutcomeIntake;
use App\Domain\Payment\PaymentRules;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireBookingPayments extends Command
{
    use ClearsBookingCache;

    protected $signature = 'bookings:expire-payments
                            {--dry-run : Show what would be expired without making changes}';

    protected $description = 'Reclaim booking and subscription payments past their deadline: ask the provider first, then expire them and free their slots.';

    public function handle(PaymentOutcomeIntake $intake): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? '[DRY RUN] Checking expired booking payments...' : 'Checking expired booking payments...');

        // scope-bypass: the sweep covers every tenant; each payment is reclaimed in its own tenant-locked transaction.
        // Booking and subscription payments past their deadline plus the settlement margin (ADR-0009)
        $expiredPayments = Payment::withoutGlobalScopes()
            ->whereIn('tipe', ['booking', 'subscription'])
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now()->subMinutes(PaymentRules::settlementMarginMinutes()))
            ->orderBy('idtenant')
            ->get();

        if ($expiredPayments->isEmpty()) {
            $this->info('No expired booking payments found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$expiredPayments->count()} expired payment(s).");

        $processed = 0;
        $cacheCleared = [];

        foreach ($expiredPayments as $payment) {
            app(TenantContext::class)->setTenantId($payment->idtenant);

            // Load all pending bookings linked to this payment
            // scope-bypass: bookings of one payment; tenant context set from that payment
            $bookings = Booking::withoutGlobalScopes()
                ->where('idpayment', $payment->id)
                ->where('status', 'pending')
                ->get();

            if ($isDryRun) {
                $this->line(" [DRY] Would cancel Payment #{$payment->id} (order: {$payment->order_id}) and {$bookings->count()} booking(s)");

                continue;
            }

            try {
                // Ask the payment's own provider first, then expire it if it is still pending (ADR-0007)
                $result = $intake->check($payment);

                if ($result->status() === 'sukses') {
                    $this->line("  Payment #{$payment->id} verified as successful by its provider. Skipped cancellation.");

                    continue;
                }

                $this->line("  Cancelled Payment #{$payment->id} and {$bookings->count()} Booking(s)");
                $processed++;

            } catch (\Throwable $e) {
                // BQ-077: the exception message can carry SQL, credentials or provider payloads; log only its type and location.
                Log::error('ExpireBookingPayments: Failed to expire payment', [
                    'payment_id' => $payment->id,
                    'tenant_id' => $payment->idtenant,
                    'exception_type' => $e::class,
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                ]);
                $this->error("  Failed to expire Payment #{$payment->id} (".class_basename($e).'). See the application log.');
            }
        }

        if (! $isDryRun) {
            $this->info("Done. Cancelled {$processed} expired booking payment(s).");
            $this->info('Cache cleared for '.count($cacheCleared).' slot(s).');
        }

        app(TenantContext::class)->clear();

        return Command::SUCCESS;
    }
}
