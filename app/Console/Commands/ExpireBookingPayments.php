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

            // Load the pending booking linked to this payment
            $booking = Booking::withoutGlobalScopes()
                ->where('idpayment', $payment->id)
                ->where('status', 'pending')
                ->first();

            if ($isDryRun) {
                $this->line(" [DRY] Would cancel Payment #{$payment->id} (order: {$payment->order_id})" .
                    ($booking ? " and Booking #{$booking->id}" : ' (no pending booking)'));
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

                // If still pending (which means it's truly expired in Midtrans too) or already failed, proceed to cancel
                DB::transaction(function () use ($payment, $booking) {
                    if ($payment->status === 'pending') {
                        $payment->update(['status' => 'gagal']);
                    }

                    if ($booking) {
                        $booking->update(['status' => 'cancelled']);
                    }
                });

                // Invalidate cache for the freed slot (only once per tenant+service+date combination)
                if ($booking && $booking->idlayanan && $booking->tanggalbooking) {
                    $tanggal   = $booking->tanggalbooking instanceof Carbon
                        ? $booking->tanggalbooking->toDateString()
                        : Carbon::parse($booking->tanggalbooking)->toDateString();

                    $cacheKey = "{$booking->idtenant}:{$booking->idlayanan}:{$tanggal}";

                    if (!isset($cacheCleared[$cacheKey])) {
                        $this->clearBookingAvailabilityCache(
                            (int) $booking->idtenant,
                            (int) $booking->idlayanan,
                            $tanggal
                        );
                        $cacheCleared[$cacheKey] = true;

                        $this->line("  Cleared cache: tenant={$booking->idtenant} service={$booking->idlayanan} date={$tanggal}");
                    }
                }

                $this->line("  Cancelled Payment #{$payment->id}" .
                    ($booking ? " and Booking #{$booking->id}" : ''));
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
