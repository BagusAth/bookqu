<?php

namespace Tests\Feature\Owner;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerSidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'My Booking Business',
            'jenisbisnis' => 'Klinik',
            'slug' => 'my-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
            'deskripsi' => 'Testing business',
        ]);
    }

    public function test_owner_can_access_all_expanded_sidebar_pages(): void
    {
        $routes = [
            '/owner/dashboard',
            '/owner/calendar',
            '/owner/schedule',
            '/owner/bookings',
            '/owner/schedule-report',
            '/owner/categories',
            '/owner/services',
            '/owner/staff-resources',
            '/owner/additional-items',
            '/owner/vouchers',
            '/owner/reviews',
            '/owner/customers',
            '/owner/settings/business',
            '/owner/settings/appearance',
            '/owner/settings/payment-setting',
            '/owner/settings/assets',
            '/owner/settings/balance',
            '/owner/settings/integrations',
            '/owner/subscription',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->user)->get($route);
            $response->assertStatus(200);
            $response->assertSee('BookQu');
            $response->assertSee('Admin Portal');
            $response->assertSee('BOOKING');
            $response->assertSee('MASTER DATA');
            $response->assertSee('CUSTOMER');
            $response->assertSee('SETTING');
        }
    }
}
