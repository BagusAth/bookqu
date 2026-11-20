<?php

namespace Tests\Feature\Owner;

use App\Actions\Owner\GetOwnerSettings;
use App\Actions\Subscription\CreateTrialSubscription;
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
        $onTenant = Tenant::factory()->create(['iduser' => $on->id]);
        app(CreateTrialSubscription::class)->execute($onTenant);
        $off = User::factory()->create(['role' => 'owner', 'email_verified_at' => now(), 'notification_preferences' => ['weekly_summary' => false]]);
        $offTenant = Tenant::factory()->create(['iduser' => $off->id]);
        app(CreateTrialSubscription::class)->execute($offTenant);
        $expired = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($expired)->update(['trial_berakhir' => now()->subDay()]);

        $this->artisan('owners:weekly-summary')->assertSuccessful();

        Notification::assertSentTo($on, WeeklySummaryNotification::class, fn (WeeklySummaryNotification $n): bool => $n->summary['from'] === '9 Nov' && $n->summary['to'] === '15 Nov 2026');
        Notification::assertNotSentTo($off, WeeklySummaryNotification::class);
        Notification::assertNotSentTo($expired->user, WeeklySummaryNotification::class);
    }

    public function test_the_summary_period_uses_indonesian_month_names_even_when_the_app_locale_is_english(): void
    {
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-12-07 07:00:00', 'Asia/Jakarta'));
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        app(CreateTrialSubscription::class)->execute($tenant);
        app()->setLocale('en');

        $this->artisan('owners:weekly-summary')->assertSuccessful();

        Notification::assertSentTo($owner, WeeklySummaryNotification::class, fn (WeeklySummaryNotification $n): bool => $n->summary['from'] === '30 Nov' && $n->summary['to'] === '6 Des 2026');
    }

    public function test_only_the_business_owner_sees_the_weekly_summary_preference(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        $member = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);

        $settings = app(GetOwnerSettings::class);

        $this->assertArrayHasKey('weekly_summary', $settings->execute($tenant, $owner, 'notifications')['notificationEvents']);
        $this->assertArrayNotHasKey('weekly_summary', $settings->execute($tenant, $member, 'notifications')['notificationEvents']);
    }
}
