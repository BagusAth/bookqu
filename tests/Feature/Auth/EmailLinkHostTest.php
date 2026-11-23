<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\OwnerPayout;
use App\Models\User;
use App\Notifications\NewPayoutRequestNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Links that leave the request (mail, stored notifications) are built from APP_URL, never from the
 * Host header: a forged Host would otherwise mail a reset token to a host the attacker controls (BQ-173).
 */
class EmailLinkHostTest extends TestCase
{
    use RefreshDatabase;

    private const FORGED = 'http://evil.example';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://bookqu.test']);
    }

    public function test_password_reset_link_ignores_a_forged_host_header(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'owner']);

        $this->post(self::FORGED.'/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $link = $notification->toMail($user)->actionUrl;

            return str_starts_with($link, 'http://bookqu.test/reset-password/') && ! str_contains($link, 'evil.example');
        });
    }

    public function test_verification_link_ignores_a_forged_host_header_and_still_verifies(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => null]);

        $this->actingAs($user)->post(self::FORGED.'/email/verification-notification');

        $link = null;
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$link): bool {
            $link = $notification->toMail($user)->actionUrl;

            return true;
        });
        $this->assertStringStartsWith('http://bookqu.test/email/verify/', $link);

        auth()->logout();
        $this->get($link)->assertRedirect('/login');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_admin_payout_notification_link_ignores_the_request_host(): void
    {
        URL::forceRootUrl(self::FORGED);
        $payout = new OwnerPayout(['idtenant' => 1, 'jumlah' => 100000]);

        $data = (new NewPayoutRequestNotification($payout, 'Barber Pro'))->toArray(new User);

        $this->assertSame('http://bookqu.test/admin/payouts?status=pending', $data['url']);
    }
}
