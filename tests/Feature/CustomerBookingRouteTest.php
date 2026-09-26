<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingRouteTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'owner']);
        
        $this->tenant = Tenant::create([
            'iduser' => $user->id,
            'namabisnis' => 'Test Business',
            'jenisbisnis' => 'Retail',
            'alamat' => 'Test Address',
            'nomorhp' => '081234567890',
            'slug' => 'test-business',
            'custom_domain' => 'test-business.com'
        ]);
    }

    public function test_custom_domain_routing_resolves_tenant()
    {
        $response = $this->withHeaders([
            'Host' => 'test-business.com',
        ])->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('tenant');
        
        $viewTenant = $response->viewData('tenant');
        $this->assertEquals($this->tenant->id, $viewTenant->id);
    }

    public function test_subdirectory_routing_resolves_tenant()
    {
        $response = $this->get('/test-business');

        $response->assertStatus(200);
        $response->assertViewHas('tenant');
        
        $viewTenant = $response->viewData('tenant');
        $this->assertEquals($this->tenant->id, $viewTenant->id);
    }

    public function test_invalid_custom_domain_returns_404_or_handled()
    {
        $response = $this->withHeaders([
            'Host' => 'unknown-domain.com',
        ])->get('/');

        $response->assertStatus(404);
    }

    public function test_invalid_subdirectory_returns_404()
    {
        $response = $this->get('/unknown-slug');
        $response->assertStatus(404);
    }
}
