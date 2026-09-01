<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Service;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class P0SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_context_is_request_scoped_and_cleared()
    {
        $tenant1 = Tenant::factory()->create(['slug' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['slug' => 'tenant2']);

        // First request
        $this->get('/tenant1');
        $this->assertNull(app(TenantContext::class)->getTenantId(), 'Tenant context should be cleared after request 1');

        // Second request
        $this->get('/tenant2');
        $this->assertNull(app(TenantContext::class)->getTenantId(), 'Tenant context should be cleared after request 2');
    }

    public function test_tenant_scope_fails_closed_without_context()
    {
        $tenant = Tenant::factory()->create();
        Service::factory()->count(3)->create(['idtenant' => $tenant->id]);

        app(TenantContext::class)->clear();
        $this->assertEquals(0, Service::count(), 'TenantScope should return 0 records when TenantContext is empty');
        
        app(TenantContext::class)->setTenantId($tenant->id);
        $this->assertEquals(3, Service::count());
    }

    public function test_owner_idor_is_blocked()
    {
        $owner1 = User::factory()->create(['role' => 'owner']);
        $tenant1 = Tenant::factory()->create(['iduser' => $owner1->id, 'slug' => 'owner1-tenant']);
        $booking1 = Booking::factory()->create(['idtenant' => $tenant1->id]);

        $owner2 = User::factory()->create(['role' => 'owner']);
        $tenant2 = Tenant::factory()->create(['iduser' => $owner2->id, 'slug' => 'owner2-tenant']);
        $booking2 = Booking::factory()->create(['idtenant' => $tenant2->id]);

        $this->actingAs($owner1);

        // Owner 1 accessing their own booking
        $response = $this->patch("/owner/bookings/{$booking1->id}/status", ['status' => 'completed']);
        $response->assertSessionHasNoErrors();

        // Owner 1 trying to access Owner 2's booking
        $response2 = $this->patch("/owner/bookings/{$booking2->id}/status", ['status' => 'completed']);
        $response2->assertSessionHasErrors(['error' => 'Akses tidak diizinkan.']);
    }

    public function test_customer_payment_idor_is_blocked_using_order_id()
    {
        $tenant = Tenant::factory()->create(['slug' => 'tenant1']);
        $payment1 = Payment::factory()->create(['idtenant' => $tenant->id, 'order_id' => 'ORD-123']);
        $payment2 = Payment::factory()->create(['idtenant' => $tenant->id, 'order_id' => 'ORD-456']);

        // Directly using ID should fail if route is bound to order_id
        $response = $this->get("/tenant1/booking/payment/{$payment1->id}");
        $response->assertStatus(404);

        // Using proper order_id should pass
        $response = $this->get("/tenant1/booking/payment/{$payment1->order_id}");
        $response->assertStatus(200);
    }

    public function test_customer_booking_management_idor_requires_secure_token()
    {
        $tenant = Tenant::factory()->create(['slug' => 'tenant1']);
        $booking = Booking::factory()->create([
            'idtenant' => $tenant->id,
            'booking_code' => 'BKQ-123456',
            'cancellation_token' => 'secure-token-123',
            'status' => 'paid'
        ]);

        // Accessing without token
        $response = $this->get("/manage/{$booking->booking_code}");
        $response->assertStatus(403);

        // Accessing with wrong token
        $response = $this->get("/manage/{$booking->booking_code}?token=wrong-token");
        $response->assertStatus(403);

        // Accessing with correct token
        $response = $this->get("/manage/{$booking->booking_code}?token=secure-token-123");
        $response->assertStatus(200);
    }

    public function test_late_webhook_cannot_reactivate_cancelled_booking()
    {
        $tenant = Tenant::factory()->create();
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'status' => 'pending']);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id, 'idpayment' => $payment->id, 'status' => 'cancelled']);

        $service = new \App\Services\MidtransPaymentService();
        $service->syncStatus($payment->order_id, 'booking', 'settlement', 'accept', 'bank_transfer');

        $booking->refresh();
        $payment->refresh();

        $this->assertEquals('cancelled', $booking->status, 'Booking must remain cancelled on late settlement webhook');
    }

    public function test_duplicate_webhook_is_idempotent()
    {
        $tenant = Tenant::factory()->create();
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'status' => 'pending']);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id, 'idpayment' => $payment->id, 'status' => 'pending']);

        $service = new \App\Services\MidtransPaymentService();
        
        // First webhook
        $service->syncStatus($payment->order_id, 'booking', 'settlement', 'accept', 'bank_transfer');
        $this->assertEquals('paid', $booking->fresh()->status);

        // Second webhook (Duplicate)
        $service->syncStatus($payment->order_id, 'booking', 'settlement', 'accept', 'bank_transfer');
        $this->assertEquals('paid', $booking->fresh()->status);
    }
}
