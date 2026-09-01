<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Service;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class P0SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_isolation_prevents_accessing_other_tenant_services()
    {
        $tenant1 = Tenant::factory()->create(['slug_usaha' => 'tenant1']);
        $service1 = Service::factory()->create(['idtenant' => $tenant1->id]);

        $tenant2 = Tenant::factory()->create(['slug_usaha' => 'tenant2']);
        $service2 = Service::factory()->create(['idtenant' => $tenant2->id]);

        // Accessing tenant1's service via tenant2's slug should fail or not show it
        $response = $this->get('/tenant2');
        $response->assertDontSee($service1->namalayanan);
        $response->assertSee($service2->namalayanan);
    }

    public function test_customer_idor_on_payment_page()
    {
        $tenant = Tenant::factory()->create(['slug_usaha' => 'tenant1']);
        $payment = Payment::factory()->create([
            'idtenant' => $tenant->id,
            'order_id' => 'ORD-12345',
        ]);

        // The URL requires the order_id, accessing it directly with the payment ID should 404 or fail if order_id is expected
        $response = $this->get('/tenant1/booking/payment/' . $payment->id);
        $response->assertStatus(404);

        $response2 = $this->get('/tenant1/booking/payment/' . $payment->order_id);
        $response2->assertStatus(200); // Assuming the page renders
    }
}
