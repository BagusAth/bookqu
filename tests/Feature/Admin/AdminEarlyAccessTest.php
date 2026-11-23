<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\EarlyAccessSignup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Early access sign-ups in the admin portal (BQ-175, FR-ADMIN-006).
 */
class AdminEarlyAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    public function test_admin_sees_sign_ups_newest_first_with_whatsapp_link(): void
    {
        EarlyAccessSignup::factory()->create(['business_name' => 'Salon Lama', 'created_at' => now()->subDays(3)]);
        EarlyAccessSignup::factory()->create(['business_name' => 'Barber Baru', 'whatsapp_number' => '081234567890']);

        $this->actingAs($this->admin())->get('/admin/early-access')
            ->assertOk()
            ->assertSeeInOrder(['Barber Baru', 'Salon Lama'])
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('2 pendaftar');
    }

    public function test_admin_searches_by_name_business_or_email(): void
    {
        EarlyAccessSignup::factory()->create(['full_name' => 'Bowo', 'business_name' => 'Bowo Barbershop', 'email' => 'bowo@example.com']);
        EarlyAccessSignup::factory()->create(['full_name' => 'Sari', 'business_name' => 'Sari Salon', 'email' => 'sari@example.com']);

        $admin = $this->admin();
        foreach (['Bowo', 'barbershop', 'bowo@example'] as $term) {
            $this->actingAs($admin)->get('/admin/early-access?q='.urlencode($term))
                ->assertOk()->assertSee('bowo@example.com')->assertDontSee('sari@example.com');
        }

        $this->actingAs($admin)->get('/admin/early-access?q='.urlencode('%'))
            ->assertOk()->assertSee('Tidak ada pendaftar yang cocok');
    }

    public function test_dashboard_shows_total_and_last_week_sign_ups(): void
    {
        EarlyAccessSignup::factory()->count(2)->create();
        EarlyAccessSignup::factory()->create(['created_at' => now()->subDays(10)]);

        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Pendaftar early access')
            ->assertSee('2 dalam 7 hari terakhir')
            ->assertSee(route('admin.early-access'), false);
    }

    public function test_owner_cannot_open_the_list(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);

        $this->actingAs($owner)->get('/admin/early-access')->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/admin/early-access')->assertRedirect('/login');
    }
}
