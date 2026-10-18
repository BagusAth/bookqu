<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Lupa Password');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'role'  => 'owner',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_link_fails_for_nonexistent_email(): void
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_screen_can_be_rendered_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'role'  => 'owner',
        ]);

        $token = Password::createToken($user);

        $response = $this->get('/reset-password/' . $token . '?email=' . urlencode($user->email));

        $response->assertStatus(200);
        $response->assertSee('Atur Ulang Password');
        $response->assertSee($token);
        $response->assertSee($user->email);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'owner@example.com',
            'password' => Hash::make('old-password123'),
            'role'     => 'owner',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'owner@example.com',
            'password' => Hash::make('old-password123'),
            'role'     => 'owner',
        ]);

        $response = $this->from('/reset-password/invalid-token')->post('/reset-password', [
            'token'                 => 'invalid-token',
            'email'                 => $user->email,
            'password'              => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect('/reset-password/invalid-token');
        $response->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_unmatched_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'role'  => 'owner',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_cannot_be_reset_with_short_password(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'role'  => 'owner',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_reset_password_link_fails_for_invalid_email_format(): void
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_link_request_is_throttled_on_rapid_successive_attempts(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'owner-throttle@example.com',
            'role'  => 'owner',
        ]);

        // First attempt succeeds
        $firstResponse = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);
        $firstResponse->assertSessionHas('status');

        // Immediate second attempt within 60s broker throttle window fails with error
        $secondResponse = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $user->email,
        ]);
        $secondResponse->assertRedirect('/forgot-password');
        $secondResponse->assertSessionHasErrors('email');
    }

    public function test_user_can_login_with_new_password_after_reset_and_old_password_fails(): void
    {
        $user = User::factory()->create([
            'email'    => 'owner-login@example.com',
            'password' => Hash::make('old-password-123'),
            'role'     => 'owner',
        ]);

        $token = Password::createToken($user);

        $resetResponse = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'brand-new-secret-456',
            'password_confirmation' => 'brand-new-secret-456',
        ]);

        $resetResponse->assertRedirect(route('login'));

        // Old password must fail
        $oldLogin = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'old-password-123',
        ]);
        $this->assertGuest();

        // New password must succeed
        $newLogin = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'brand-new-secret-456',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_password_screen_can_be_rendered_without_email_query_param(): void
    {
        $response = $this->get('/reset-password/sample-token-without-email');

        $response->assertStatus(200);
        $response->assertSee('Atur Ulang Password');
        $response->assertSee('sample-token-without-email');
    }

    public function test_password_cannot_be_reset_with_expired_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'owner-expired@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'owner',
        ]);

        $token = Password::createToken($user);

        // Travel 61 minutes into the future (broker default expire is 60 mins)
        $this->travel(61)->minutes();

        $response = $this->from('/reset-password/' . $token)->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect('/reset-password/' . $token);
        $response->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }
}
