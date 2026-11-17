<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Actions\Booking\CompleteBookings;
use App\Actions\Report\GetReport;
use App\Actions\Schedule\GetSlotRunway;
use App\Models\RefundRequest;
use App\Models\Tenant;
use App\Notifications\WeeklySummaryNotification;
use App\Support\ReportPeriod;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Weekly summary email for the Pemilik (FR-NOTIFICATION-008). Figures come from the same
 * Actions as the owner portal: GetReport (revenue, bookings, occupancy), GetSlotRunway
 * (slots open until) and CompleteBookings (ended sessions not yet closed).
 */
final class SendWeeklySummary
{
    public function __construct(
        private GetReport $report,
        private GetSlotRunway $runway,
        private CompleteBookings $completeBookings,
    ) {}

    public function execute(Tenant $tenant, ?Carbon $today = null): bool
    {
        $owner = $tenant->user;
        if ($owner === null || ! ($owner->notification_preferences['weekly_summary'] ?? true)) {
            return false;
        }

        $today = ($today ?? Carbon::today('Asia/Jakarta'))->copy()->startOfDay();
        $from = $today->copy()->subWeek()->startOfWeek();
        $to = $from->copy()->endOfWeek();

        $context = app(TenantContext::class);
        $previous = $context->getTenantId();
        $context->setTenantId((int) $tenant->id);
        try {
            $period = ReportPeriod::fromQuery(['periode' => 'kustom', 'dari' => $from->toDateString(), 'sampai' => $to->toDateString()]);
            $report = $this->report->execute($tenant, $period, 'okupansi');
            $openUntil = collect($this->runway->execute($tenant))->pluck('open_until')->filter()->min();
            $summary = [
                'from' => $from->translatedFormat('j M'),
                'to' => $to->translatedFormat('j M Y'),
                'revenue' => (float) ($report['summary']['revenue'] ?? 0),
                'bookings' => (int) ($report['summary']['bookings'] ?? 0),
                'occupancy' => (float) ($report['occupancy']['rate'] ?? 0),
                'openUntil' => $openUntil !== null ? Carbon::parse($openUntil)->translatedFormat('j M') : null,
                'endedNotClosed' => count($this->completeBookings->endedIds($tenant, Carbon::now('Asia/Jakarta'))),
                'openRefunds' => RefundRequest::where('status', 'open')->count(),
            ];
        } finally {
            $context->setTenantId($previous);
        }

        try {
            $owner->notify(new WeeklySummaryNotification($tenant, $summary));
        } catch (\Throwable $e) {
            Log::warning('SendWeeklySummary: notification failed', ['exception_type' => $e::class, 'tenant_id' => $tenant->id]);

            return false;
        }

        return true;
    }
}
