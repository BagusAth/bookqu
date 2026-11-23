<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

/**
 * BQ-172 (FR-AUTH-009, D-029).
 */
class GoogleSignInTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogle(string $email, bool $verified = true, string $id = 'google-123'): void
    {
        Socialite::fake('google', (new GoogleUser)->map(['id' => $id, 'name' => 'Budi Google', 'email' => $email])
            ->setRaw(['email_verified' => $verified]));
    }

    public function test_existing_owner_with_verified_google_email_is_linked_and_signed_in(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'owner@example.com', 'role' => 'owner']);
        $this->fakeGoogle('owner@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_linking_an_unverified_account_removes_the_earlier_password_and_sessions(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'owner@example.com', 'role' => 'owner', 'remember_token' => 'old-token']);
        config(['session.driver' => 'database']);
        \DB::table('sessions')->insert(['id' => 'attacker', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->fakeGoogle('owner@example.com');

        $this->get(route('auth.google.callback'));

        $this->assertFalse($user->fresh()->hasPassword());
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'attacker']);
    }

    public function test_linking_a_verified_account_keeps_its_password(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'role' => 'owner']);
        $this->fakeGoogle('owner@example.com');

        $this->get(route('auth.google.callback'));

        $this->assertTrue($user->fresh()->hasPassword());
        $this->assertSame('google-123', $user->fresh()->google_id);
    }

    public function test_unverified_google_email_is_rejected_without_linking(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'role' => 'owner']);
        $this->fakeGoogle('owner@example.com', verified: false);

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_admin_cannot_sign_in_with_google(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);
        $this->fakeGoogle('admin@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull($admin->fresh()->google_id);
    }

    public function test_email_linked_to_another_google_account_is_rejected(): void
    {
        User::factory()->create(['email' => 'owner@example.com', 'role' => 'owner'])->forceFill(['google_id' => 'other'])->save();
        $this->fakeGoogle('owner@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_new_google_account_completes_business_data_and_gets_a_tenant_without_password(): void
    {
        Notification::fake();
        $this->fakeGoogle('baru@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('auth.google.complete'));
        $this->get(route('auth.google.complete'))->assertOk()->assertSee('baru@example.com')->assertDontSee('name="password"', false);

        $this->post(route('auth.google.complete.store'), [
            'name' => 'Budi Google',
            'nomorhp' => '081234567890',
            'nama_bisnis' => 'Studio Budi',
            'jenis_bisnis' => 'Studio Foto',
            'alamat' => 'Jl. Merdeka 1',
            'terms' => '1',
        ])->assertRedirect(route('owner.dashboard'));

        $user = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertFalse($user->hasPassword());
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Tenant::where('iduser', $user->id)->exists());
        Notification::assertNothingSent();
    }

    public function test_completion_without_pending_google_data_returns_to_login(): void
    {
        $this->post(route('auth.google.complete.store'), [
            'name' => 'X', 'nomorhp' => '081234567890', 'nama_bisnis' => 'Bisnis', 'jenis_bisnis' => 'Salon', 'alamat' => 'Jl. 1', 'terms' => '1',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, User::count());
    }

    public function test_google_only_account_cannot_sign_in_with_a_password(): void
    {
        User::factory()->create(['email' => 'owner@example.com', 'role' => 'owner', 'password' => null]);

        $this->post(route('login.store'), ['email' => 'owner@example.com', 'password' => 'anything123'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_google_only_owner_requests_password_link_and_is_signed_out(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'owner', 'password' => null]);
        Tenant::factory()->create(['iduser' => $user->id]);

        $this->actingAs($user)->post(route('owner.settings.account.password-link'))->assertRedirect(route('login'));

        $this->assertGuest();
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
