<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTestBookingToken extends Command
{
    protected $signature = 'booking:generate-test-token
                            {--booking-id= : ID booking yang sudah ada (opsional)}
                            {--tenant-id=1 : ID tenant (default: 1)}
                            {--status=paid : Status booking: paid, cancelled, completed}';

    protected $description = 'Generate booking_code + token untuk testing fitur Booking Management Without Account';

    public function handle(): int
    {
        $bookingId = $this->option('booking-id');
        $tenantId  = (int) $this->option('tenant-id');
        $status    = $this->option('status');

        // ── Resolve booking ──────────────────────────────────────────────────
        if ($bookingId) {
            $booking = Booking::with(['tenant', 'layanan', 'payment'])->find($bookingId);
            if (!$booking) {
                $this->error("Booking ID #{$bookingId} tidak ditemukan.");
                return 1;
            }
        } else {
            // Find an existing booking with the given status, or create a mock one
            $booking = Booking::with(['tenant', 'layanan', 'payment'])
                ->where('idtenant', $tenantId)
                ->where('status', $status)
                ->whereNotNull('idpayment')
                ->latest()
                ->first();

            if (!$booking) {
                $this->warn("Tidak ada booking dengan status '{$status}' di tenant #{$tenantId}.");
                $this->info("Mencoba mencari booking manapun di tenant #{$tenantId}...");

                $booking = Booking::with(['tenant', 'layanan', 'payment'])
                    ->where('idtenant', $tenantId)
                    ->latest()
                    ->first();

                if (!$booking) {
                    $this->error("Tidak ada booking di tenant #{$tenantId}. Jalankan seeder terlebih dahulu.");
                    return 1;
                }

                // Update status for testing
                $this->info("Menggunakan booking ID #{$booking->id}, update status ke '{$status}'...");
                $booking->update(['status' => $status]);
                $booking->refresh();
            }
        }

        // ── Generate / regenerate tokens ─────────────────────────────────────
        $this->info("📋 Booking ditemukan: #{$booking->id} — {$booking->namapelanggan}");

        if ($this->confirm("Generate ulang token untuk booking ini? (token lama akan diganti)", true)) {
            $booking->assignManagementTokens();
            $booking->refresh();

            // Create audit log
            BookingLog::record(
                $booking->id,
                'payment_success',
                '[TEST] Token di-generate manual via artisan command untuk testing.',
                ['generated_at' => now()->toIso8601String(), 'generated_by' => 'artisan:test']
            );

            $this->info("✅ Token berhasil di-generate!");
        }

        if (!$booking->booking_code || !$booking->cancellation_token) {
            $this->error("Token tidak tersedia. Coba jalankan ulang command ini.");
            return 1;
        }

        // ── Output ────────────────────────────────────────────────────────────
        $tenant     = $booking->tenant;
        $manageUrl  = url('/manage/' . $booking->booking_code) . '?token=' . $booking->cancellation_token;
        $reschedUrl = url('/manage/' . $booking->booking_code . '/reschedule') . '?token=' . $booking->reschedule_token;
        $invoiceUrl = url('/manage/' . $booking->booking_code . '/invoice') . '?token=' . $booking->cancellation_token;

        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->info('  🎯 TEST BOOKING MANAGEMENT WITHOUT ACCOUNT');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['Booking ID',     '#' . $booking->id],
                ['Booking Code',   $booking->booking_code],
                ['Customer',       $booking->namapelanggan],
                ['Email',          $booking->email],
                ['Layanan',        $booking->layanan?->namalayanan ?? '-'],
                ['Tanggal',        Carbon::parse($booking->tanggalbooking)->format('d M Y')],
                ['Jam',            Carbon::parse($booking->jam)->format('H:i')],
                ['Status',         strtoupper($booking->status)],
                ['Tenant',         $tenant?->namabisnis ?? '-'],
                ['Cancel Policy',  ($tenant?->cancel_before_hours ?? 24) . ' jam sebelum jadwal'],
            ]
        );

        $this->newLine();
        $this->line('─────────────────────────────────────────────────────────────');
        $this->warn('  📎 TEST URLs (buka di browser):');
        $this->line('─────────────────────────────────────────────────────────────');
        $this->newLine();
        $this->info('  [1] Halaman Utama Manage Booking:');
        $this->line("      {$manageUrl}");
        $this->newLine();
        $this->info('  [2] Halaman Reschedule:');
        $this->line("      {$reschedUrl}");
        $this->newLine();
        $this->info('  [3] Invoice (print-ready):');
        $this->line("      {$invoiceUrl}");
        $this->newLine();
        $this->line('─────────────────────────────────────────────────────────────');
        $this->warn('  🔑 TOKENS (untuk manual testing):');
        $this->line('─────────────────────────────────────────────────────────────');
        $this->line("  Cancellation Token : " . $booking->cancellation_token);
        $this->line("  Reschedule Token   : " . $booking->reschedule_token);
        $this->newLine();
        $this->line('  Test token SALAH (harusnya 403):');
        $this->line("  " . url('/manage/' . $booking->booking_code) . '?token=INI_TOKEN_SALAH_HARUSNYA_403');
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // ── Quick test: validate token works ─────────────────────────────────
        $found = Booking::where('booking_code', $booking->booking_code)
            ->whereRaw('cancellation_token = ?', [$booking->cancellation_token])
            ->exists();

        if ($found) {
            $this->info('  ✅ Token validation test: PASSED (token ditemukan di DB)');
        } else {
            $this->error('  ❌ Token validation test: FAILED');
        }

        $this->newLine();

        return 0;
    }
}
