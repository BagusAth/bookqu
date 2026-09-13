<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_view_admin_dashboard_with_metrics(): void
    {
        $tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        $plan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            ['hargabulanan' => 100000, 'maxlayanan' => 10, 'maxbooking' => 500, 'isunlimited' => false]
        );

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $plan->id,
            'status' => 'active',
        ]);

        Payment::create([
            'idtenant' => $tenant->id,
            'idplan' => $plan->id,
            'tipe' => 'subscription',
            'jumlah' => 100000,
            'status' => 'sukses',
            'metode' => 'midtrans',
            'order_id' => 'ORD-SUB-TEST-1',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Superadmin');
        $response->assertSee($tenant->namabisnis);
    }

    public function test_owner_is_forbidden_from_admin_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }
}
