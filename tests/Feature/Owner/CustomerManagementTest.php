<?php

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\CustomerNote;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;
    protected Tenant $tenantA;
    protected User $ownerB;
    protected Tenant $tenantB;
    protected Service $serviceA;
    protected Schedule $scheduleA;

    protected function setUp(): void
    {
        parent::setUp();

        // Owner A — full profile so owner.profile middleware passes
        $this->ownerA = User::factory()->create([
            'role'              => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->tenantA = Tenant::create([
            'iduser'      => $this->ownerA->id,
            'namabisnis'  => 'Yoga Studio A',
            'jenisbisnis' => 'Kebugaran',
            'slug'        => 'yoga-studio-a',
            'alamat'      => 'Jl. Senopati No. 1',
            'nomorhp'     => '081111111111',
        ]);
        $this->serviceA = Service::create([
            'idtenant'    => $this->tenantA->id,
            'namalayanan' => 'Yoga Class',
            'harga'       => 150000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);
        $this->scheduleA = Schedule::factory()->create([
            'idtenant'  => $this->tenantA->id,
            'idlayanan' => $this->serviceA->id,
        ]);

        // Owner B
        $this->ownerB = User::factory()->create([
            'role'              => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->tenantB = Tenant::create([
            'iduser'      => $this->ownerB->id,
            'namabisnis'  => 'Barbershop B',
            'jenisbisnis' => 'Barbershop',
            'slug'        => 'barbershop-b',
            'alamat'      => 'Jl. Wijaya No. 5',
            'nomorhp'     => '082222222222',
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeBooking(Tenant $tenant, Service $service, Schedule $schedule, array $override = []): Booking
    {
        // If no explicit schedule provided, create a fresh one to avoid active_idschedule unique constraint
        return Booking::factory()->create(array_merge([
            'idtenant'       => $tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'namapelanggan'  => 'Budi Santoso',
            'email'          => 'budi@example.com',
            'nomorhp'        => '081111111111',
            'tanggalbooking' => now()->toDateString(),
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ], $override));
    }

    /**
     * Create a booking with a fresh schedule to avoid the active_idschedule unique constraint.
     * Use this when creating multiple bookings for the same customer/tenant in a single test.
     */
    private function makeBookingFreshSchedule(Tenant $tenant, Service $service, array $override = []): Booking
    {
        $sched = Schedule::factory()->create([
            'idtenant'  => $tenant->id,
            'idlayanan' => $service->id,
        ]);
        return $this->makeBooking($tenant, $service, $sched, $override);
    }

    private function makePayment(Tenant $tenant, Booking $booking, string $status = 'sukses', float $jumlah = 150000): Payment
    {
        return Payment::create([
            'idtenant'  => $tenant->id,
            'idbooking' => $booking->id,
            'tipe'      => 'booking',
            'jumlah'    => $jumlah,
            'status'    => $status,
            'metode'    => 'midtrans',
            'order_id'  => 'ORD-' . $booking->id . '-' . uniqid(),
        ]);
    }

    private function makeTenantBBooking(array $override = []): Booking
    {
        $svcB  = Service::create([
            'idtenant'    => $this->tenantB->id,
            'namalayanan' => 'Barber B',
            'harga'       => 50000,
            'durasi'      => 30,
            'is_active'   => true,
        ]);
        $schedB = Schedule::factory()->create([
            'idtenant'  => $this->tenantB->id,
            'idlayanan' => $svcB->id,
        ]);
        return Booking::factory()->create(array_merge([
            'idtenant'   => $this->tenantB->id,
            'idlayanan'  => $svcB->id,
            'idschedule' => $schedB->id,
            'status'     => 'paid',
        ], $override));
    }

    // ── Tests ──────────────────────────────────────────────────────────────

    /** 1. Owner can view their customer list */
    public function test_owner_can_view_customer_list(): void
    {
        $this->makeBooking($this->tenantA, $this->serviceA, $this->scheduleA, [
            'namapelanggan' => 'Budi Santoso',
            'email'         => 'budi@example.com',
        ]);

        $response = $this->actingAs($this->ownerA)->get(route('owner.customers'));

        $response->assertOk();
        $response->assertSee('Budi Santoso');
    }

    /** 2. Customer list only shows THIS tenant's customers */
    public function test_customer_list_is_tenant_scoped(): void
    {
        $this->makeBooking($this->tenantA, $this->serviceA, $this->scheduleA, [
            'email'         => 'alice@example.com',
            'namapelanggan' => 'Alice Owner A',
        ]);
        $this->makeTenantBBooking([
            'email'         => 'bob@example.com',
            'namapelanggan' => 'Bob Owner B',
        ]);

        $response = $this->actingAs($this->ownerA)->get(route('owner.customers'));

        $response->assertOk();
        $response->assertSee('Alice Owner A');
        $response->assertDontSee('Bob Owner B');
    }

    /** 3. Customer detail IDOR: Owner A cannot access Tenant B's customer */
    public function test_customer_detail_idor_is_blocked(): void
    {
        $this->makeTenantBBooking(['email' => 'victim@example.com', 'namapelanggan' => 'Victim']);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail', ['identifier' => 'victim@example.com']));

        $response->assertStatus(404);
    }

    /** 4. Customer detail returns correct JSON structure and data */
    public function test_customer_detail_returns_correct_data(): void
    {
        $booking = $this->makeBooking($this->tenantA, $this->serviceA, $this->scheduleA, [
            'email'         => 'detail@example.com',
            'namapelanggan' => 'Detail User',
        ]);
        $this->makePayment($this->tenantA, $booking);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail', ['identifier' => 'detail@example.com']));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Detail User'])
            ->assertJsonFragment(['email' => 'detail@example.com'])
            ->assertJsonStructure([
                'identifier', 'name', 'email', 'phone',
                'total_bookings', 'formatted_spent', 'avg_transaction',
                'last_booking', 'services_used', 'bookings', 'payments',
            ]);
    }

    /** 5. Server-side search works by customer name */
    public function test_server_side_search_by_name(): void
    {
        $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'alpha@example.com', 'namapelanggan' => 'Alpha Customer']);
        $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'beta@example.com', 'namapelanggan' => 'Beta Customer']);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers', ['search' => 'Alpha']));

        $response->assertOk();
        $response->assertSee('Alpha Customer');
        $response->assertDontSee('Beta Customer');
    }

    /** 6. Server-side search works by email */
    public function test_server_side_search_by_email(): void
    {
        $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'findme@example.com', 'namapelanggan' => 'Found']);
        $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'other@example.com', 'namapelanggan' => 'Other']);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers', ['search' => 'findme']));

        $response->assertOk();
        $response->assertSee('Found');
        $response->assertDontSee('Other');
    }

    /** 7. Multiple bookings from same email count as one customer with N bookings */
    public function test_total_bookings_per_customer_is_correct(): void
    {
        $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'multi@example.com']);
        $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'multi@example.com']);
        $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'multi@example.com']);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail', ['identifier' => 'multi@example.com']));

        $response->assertOk()->assertJsonFragment(['total_bookings' => 3]);
    }

    /** 8. Total spending uses payment.jumlah where status=sukses — NOT layanan.harga */
    public function test_total_spending_uses_payment_state_machine(): void
    {
        $b1 = $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, [
            'email'  => 'pay@example.com',
            'status' => 'paid',
        ]);
        $this->makePayment($this->tenantA, $b1, 'sukses', 120000);

        // Booking 2: gagal payment — must NOT be counted
        $b2 = $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, [
            'email'  => 'pay@example.com',
            'status' => 'cancelled',
        ]);
        $this->makePayment($this->tenantA, $b2, 'gagal', 150000);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail', ['identifier' => 'pay@example.com']));

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals(120000.0, $data['total_spent']);
    }

    /** 9. Booking history is correctly returned in customer detail */
    public function test_booking_history_is_correct(): void
    {
        $booking = $this->makeBooking($this->tenantA, $this->serviceA, $this->scheduleA, [
            'email'  => 'hist@example.com',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail', ['identifier' => 'hist@example.com']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data['bookings']);
        $this->assertEquals($booking->id, $data['bookings'][0]['id']);
        $this->assertEquals('completed', $data['bookings'][0]['status']);
    }

    /** 10. Payment history does not leak cross-tenant (same email, different tenants) */
    public function test_payment_history_does_not_leak_cross_tenant(): void
    {
        $bA = $this->makeBooking($this->tenantA, $this->serviceA, $this->scheduleA, ['email' => 'shared@example.com']);
        $this->makePayment($this->tenantA, $bA);

        $bB = $this->makeTenantBBooking(['email' => 'shared@example.com']);
        $this->makePayment($this->tenantB, $bB);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail', ['identifier' => 'shared@example.com']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data['bookings']);
        $this->assertCount(1, $data['payments']);
        $this->assertEquals($bA->id, $data['bookings'][0]['id']);
    }

    /** 11. Customer note can be saved and is scoped to tenant */
    public function test_customer_note_can_be_saved(): void
    {
        $this->makeBooking($this->tenantA, $this->serviceA, $this->scheduleA, [
            'email'         => 'note@example.com',
            'namapelanggan' => 'Note User',
        ]);

        $response = $this->actingAs($this->ownerA)
            ->postJson(route('owner.customers.note'), [
                'customer_identifier' => 'note@example.com',
                'notes'               => 'VIP customer, prefers morning slots.',
            ]);

        $response->assertOk()->assertJsonFragment(['success' => true]);
        $this->assertDatabaseHas('customer_notes', [
            'idtenant'            => $this->tenantA->id,
            'customer_identifier' => 'note@example.com',
            'notes'               => 'VIP customer, prefers morning slots.',
        ]);
    }

    /** 12. Customer note IDOR: Owner A cannot write note for Tenant B's customer */
    public function test_customer_note_idor_is_blocked(): void
    {
        $this->makeTenantBBooking(['email' => 'victim@example.com']);

        $response = $this->actingAs($this->ownerA)
            ->postJson(route('owner.customers.note'), [
                'customer_identifier' => 'victim@example.com',
                'notes'               => 'Malicious note',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('customer_notes', [
            'customer_identifier' => 'victim@example.com',
            'notes'               => 'Malicious note',
        ]);
    }

    /** 13. Customer without booking_code falls back to BKQ-{id} format gracefully */
    public function test_customer_without_booking_code_is_handled(): void
    {
        $b = $this->makeBooking($this->tenantA, $this->serviceA, $this->scheduleA, [
            'email'        => 'nocode@example.com',
            'booking_code' => null,
        ]);

        $response = $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail', ['identifier' => 'nocode@example.com']));

        $response->assertOk();
        $data = $response->json();
        $this->assertStringStartsWith('BKQ-', $data['bookings'][0]['code']);
    }

    /** 14. Empty customer list shows the correct empty state text */
    public function test_empty_customer_list_shows_empty_state(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('owner.customers'));

        $response->assertOk();
        $response->assertSee('Belum ada data customer');
    }

    /** 15. Pagination: page 2 returns 200 when there are more than 20 unique customers */
    public function test_pagination_is_present(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, [
                'email'         => "customer{$i}@example.com",
                'namapelanggan' => "Customer {$i}",
            ]);
        }

        $this->actingAs($this->ownerA)->get(route('owner.customers'))->assertOk();
        $this->actingAs($this->ownerA)->get(route('owner.customers', ['page' => 2]))->assertOk();
    }

    /** 16. Unauthenticated user is redirected to login */
    public function test_unauthenticated_cannot_access_customers(): void
    {
        $this->get(route('owner.customers'))->assertRedirect(route('login'));
    }

    /** 17. Detail endpoint without identifier returns 400 */
    public function test_detail_without_identifier_returns_error(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('owner.customers.detail'))
            ->assertStatus(400);
    }

    /** 18. Summary stats reflect correct totals (spending from sukses payments only) */
    public function test_summary_stats_are_correct(): void
    {
        $b1 = $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'c1@example.com']);
        $this->makePayment($this->tenantA, $b1, 'sukses', 100000);

        $b2 = $this->makeBookingFreshSchedule($this->tenantA, $this->serviceA, ['email' => 'c2@example.com']);
        $this->makePayment($this->tenantA, $b2, 'sukses', 200000);

        $response = $this->actingAs($this->ownerA)->get(route('owner.customers'));
        $response->assertOk();
        // Total spending = 300.000
        $response->assertSee('300.000');
    }
}
