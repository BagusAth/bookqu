<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'nomorhp' => '08123456789',
            'nama_bisnis' => 'Klinik Sehat',
            'jenis_bisnis' => 'Kesehatan',
            'alamat' => 'Jl. Sudirman No. 123',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ]);

        $this->assertAuthenticated();
        // Since register redirects to email verification notice
        $response->assertRedirect('/email/verify');
        
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'namalengkap' => 'Test Owner',
            'role' => 'owner',
        ]);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'owner2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'owner'
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        
        // If unverified, should redirect to verification notice
        if (!$user->hasVerifiedEmail()) {
            $response->assertRedirect('/email/verify');
        } else {
            $response->assertRedirect('/owner/dashboard');
        }
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_user_can_verify_email_via_signed_url_even_when_unauthenticated(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'role' => 'owner',
        ]);

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Akun berhasil diaktivasi. Silakan login.');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_cannot_verify_email_with_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'role' => 'owner',
        ]);

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash-string']
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_register_view_contains_link_to_terms_and_conditions(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee(route('terms-conditions'));
    }

    public function test_login_screen_contains_link_to_forgot_password(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee(route('password.request'));
    }

    public function test_welcome_page_cta_for_unverified_owner_points_to_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'role'              => 'owner',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('verification.notice'));
    }

    public function test_welcome_page_cta_for_verified_owner_points_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role'              => 'owner',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee(url('/owner/dashboard'));
    }

    public function test_login_failure_preserves_remember_me_checkbox_value(): void
    {
        $user = User::factory()->create([
            'email' => 'remember-test@example.com',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
            'remember' => 'on',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $response->assertSessionHasInput('remember', 'on');
        $this->assertEquals('on', session()->getOldInput('remember'));
    }

    public function test_verified_owner_visiting_verification_notice_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role'              => 'owner',
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));
        $response->assertRedirect(route('owner.dashboard'));
    }
}
