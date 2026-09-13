<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Resource;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoreFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;
    protected Tenant $tenantA;
    protected Service $serviceA;
    protected Schedule $scheduleA;

    protected User $ownerB;
    protected Tenant $tenantB;
    protected Service $serviceB;
    protected Schedule $scheduleB;

    protected Plan $proPlan;
    protected string $tomorrow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $this->proPlan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            [
                'hargabulanan' => 100000,
                'maxlayanan'   => 10,
                'maxbooking'   => 500,
                'isunlimited'  => false,
            ]
        );

        // Tenant A Setup
        $this->ownerA = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenantA = Tenant::create([
            'iduser'      => $this->ownerA->id,
            'namabisnis'  => 'Studio A',
            'jenisbisnis' => 'Studio',
            'slug'        => 'studio-a',
            'alamat'      => 'Jalan A No 1',
            'nomorhp'     => '0811111111',
        ]);
        Subscription::create([
            'idtenant'       => $this->tenantA->id,
            'idplan'         => $this->proPlan->id,
            'status'         => 'active',
            'trial_berakhir' => now()->addDays(30),
        ]);
        $this->serviceA = Service::create([
            'idtenant'    => $this->tenantA->id,
            'namalayanan' => 'Photo Session A',
            'harga'       => 150000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);
        $this->scheduleA = Schedule::create([
            'idtenant'    => $this->tenantA->id,
            'idlayanan'   => $this->serviceA->id,
            'tanggal'     => $this->tomorrow,
            'jam_mulai'   => '10:00:00',
            'jam_selesai' => '10:59:00',
            'status'      => 'tersedia',
        ]);

        // Tenant B Setup
        $this->ownerB = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenantB = Tenant::create([
            'iduser'      => $this->ownerB->id,
            'namabisnis'  => 'Studio B',
            'jenisbisnis' => 'Studio',
            'slug'        => 'studio-b',
            'alamat'      => 'Jalan B No 2',
            'nomorhp'     => '0822222222',
        ]);
        Subscription::create([
            'idtenant'       => $this->tenantB->id,
            'idplan'         => $this->proPlan->id,
            'status'         => 'active',
            'trial_berakhir' => now()->addDays(30),
        ]);
        $this->serviceB = Service::create([
            'idtenant'    => $this->tenantB->id,
            'namalayanan' => 'Photo Session B',
            'harga'       => 250000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);
        $this->scheduleB = Schedule::create([
            'idtenant'    => $this->tenantB->id,
            'idlayanan'   => $this->serviceB->id,
            'tanggal'     => $this->tomorrow,
            'jam_mulai'   => '14:00:00',
            'jam_selesai' => '14:59:00',
            'status'      => 'tersedia',
        ]);
    }

    /**
     * 1. Tenant Isolation & Service Ownership
     * Owner A cannot view, update, delete, or toggle Owner B's service
     */
    public function test_service_ownership_and_tenant_isolation(): void
    {
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        // Cannot toggle service B
        $response = $this->post(route('owner.services.toggle', $this->serviceB->id));
        $response->assertStatus(404);

        // Cannot update service B
        $response = $this->put(route('owner.services.update', $this->serviceB->id), [
            'namalayanan' => 'Hacked Name',
            'harga'       => 1000,
            'durasi'      => 60,
            'is_active'   => 1,
        ]);
        $response->assertStatus(404);

        // Cannot delete service B
        $response = $this->delete(route('owner.services.destroy', $this->serviceB->id));
        $response->assertStatus(404);

        $this->assertDatabaseHas('services', [
            'id'          => $this->serviceB->id,
            'namalayanan' => 'Photo Session B',
        ]);
    }

    /**
     * 2. Schedule Ownership & Overlap Validation
     * Owner A cannot delete Tenant B's schedule slot
     */
    public function test_schedule_ownership(): void
    {
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->delete(route('owner.schedule.slots.destroy', $this->scheduleB->id));
        $response->assertStatus(404);

        $this->assertDatabaseHas('schedules', ['id' => $this->scheduleB->id]);
    }

    /**
     * 3. Duplicate & Overlapping Schedule Prevention
     * Attempting to create duplicate or overlapping slots within the same service and date is rejected
     */
    public function test_duplicate_and_overlapping_schedule_slot_prevention(): void
    {
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        // Slot 10:00 - 10:59 already exists.
        // Trying to bulk store 10:00 - 11:00 with 60 min interval must not create duplicate
        $response = $this->post(route('owner.schedule.bulk-store'), [
            'jenisslot'    => 'harian',
            'idlayanan'    => $this->serviceA->id,
            'tanggal'      => $this->tomorrow,
            'jammulai'     => '10:00',
            'jamselesai'   => '12:00',
            'intervalslot' => 60,
        ]);

        $response->assertRedirect('/owner/schedule');

        // Only 1 slot for 10:00 should exist, plus the 11:00 slot
        $slotsAt10 = Schedule::withoutGlobalScopes()
            ->where('idtenant', $this->tenantA->id)
            ->where('idlayanan', $this->serviceA->id)
            ->whereDate('tanggal', $this->tomorrow)
            ->where('jam_mulai', '10:00:00')
            ->count();

        $this->assertEquals(1, $slotsAt10);
    }

    /**
     * 4. Double Booking Concurrency Protection
     * Two customers attempting to book the exact same schedule slot:
     * First customer succeeds, second customer is rejected
     */
    public function test_double_booking_concurrency_protection(): void
    {
        // Mock Snap
        \Mockery::mock('alias:Midtrans\Snap', function ($mock) {
            $mock->shouldReceive('getSnapToken')->andReturn('mock-snap-token-123');
        });

        // Customer A books the slot
        $this->withSession([
            'booking' => [
                'tenant_id'   => $this->tenantA->id,
                'service_id'  => $this->serviceA->id,
                'schedule_id' => $this->scheduleA->id,
                'tanggal'     => $this->tomorrow,
                'jam'         => '10:00',
            ]
        ])->post('/studio-a/booking/checkout', [
            'namapelanggan' => 'Customer A',
            'email'         => 'customerA@example.com',
            'nomorhp'       => '081234567890',
        ]);

        $this->assertDatabaseHas('bookings', [
            'idschedule'    => $this->scheduleA->id,
            'namapelanggan' => 'Customer A',
        ]);

        // Customer B attempts to book the same slot
        $responseB = $this->withSession([
            'booking' => [
                'tenant_id'   => $this->tenantA->id,
                'service_id'  => $this->serviceA->id,
                'schedule_id' => $this->scheduleA->id,
                'tanggal'     => $this->tomorrow,
                'jam'         => '10:00',
            ]
        ])->post('/studio-a/booking/checkout', [
            'namapelanggan' => 'Customer B',
            'email'         => 'customerB@example.com',
            'nomorhp'       => '089876543210',
        ]);

        $responseB->assertRedirect('/studio-a/booking/time');
        $responseB->assertSessionHasErrors(['jam']);

        // Customer B must NOT have a booking created
        $this->assertDatabaseMissing('bookings', [
            'idschedule'    => $this->scheduleA->id,
            'namapelanggan' => 'Customer B',
        ]);
    }

    /**
     * 5. Booking Ownership & Authorization
     * Owner A cannot change status or access Owner B's booking
     */
    public function test_booking_ownership_and_idor_protection(): void
    {
        $bookingB = Booking::create([
            'idtenant'       => $this->tenantB->id,
            'idlayanan'      => $this->serviceB->id,
            'idschedule'     => $this->scheduleB->id,
            'namapelanggan'  => 'Pelanggan B',
            'email'          => 'pelangganb@example.com',
            'nomorhp'        => '0877777777',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '14:00:00',
            'status'         => 'paid',
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->patch(route('owner.bookings.status', $bookingB->id), [
            'status' => 'completed',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('paid', Booking::withoutGlobalScopes()->find($bookingB->id)->status);
    }

    /**
     * 6. Payment & Webhook Idempotency + Booking Synchronization
     * Webhook delivers settlement -> marks payment 'sukses' and booking 'paid'.
     * Duplicate webhook does not corrupt state or reprocess.
     */
    public function test_payment_and_webhook_idempotency_and_synchronization(): void
    {
        $orderId = 'BKG-TEST-' . time();
        $payment = Payment::create([
            'idtenant'       => $this->tenantA->id,
            'tipe'           => 'booking',
            'jumlah'         => 150000,
            'status'         => 'pending',
            'metode'         => 'midtrans',
            'order_id'       => $orderId,
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Test Customer',
            'email_pembayar' => 'customer@test.com',
            'hp_pembayar'    => '081234567',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Test Customer',
            'email'          => 'customer@test.com',
            'nomorhp'        => '081234567',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'pending',
        ]);

        $serverKey = config('midtrans.server_key');
        $grossAmount = '150000.00';
        $signature = hash('sha512', $orderId . '200' . $grossAmount . $serverKey);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => '200',
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
        ];

        // First delivery
        $response1 = $this->postJson(route('midtrans.webhook'), $payload);
        $response1->assertStatus(200);

        $this->assertEquals('sukses', $payment->fresh()->status);
        $this->assertEquals('paid', $booking->fresh()->status);

        // Duplicate delivery
        $response2 = $this->postJson(route('midtrans.webhook'), $payload);
        $response2->assertStatus(200);

        $this->assertEquals('sukses', $payment->fresh()->status);
        $this->assertEquals('paid', $booking->fresh()->status);
    }

    /**
     * 7. Customer CRM Isolation
     * Owner A CRM only sees customers of Tenant A, never Tenant B
     */
    public function test_customer_crm_tenant_isolation(): void
    {
        Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'namapelanggan'  => 'Customer Alpha',
            'email'          => 'alpha@domain.com',
            'nomorhp'        => '08111111111',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ]);

        Booking::create([
            'idtenant'       => $this->tenantB->id,
            'idlayanan'      => $this->serviceB->id,
            'idschedule'     => $this->scheduleB->id,
            'namapelanggan'  => 'Customer Beta',
            'email'          => 'beta@domain.com',
            'nomorhp'        => '08222222222',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '14:00:00',
            'status'         => 'paid',
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->get(route('owner.customers'));
        $response->assertStatus(200);
        $response->assertSee('Customer Alpha');
        $response->assertDontSee('Customer Beta');

        // Detail IDOR test: Owner A cannot request detail of Customer Beta
        $responseDetail = $this->get(route('owner.customers.detail', ['identifier' => 'beta@domain.com']));
        $responseDetail->assertStatus(404);
    }

    /**
     * 8. Voucher & Review Tenant Isolation
     */
    public function test_voucher_and_review_tenant_isolation(): void
    {
        $voucherB = Voucher::create([
            'idtenant'       => $this->tenantB->id,
            'code'           => 'DISC50B',
            'discount_type'  => 'percentage',
            'discount_value' => 50,
            'usage_limit'    => 10,
            'is_active'      => true,
            'start_date'     => now()->subDay(),
            'end_date'       => now()->addDays(7),
        ]);

        $bookingB = Booking::create([
            'idtenant'       => $this->tenantB->id,
            'idlayanan'      => $this->serviceB->id,
            'idschedule'     => $this->scheduleB->id,
            'namapelanggan'  => 'Reviewer B',
            'email'          => 'reviewer@b.com',
            'nomorhp'        => '082222222',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '14:00:00',
            'status'         => 'completed',
        ]);

        $reviewB = Review::create([
            'idtenant'      => $this->tenantB->id,
            'idbooking'     => $bookingB->id,
            'customer_name' => 'Reviewer B',
            'rating'        => 5,
            'comment'       => 'Great service B',
            'is_visible'    => true,
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        // Voucher B IDOR
        $response = $this->put(route('owner.vouchers.update', $voucherB->id), [
            'code'           => 'DISC50B-HACK',
            'discount_type'  => 'percentage',
            'discount_value' => 50,
            'usage_limit'    => 10,
            'is_active'      => 1,
            'start_date'     => now()->subDay()->format('Y-m-d'),
            'end_date'       => now()->addDays(7)->format('Y-m-d'),
        ]);
        $response->assertStatus(404);

        // Review B IDOR
        $response = $this->post(route('owner.reviews.reply', $reviewB->id), [
            'reply' => 'Unauthorized reply',
        ]);
        $response->assertStatus(404);
    }

    /**
     * 9. Staff / Resource Inactive Fulfillment Check
     * When a service has assigned staff/resources but all are inactive,
     * the customer cannot select or book that service
     */
    public function test_inactive_staff_or_resource_blocks_customer_booking(): void
    {
        $staff = Staff::create([
            'idtenant'  => $this->tenantA->id,
            'name'      => 'Therapist John',
            'role'      => 'Therapist',
            'is_active' => false, // Inactive
        ]);

        $this->serviceA->staff()->attach($staff->id);

        $this->assertFalse($this->serviceA->fresh()->hasActiveFulfillment());

        // Selecting this program must fail with an error
        $response = $this->post('/studio-a/booking/select-program', [
            'service_id' => $this->serviceA->id,
        ]);

        $response->assertSessionHasErrors(['service']);

        // Now activate the staff
        $staff->update(['is_active' => true]);
        $this->assertTrue($this->serviceA->fresh()->hasActiveFulfillment());

        // Now selecting this program succeeds
        $response2 = $this->post('/studio-a/booking/select-program', [
            'service_id' => $this->serviceA->id,
        ]);
        $response2->assertRedirect('/studio-a/booking/date');
    }

    /**
     * 10. Schedule Report Empty State: "Tidak ada data" instead of fake numbers
     */
    public function test_schedule_report_shows_no_data_when_empty(): void
    {
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->get(route('owner.schedule-report'));
        $response->assertStatus(200);

        // Since tenant A has 0 confirmed bookings, peak hours and peak days must display 'Tidak ada data'
        $response->assertSee('Tidak ada data');
        $response->assertDontSee('14:00 (Jam paling diminati)');
    }

    /**
     * 11. Customer can select available schedule
     */
    public function test_customer_can_select_available_schedule(): void
    {
        $this->assertTrue($this->scheduleA->isAvailable());

        $response = $this->withSession([
            'booking' => [
                'tenant_id'  => $this->tenantA->id,
                'service_id' => $this->serviceA->id,
                'tanggal'    => $this->tomorrow,
                'jam'        => null,
            ]
        ])->post('/studio-a/booking/select-time', [
            'schedule_id' => $this->scheduleA->id,
            'jam'         => '10:00',
        ]);

        $response->assertRedirect('/studio-a/booking/checkout');
        $this->assertEquals('10:00', session('booking.jam'));
        $this->assertEquals($this->scheduleA->id, session('booking.schedule_id'));
    }

    /**
     * 12. Customer cannot select booked schedule
     */
    public function test_customer_cannot_select_booked_schedule(): void
    {
        // Create active booking for scheduleA
        Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'namapelanggan'  => 'Occupant Customer',
            'email'          => 'occupant@test.com',
            'nomorhp'        => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ]);

        $this->assertFalse($this->scheduleA->fresh()->isAvailable());
        $this->assertEquals(Schedule::STATUS_BOOKED, $this->scheduleA->fresh()->getAvailabilityStatus());

        $response = $this->withSession([
            'booking' => [
                'tenant_id'  => $this->tenantA->id,
                'service_id' => $this->serviceA->id,
                'tanggal'    => $this->tomorrow,
                'jam'        => null,
            ]
        ])->post('/studio-a/booking/select-time', [
            'schedule_id' => $this->scheduleA->id,
            'jam'         => '10:00',
        ]);

        $response->assertRedirect('/studio-a/booking/time');
        $response->assertSessionHasErrors(['jam']);
    }

    /**
     * 13. Cancelled booking releases schedule slot back to available
     */
    public function test_cancelled_booking_releases_schedule_slot(): void
    {
        $booking = Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'namapelanggan'  => 'Temporary Customer',
            'email'          => 'temp@test.com',
            'nomorhp'        => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ]);

        $this->assertFalse($this->scheduleA->fresh()->isAvailable());

        // Cancel the booking
        $booking->update(['status' => 'cancelled']);

        // Schedule slot is immediately available again
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $this->scheduleA->fresh()->getAvailabilityStatus());
        $this->assertTrue($this->scheduleA->fresh()->isAvailable());

        // Another customer can now successfully select this time slot
        $response = $this->withSession([
            'booking' => [
                'tenant_id'  => $this->tenantA->id,
                'service_id' => $this->serviceA->id,
                'tanggal'    => $this->tomorrow,
                'jam'        => null,
            ]
        ])->post('/studio-a/booking/select-time', [
            'schedule_id' => $this->scheduleA->id,
            'jam'         => '10:00',
        ]);

        $response->assertRedirect('/studio-a/booking/checkout');
    }

    /**
     * 14. Owner only sees own tenant bookings in booking list
     */
    public function test_owner_only_sees_own_tenant_bookings(): void
    {
        Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'namapelanggan'  => 'Pelanggan Tenant A',
            'email'          => 'pelanggana@domain.com',
            'nomorhp'        => '081111111',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ]);

        Booking::create([
            'idtenant'       => $this->tenantB->id,
            'idlayanan'      => $this->serviceB->id,
            'idschedule'     => $this->scheduleB->id,
            'namapelanggan'  => 'Pelanggan Tenant B',
            'email'          => 'pelangganb@domain.com',
            'nomorhp'        => '082222222',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '14:00:00',
            'status'         => 'paid',
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->get(route('owner.bookings'));
        $response->assertStatus(200);
        $response->assertSee('Pelanggan Tenant A');
        $response->assertDontSee('Pelanggan Tenant B');
    }

    /**
     * 15. Calendar displays available schedule slots
     */
    public function test_calendar_displays_available_schedules(): void
    {
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->get(route('owner.calendar', [
            'view' => 'week',
            'date' => $this->tomorrow,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Photo Session A');
        $response->assertSee('Tersedia');
    }

    /**
     * 16. Calendar displays bookings
     */
    public function test_calendar_displays_bookings(): void
    {
        Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'namapelanggan'  => 'Calender Customer',
            'email'          => 'cal@domain.com',
            'nomorhp'        => '089999999',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->get(route('owner.calendar', [
            'view' => 'week',
            'date' => $this->tomorrow,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Calender Customer');
        $response->assertSee('Confirmed');
    }

    /**
     * 17. Calendar filters by service and status
     */
    public function test_calendar_filters_work(): void
    {
        $serviceA2 = Service::create([
            'idtenant'    => $this->tenantA->id,
            'namalayanan' => 'Special Class A2',
            'harga'       => 300000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);
        $scheduleA2 = Schedule::create([
            'idtenant'    => $this->tenantA->id,
            'idlayanan'   => $serviceA2->id,
            'tanggal'     => $this->tomorrow,
            'jam_mulai'   => '16:00:00',
            'jam_selesai' => '16:59:00',
            'status'      => 'tersedia',
        ]);

        Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $serviceA2->id,
            'idschedule'     => $scheduleA2->id,
            'namapelanggan'  => 'Special Student',
            'email'          => 'special@test.com',
            'nomorhp'        => '081233333',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '16:00:00',
            'status'         => 'pending',
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        // Filter by service A only -> should NOT see Special Student
        $responseService = $this->get(route('owner.calendar', [
            'view'       => 'day',
            'date'       => $this->tomorrow,
            'service_id' => $this->serviceA->id,
        ]));
        $responseService->assertStatus(200);
        $responseService->assertDontSee('Special Student');

        // Filter by status 'paid' only -> should NOT see Special Student who is 'pending'
        $responseStatus = $this->get(route('owner.calendar', [
            'view'   => 'day',
            'date'   => $this->tomorrow,
            'status' => 'paid',
        ]));
        $responseStatus->assertStatus(200);
        $responseStatus->assertDontSee('Special Student');
    }

    /**
     * 18. Calendar Today/Previous/Next and View navigation work
     */
    public function test_calendar_today_previous_next_navigation(): void
    {
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        // Day view
        $responseDay = $this->get(route('owner.calendar', [
            'view' => 'day',
            'date' => $this->tomorrow,
        ]));
        $responseDay->assertStatus(200);
        $responseDay->assertSee('Jadwal Harian');

        // Month view
        $responseMonth = $this->get(route('owner.calendar', [
            'view' => 'month',
            'date' => $this->tomorrow,
        ]));
        $responseMonth->assertStatus(200);
        $responseMonth->assertSee(Carbon::parse($this->tomorrow)->translatedFormat('F Y'));
    }

    /**
     * 19. Booking status and payment status are kept strictly separate
     */
    public function test_booking_status_and_payment_status_separation(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenantA->id,
            'tipe'           => 'booking',
            'jumlah'         => 150000,
            'status'         => 'sukses',
            'metode'         => 'midtrans',
            'order_id'       => 'BKG-STATUS-TEST-1',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Status Tester',
            'email_pembayar' => 'stat@test.com',
            'hp_pembayar'    => '0812345678',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Status Tester',
            'email'          => 'stat@test.com',
            'nomorhp'        => '0812345678',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'cancelled', // Booking is cancelled, while payment was sukses
        ]);

        // Booking status remains 'cancelled' and payment remains 'sukses'
        $this->assertEquals('cancelled', $booking->fresh()->status);
        $this->assertEquals('sukses', $payment->fresh()->status);
    }

    /**
     * 20. Schedule Report calculates utilization correctly with cancelled bookings excluded
     */
    public function test_schedule_report_calculates_utilization_correctly(): void
    {
        // Tenant A currently has 1 schedule (scheduleA). Let's create a second schedule
        $scheduleA2 = Schedule::create([
            'idtenant'    => $this->tenantA->id,
            'idlayanan'   => $this->serviceA->id,
            'tanggal'     => $this->tomorrow,
            'jam_mulai'   => '11:00:00',
            'jam_selesai' => '11:59:00',
            'status'      => 'tersedia',
        ]);

        // 1 confirmed booking on scheduleA
        Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $this->scheduleA->id,
            'namapelanggan'  => 'Valid Occupant',
            'email'          => 'valid@test.com',
            'nomorhp'        => '081234567',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ]);

        // 1 cancelled booking on scheduleA2 (should NOT count as booked)
        Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $this->serviceA->id,
            'idschedule'     => $scheduleA2->id,
            'namapelanggan'  => 'Cancelled Customer',
            'email'          => 'cancelled@test.com',
            'nomorhp'        => '087654321',
            'tanggalbooking' => $this->tomorrow,
            'jam'            => '11:00:00',
            'status'         => 'cancelled',
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $response = $this->get(route('owner.schedule-report'));
        $response->assertStatus(200);

        // Total slots: 2, Booked: 1, Available: 1, Utilization: 50%
        $response->assertSee('50%');
    }
}
