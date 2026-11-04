<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccountEmailChangedNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * BQ-098 (FR-AUTH-006, NFR-SEC-006): email, password and account deletion need the current password.
 */
class AccountChangeRequiresCurrentPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email' => 'owner@example.com',
            'namalengkap' => 'Owner Lama',
            'nomorhp' => '081200000001',
            'email_verified_at' => now(),
        ]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->user->id, 'saldo_platform' => 0]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function accountPayload(array $overrides = []): array
    {
        return array_merge([
            'namalengkap' => 'Owner Lama',
            'email' => 'owner@example.com',
            'nomorhp' => '081200000001',
        ], $overrides);
    }

    public function test_settings_page_offers_the_current_password_fields(): void
    {
        $this->actingAs($this->user)
            ->get(route('owner.settings'))
            ->assertOk()
            ->assertSee('name="current_password"', false)
            ->assertSee('name="delete_password"', false);
    }

    public function test_name_and_phone_change_needs_no_password(): void
    {
        $this->actingAs($this->user)
            ->post(route('owner.settings.account'), $this->accountPayload([
                'namalengkap' => 'Owner Baru',
                'nomorhp' => '081200000002',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('owner.settings'));

        $this->assertSame('Owner Baru', $this->user->fresh()->namalengkap);
        $this->assertSame('081200000002', $this->user->fresh()->nomorhp);
    }

    public function test_email_change_without_current_password_is_refused_and_nothing_is_saved(): void
    {
        $this->actingAs($this->user)
            ->post(route('owner.settings.account'), $this->accountPayload([
                'namalengkap' => 'Owner Baru',
                'email' => 'attacker@example.com',
            ]))
            ->assertSessionHasErrors('current_password');

        $fresh = $this->user->fresh();
        $this->assertSame('owner@example.com', $fresh->email);
        $this->assertSame('Owner Lama', $fresh->namalengkap);
    }

    public function test_password_change_with_a_wrong_current_password_is_refused(): void
    {
        $this->actingAs($this->user)
            ->post(route('owner.settings.account'), $this->accountPayload([
                'current_password' => 'not-the-password',
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ]))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_email_change_with_current_password_resets_verification_and_mails_both_addresses(): void
    {
        Notification::fake();

        $this->actingAs($this->user)
            ->post(route('owner.settings.account'), $this->accountPayload([
                'email' => 'new-owner@example.com',
                'current_password' => 'password',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $this->user->fresh();
        $this->assertSame('new-owner@example.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
        Notification::assertSentTo($fresh, VerifyEmail::class);
        Notification::assertSentTo(
            new AnonymousNotifiable,
            AccountEmailChangedNotification::class,
            fn (AccountEmailChangedNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'owner@example.com'
        );
    }

    public function test_same_email_in_different_case_is_not_an_email_change(): void
    {
        Notification::fake();

        $this->actingAs($this->user)
            ->post(route('owner.settings.account'), $this->accountPayload(['email' => 'Owner@Example.com']))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($this->user->fresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_password_change_with_current_password_ends_other_sessions(): void
    {
        config(['session.driver' => 'database']);
        $oldRememberToken = 'old-remember-token';
        $this->user->forceFill(['remember_token' => $oldRememberToken])->save();
        DB::table('sessions')->insert([
            ['id' => 'other-device', 'user_id' => $this->user->id, 'payload' => '', 'last_activity' => time(), 'ip_address' => null, 'user_agent' => null],
            ['id' => 'someone-else', 'user_id' => $this->user->id + 1, 'payload' => '', 'last_activity' => time(), 'ip_address' => null, 'user_agent' => null],
        ]);

        $this->actingAs($this->user)
            ->post(route('owner.settings.account'), $this->accountPayload([
                'current_password' => 'password',
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('owner.settings'));

        $fresh = $this->user->fresh();
        $this->assertTrue(Hash::check('new-secret-123', $fresh->password));
        $this->assertNotSame($oldRememberToken, $fresh->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertDatabaseHas('sessions', ['id' => 'someone-else']);
        $this->assertAuthenticatedAs($fresh);
    }

    public function test_account_deletion_without_the_current_password_is_refused(): void
    {
        $this->actingAs($this->user)
            ->delete(route('owner.settings.account.delete'), ['confirm_account' => 'owner@example.com'])
            ->assertSessionHasErrors('delete_password');

        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_account_deletion_with_a_wrong_password_is_refused(): void
    {
        $this->actingAs($this->user)
            ->delete(route('owner.settings.account.delete'), [
                'confirm_account' => 'owner@example.com',
                'delete_password' => 'not-the-password',
            ])
            ->assertSessionHasErrors('delete_password');

        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }
}
