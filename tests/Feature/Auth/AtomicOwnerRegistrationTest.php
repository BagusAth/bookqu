<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * BQ-112: registration creates the user, tenant and trial together or not at all, and owner
 * pages tell the owner when an asynchronous request hits an expired session.
 */
class AtomicOwnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function registrationPayload(): array
    {
        return [
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'nomorhp' => '08123456789',
            'nama_bisnis' => 'Klinik Sehat',
            'jenis_bisnis' => 'Kesehatan',
            'alamat' => 'Jl. Sudirman No. 123',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ];
    }

    public function test_a_failing_trial_rolls_back_the_user_and_tenant(): void
    {
        Notification::fake();
        $this->mock(CreateTrialSubscription::class)
            ->shouldReceive('execute')
            ->once()
            ->andThrow(new RuntimeException('trial store unavailable'));

        $this->post('/register', $this->registrationPayload())->assertServerError();

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        Notification::assertNothingSent();
    }

    public function test_successful_registration_creates_user_tenant_and_trial_and_sends_verification(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationPayload())->assertRedirect('/email/verify');

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::withoutGlobalScopes()->where('iduser', $user->id)->firstOrFail();
        $this->assertSame('klinik-sehat', $tenant->slug);
        $this->assertDatabaseHas('subscriptions', ['idtenant' => $tenant->id]);
        $this->assertAuthenticatedAs($user);
        $this->assertSame($tenant->id, session('current_tenant_id'));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_owner_pages_carry_the_session_expiry_guard(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        Tenant::factory()->create(['iduser' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('id="session-expired-dialog"', false)
            ->assertSee('data-session-guard', false)
            ->assertSee('Sesi Anda telah berakhir');
    }
}
