<?php

namespace Tests\Feature\Owner;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WeeklySummaryNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** BQ-162 batch 4: weekly summary email for the Pemilik (FR-NOTIFICATION-008). */
class WeeklySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_owner_gets_last_weeks_summary_unless_switched_off(): void
    {
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-16 07:00:00', 'Asia/Jakarta'));
        $on = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        Tenant::factory()->create(['iduser' => $on->id]);
        $off = User::factory()->create(['role' => 'owner', 'email_verified_at' => now(), 'notification_preferences' => ['weekly_summary' => false]]);
        Tenant::factory()->create(['iduser' => $off->id]);

        $this->artisan('owners:weekly-summary')->assertSuccessful();

        Notification::assertSentTo($on, WeeklySummaryNotification::class, fn (WeeklySummaryNotification $n): bool => $n->summary['from'] === '9 Nov' && $n->summary['to'] === '15 Nov 2026');
        Notification::assertNotSentTo($off, WeeklySummaryNotification::class);
    }
}
