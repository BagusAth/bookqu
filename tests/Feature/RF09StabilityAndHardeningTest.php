<?php

namespace Tests\Feature;

use App\Actions\Booking\CancelBooking;
use App\Actions\Payment\ExpirePayment;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Refund;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RF09StabilityAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Tenant $tenant;
    protected Service $service;
    protected Schedule $schedule;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->plan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            [
                'hargabulanan' => 100000,
                'maxlayanan'   => 10,
                'maxbooking'   => 500,
                'isunlimited'  => false,
            ]
        );

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::create([
            'iduser'      => $this->owner->id,
            'namabisnis'  => 'Salon Cantik',
            'jenisbisnis' => 'Salon',
            'slug'        => 'salon-cantik',
            'alamat'      => 'Jl. Mawar No. 12',
            'nomorhp'     => '081234567890',
        ]);

        Subscription::create([
            'idtenant'       => $this->tenant->id,
            'idplan'         => $this->plan->id,
            'status'         => 'active',
            'trial_berakhir' => now()->addDays(30),
        ]);

        $this->service = Service::create([
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Potong Rambut',
            'harga'       => 50000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);

        $this->schedule = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d'),
            'jam_mulai'   => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status'      => 'tersedia',
        ]);
    }

    /**
     * P0-01: Timezone configuration and Carbon handling
     */
    public function test_p0_01_timezone_is_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    /**
     * P0-02 & P0-03: Payment expiry sets payment to gagal, cancels booking, and frees slot
     */
    public function test_p0_02_payment_expiry_lifecycle(): void
    {
        $payment = Payment::create([
            'idtenant'     => $this->tenant->id,
            'order_id'     => 'ORDER-EXP-001',
            'jumlah'       => 50000,
            'tipe'         => 'booking',
            'metode'       => 'midtrans',
            'status'       => 'pending',
            'snap_token'   => 'fake-snap-token',
            'manage_token' => Booking::generateSecureToken(),
            'expired_at'   => Carbon::now('Asia/Jakarta')->subMinute(),
        ]);

        $booking = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $this->schedule->id,
            'idpayment'          => $payment->id,
            'namapelanggan'      => 'Budi Santoso',
            'nomorhp'            => '08123456789',
            'email'              => 'budi@example.com',
            'tanggalbooking'     => $this->schedule->tanggal,
            'jam'                => $this->schedule->jam_mulai,
            'status'             => 'pending',
            'booking_code'       => 'BK-EXP-001',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
        ]);

        $this->assertFalse($this->schedule->isAvailable());
        $this->assertSame(\App\Domain\Schedule\AvailabilityRules::STATUS_BOOKED, $this->schedule->getAvailabilityStatus());

        $expireAction = app(ExpirePayment::class);
        $result = $expireAction->execute($payment);

        $this->assertIsArray($result);
        $this->assertSame('gagal', $result['status']);
        $payment->refresh();
        $booking->refresh();
        $this->schedule->refresh();

        // Payment status must be 'gagal' (database enum), not 'kadaluarsa'
        $this->assertSame('gagal', $payment->status);
        $this->assertTrue($payment->isExpired());

        // Booking status must be 'cancelled'
        $this->assertSame('cancelled', $booking->status);

        // Schedule slot must be released and available again
        $this->assertTrue($this->schedule->isAvailable());
        $this->assertSame(\App\Domain\Schedule\AvailabilityRules::STATUS_AVAILABLE, $this->schedule->getAvailabilityStatus());
    }

    /**
     * P0-04: Active booking definition consistency across states and grace period
     */
    public function test_p0_04_active_booking_definition_semantics(): void
    {
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_PAID));
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_COMPLETED));
        $this->assertFalse(BookingState::occupiesSlot(BookingState::STATUS_CANCELLED));

        // Pending created 5 minutes ago (< 15 min) -> occupies slot
        $recent = Carbon::now('Asia/Jakarta')->subMinutes(5);
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $recent, 15));

        // Pending created 20 minutes ago (> 15 min) -> does not occupy slot
        $expired = Carbon::now('Asia/Jakarta')->subMinutes(20);
        $this->assertFalse(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $expired, 15));
    }

    /**
     * P1-01: Token scope isolation
     */
    public function test_p1_01_token_scope_separation(): void
    {
        $booking = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $this->schedule->id,
            'namapelanggan'      => 'Ahmad Dani',
            'nomorhp'            => '08123456780',
            'email'              => 'ahmad@example.com',
            'tanggalbooking'     => $this->schedule->tanggal,
            'jam'                => $this->schedule->jam_mulai,
            'status'             => 'paid',
            'booking_code'       => 'BK-TOKEN-001',
            'cancellation_token' => 'cancel-token-xyz',
            'reschedule_token'   => 'resched-token-xyz',
        ]);

        // 1. Cancellation token cannot be used to reschedule
        $rescheduleAttempt = $this->get('/manage/' . $booking->booking_code . '/reschedule?token=' . $booking->cancellation_token);
        $rescheduleAttempt->assertStatus(403);

        // 2. Reschedule token cannot be used to cancel
        $cancelAttempt = $this->post('/manage/' . $booking->booking_code . '/cancel?token=' . $booking->reschedule_token);
        $cancelAttempt->assertStatus(403);

        // 3. Invalid token returns 403 on all actions
        $invalidCancel = $this->post('/manage/' . $booking->booking_code . '/cancel?token=invalid-token');
        $invalidCancel->assertStatus(403);

        $invalidResched = $this->get('/manage/' . $booking->booking_code . '/reschedule?token=invalid-token');
        $invalidResched->assertStatus(403);

        // 4. Token from another booking is rejected (create separate slot to respect unique_active_booking_slot)
        $otherSchedule = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $this->schedule->tanggal,
            'jam_mulai'   => '13:00:00',
            'jam_selesai' => '14:00:00',
            'status'      => 'tersedia',
        ]);

        $otherBooking = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $otherSchedule->id,
            'namapelanggan'      => 'Other User',
            'nomorhp'            => '08123456781',
            'email'              => 'other@example.com',
            'tanggalbooking'     => $this->schedule->tanggal,
            'jam'                => $otherSchedule->jam_mulai,
            'status'             => 'paid',
            'booking_code'       => 'BK-TOKEN-002',
            'cancellation_token' => 'other-cancel-token',
            'reschedule_token'   => 'other-resched-token',
        ]);

        $crossTokenAttempt = $this->get('/manage/' . $booking->booking_code . '/reschedule?token=' . $otherBooking->reschedule_token);
        $crossTokenAttempt->assertStatus(403);

        // 5. Valid token allows view
        $viewAllowed = $this->get('/manage/' . $booking->booking_code . '?token=' . $booking->cancellation_token);
        $viewAllowed->assertStatus(200);
    }

    /**
     * P1-03: Owner cancellation policy vs customer cancellation policy
     */
    public function test_p1_03_owner_cancellation_does_not_create_automatic_refund(): void
    {
        $payment = Payment::create([
            'idtenant'     => $this->tenant->id,
            'order_id'     => 'ORDER-CANCEL-001',
            'jumlah'       => 50000,
            'tipe'         => 'booking',
            'metode'       => 'midtrans',
            'status'       => 'sukses',
            'snap_token'   => 'fake-snap-token',
            'manage_token' => Booking::generateSecureToken(),
        ]);

        $booking = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $this->schedule->id,
            'idpayment'          => $payment->id,
            'namapelanggan'      => 'Citra Kirana',
            'nomorhp'            => '08123456782',
            'email'              => 'citra@example.com',
            'tanggalbooking'     => $this->schedule->tanggal,
            'jam'                => $this->schedule->jam_mulai,
            'status'             => 'paid',
            'booking_code'       => 'BK-CANCEL-OWNER',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
        ]);

        $cancelAction = app(CancelBooking::class);

        // When actor is owner
        $cancelAction->execute($booking, 'owner', 'Dibatalkan oleh owner toko');

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);

        // No refund record created for owner cancellation
        $this->assertDatabaseMissing('refunds', [
            'booking_id' => $booking->id,
        ]);
    }

    /**
     * P1-03: Customer cancellation on paid booking DOES create a refund record
     */
    public function test_p1_03_customer_cancellation_creates_pending_refund(): void
    {
        $payment = Payment::create([
            'idtenant'     => $this->tenant->id,
            'order_id'     => 'ORDER-CANCEL-002',
            'jumlah'       => 50000,
            'tipe'         => 'booking',
            'metode'       => 'midtrans',
            'status'       => 'sukses',
            'snap_token'   => 'fake-snap-token',
            'manage_token' => Booking::generateSecureToken(),
        ]);

        // Booking 3 days in the future (within cancellation policy)
        $futureDate = Carbon::now('Asia/Jakarta')->addDays(3)->format('Y-m-d');
        $futureSchedule = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $futureDate,
            'jam_mulai'   => '14:00:00',
            'jam_selesai' => '15:00:00',
            'status'      => 'tersedia',
        ]);

        $booking = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $futureSchedule->id,
            'idpayment'          => $payment->id,
            'namapelanggan'      => 'Dewi Lestari',
            'nomorhp'            => '08123456783',
            'email'              => 'dewi@example.com',
            'tanggalbooking'     => $futureDate,
            'jam'                => $futureSchedule->jam_mulai,
            'status'             => 'paid',
            'booking_code'       => 'BK-CANCEL-CUST',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
        ]);

        $cancelAction = app(CancelBooking::class);

        // When actor is customer
        $cancelAction->execute($booking, 'customer', 'Berhalangan hadir');

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);

        // Pending refund record created
        $this->assertDatabaseHas('refunds', [
            'booking_id' => $booking->id,
            'status'     => 'pending',
            'jumlah'     => 50000,
        ]);
    }

    /**
     * P1-04: Checkout schedule and date validation consistency
     */
    public function test_p1_04_checkout_validates_schedule_date_match(): void
    {
        $differentDate = Carbon::tomorrow('Asia/Jakarta')->addDays(2)->format('Y-m-d');

        // Session indicates booking for $differentDate, but schedule id is on tomorrow
        $response = $this->withSession([
            'booking' => [
                'tenant_id'    => $this->tenant->id,
                'service_id'   => $this->service->id,
                'tanggal'      => $differentDate,
                'jam'          => ['10:00'],
                'schedule_ids' => [$this->schedule->id],
            ],
        ])->get('/' . $this->tenant->slug . '/booking/checkout');

        // Should redirect back to time selection because schedule date does not match selectedDate
        $response->assertRedirect('/' . $this->tenant->slug . '/booking/time');
        $response->assertSessionHasErrors('jam');
    }

    /**
     * Section 8: End-to-End Customer Booking Integration Test
     */
    public function test_section_8_end_to_end_customer_booking_flow(): void
    {
        // 1. Customer visits tenant public page
        $resStep1 = $this->get('/' . $this->tenant->slug);
        $resStep1->assertStatus(200);
        $resStep1->assertSee($this->service->namalayanan);

        // 2. Customer selects service via POST
        $resStep2 = $this->post('/' . $this->tenant->slug . '/booking/select-program', [
            'service_id' => $this->service->id,
        ]);
        $resStep2->assertRedirect('/' . $this->tenant->slug . '/booking/date');

        // 3. Customer views date selection
        $resStep3 = $this->get('/' . $this->tenant->slug . '/booking/date');
        $resStep3->assertStatus(200);

        // 4. Customer selects date via POST
        $resStep4 = $this->post('/' . $this->tenant->slug . '/booking/select-date', [
            'tanggal' => $this->schedule->tanggal->format('Y-m-d'),
        ]);
        $resStep4->assertRedirect('/' . $this->tenant->slug . '/booking/time');

        // 5. Customer views time slots & selects a slot via POST
        $resStep5 = $this->get('/' . $this->tenant->slug . '/booking/time');
        $resStep5->assertStatus(200);
        $resStep5->assertSee('10:00');

        $resStep5Select = $this->post('/' . $this->tenant->slug . '/booking/select-time', [
            'jam'          => ['10:00'],
            'schedule_ids' => [$this->schedule->id],
        ]);
        $resStep5Select->assertRedirect('/' . $this->tenant->slug . '/booking/checkout');

        // 6. Customer views checkout
        $resStep6 = $this->get('/' . $this->tenant->slug . '/booking/checkout');
        $resStep6->assertStatus(200);
        $resStep6->assertSee('Potong Rambut');

        // 7. Customer submits checkout form
        $postData = [
            'namapelanggan' => 'Eko Prasetyo',
            'nomorhp'       => '081299998888',
            'email'         => 'eko@example.com',
            'catatan'       => 'Mohon tepat waktu ya',
        ];

        $resStep7 = $this->post('/' . $this->tenant->slug . '/booking/checkout', $postData);
        $resStep7->assertStatus(302);

        // 8. Verify booking and payment records in DB
        $booking = Booking::withoutGlobalScopes()->where('email', 'eko@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertSame('pending', $booking->status);
        $this->assertNotNull($booking->idpayment);

        $payment = Payment::withoutGlobalScopes()->find($booking->idpayment);
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);

        // 9. Verify slot is occupied via AvailabilityRules
        $this->schedule->refresh();
        $this->assertFalse($this->schedule->isAvailable());
        $this->assertSame(\App\Domain\Schedule\AvailabilityRules::STATUS_BOOKED, $this->schedule->getAvailabilityStatus());

        // 10. Customer management page works with token
        $resManage = $this->get('/manage/' . $booking->booking_code . '?token=' . $booking->cancellation_token);
        $resManage->assertStatus(200);
        $resManage->assertSee('Eko Prasetyo');

        // 11. Customer payment succeeds -> synchronize to paid
        $payment->update(['status' => 'sukses']);
        $booking->update(['status' => 'paid']);

        // 12. Customer views invoice
        $resInvoice = $this->get('/manage/' . $booking->booking_code . '/invoice?token=' . $booking->cancellation_token);
        $resInvoice->assertStatus(200);
    }

    /**
     * Requirement 4 & 5: Expired pending booking allows new customer booking on the same slot.
     * Stale pending is evicted and cancelled, payment marked gagal, no unique constraint failure.
     */
    public function test_expired_pending_allows_new_customer_booking_and_evicts_stale_without_unique_constraint_conflict(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(3)->format('Y-m-d');
        $slot = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $targetDate,
            'jam_mulai'   => '15:00:00',
            'jam_selesai' => '16:00:00',
            'status'      => 'tersedia',
        ]);

        // Customer A booking created 25 minutes ago (exceeding 15 min grace period)
        $stalePayment = Payment::create([
            'idtenant'     => $this->tenant->id,
            'order_id'     => 'ORDER-STALE-001',
            'jumlah'       => 50000,
            'tipe'         => 'booking',
            'metode'       => 'midtrans',
            'status'       => 'pending',
            'snap_token'   => 'snap-token-stale',
            'manage_token' => Booking::generateSecureToken(),
            'created_at'   => Carbon::now('Asia/Jakarta')->subMinutes(25),
        ]);

        $bookingA = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $slot->id,
            'idpayment'          => $stalePayment->id,
            'namapelanggan'      => 'Customer A Stale',
            'nomorhp'            => '08123456701',
            'email'              => 'customerA@example.com',
            'tanggalbooking'     => $targetDate,
            'jam'                => $slot->jam_mulai,
            'status'             => BookingState::STATUS_PENDING,
            'booking_code'       => 'BK-STALE-001',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
            'created_at'         => Carbon::now('Asia/Jakarta')->subMinutes(25),
        ]);

        DB::table('payments')->where('id', $stalePayment->id)->update(['created_at' => Carbon::now('Asia/Jakarta')->subMinutes(25)]);
        DB::table('bookings')->where('id', $bookingA->id)->update(['created_at' => Carbon::now('Asia/Jakarta')->subMinutes(25)]);
        $bookingA->refresh();
        $stalePayment->refresh();

        // Semantic checks: stale pending booking does not occupy slot
        $this->assertFalse(BookingState::occupiesSlot($bookingA->status, $bookingA->created_at));
        $this->assertFalse(BookingRules::isSlotOccupied($slot->id));
        $this->assertTrue(\App\Domain\Schedule\AvailabilityRules::isSlotAvailable($slot));

        // Customer B books the same slot via full application checkout flow
        $response = $this->withSession([
            'booking' => [
                'tenant_id'    => $this->tenant->id,
                'service_id'   => $this->service->id,
                'tanggal'      => $targetDate,
                'jam'          => ['15:00'],
                'schedule_ids' => [$slot->id],
            ],
        ])->post('/' . $this->tenant->slug . '/booking/checkout', [
            'namapelanggan' => 'Customer B Fresh',
            'nomorhp'       => '08123456702',
            'email'         => 'customerB@example.com',
            'catatan'       => 'Booking baru menggantikan yang stale',
        ]);

        // Must succeed without unique_active_booking_slot constraint violation
        $response->assertStatus(302);
        $this->assertDatabaseHas('bookings', [
            'email'      => 'customerB@example.com',
            'idschedule' => $slot->id,
            'status'     => BookingState::STATUS_PENDING,
        ]);

        // Booking A must have been evicted and marked cancelled
        $bookingA->refresh();
        $this->assertSame(BookingState::STATUS_CANCELLED, $bookingA->status);

        // Payment A must have been marked gagal
        $stalePayment->refresh();
        $this->assertSame('gagal', $stalePayment->status);

        // Slot is now occupied by Customer B
        $this->assertTrue(BookingRules::isSlotOccupied($slot->id));
        $slot->refresh();
        $this->assertFalse($slot->isAvailable());
    }

    /**
     * Requirement 3: Unified pending grace period boundary semantics.
     * 5 min -> occupied, 14 min -> occupied, 15+ min -> expired/not occupied.
     */
    public function test_grace_period_boundary_semantics(): void
    {
        $testDate = Carbon::tomorrow('Asia/Jakarta')->addDays(4)->format('Y-m-d');
        $slot = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $testDate,
            'jam_mulai'   => '16:00:00',
            'jam_selesai' => '17:00:00',
            'status'      => 'tersedia',
        ]);

        // Case: 5 minutes old -> occupied
        $fiveMinOld = Carbon::now('Asia/Jakarta')->subMinutes(5);
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $fiveMinOld));

        // Case: 14 minutes old -> occupied
        $fourteenMinOld = Carbon::now('Asia/Jakarta')->subMinutes(14);
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $fourteenMinOld));

        // Case: 15+ minutes old -> expired / not occupied
        $fifteenMinOld = Carbon::now('Asia/Jakarta')->subMinutes(15)->subSecond();
        $this->assertFalse(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $fifteenMinOld));

        $twentyMinOld = Carbon::now('Asia/Jakarta')->subMinutes(20);
        $this->assertFalse(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $twentyMinOld));
    }

    /**
     * Requirement 6: Owner Reschedule Regression Tests (Cases A, B, C).
     * Ensures STATUS_REFUNDED removal did not break reschedule and enforces business rules.
     */
    public function test_owner_reschedule_case_a_valid_reschedule_to_empty_slot(): void
    {
        $date1 = Carbon::tomorrow('Asia/Jakarta')->addDays(5)->format('Y-m-d');
        $date2 = Carbon::tomorrow('Asia/Jakarta')->addDays(6)->format('Y-m-d');

        $slot1 = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $date1,
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status'      => 'tersedia',
        ]);

        $slot2 = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $date2,
            'jam_mulai'   => '11:00:00',
            'jam_selesai' => '12:00:00',
            'status'      => 'tersedia',
        ]);

        $payment = Payment::create([
            'idtenant'     => $this->tenant->id,
            'order_id'     => 'ORDER-OWNER-RESCHED-01',
            'jumlah'       => 50000,
            'tipe'         => 'booking',
            'metode'       => 'midtrans',
            'status'       => 'sukses',
            'snap_token'   => 'fake-snap-token',
            'manage_token' => Booking::generateSecureToken(),
        ]);

        $booking = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $slot1->id,
            'idpayment'          => $payment->id,
            'namapelanggan'      => 'Owner Reschedule Test User',
            'nomorhp'            => '08123456703',
            'email'              => 'ownerresched@example.com',
            'tanggalbooking'     => $date1,
            'jam'                => $slot1->jam_mulai,
            'status'             => BookingState::STATUS_PAID,
            'booking_code'       => 'BK-OWN-RES-01',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
        ]);

        $this->assertTrue(BookingRules::isSlotOccupied($slot1->id));
        $this->assertFalse(BookingRules::isSlotOccupied($slot2->id));

        // Case A: Owner reschedules to empty slot2
        $response = $this->actingAs($this->owner)
            ->post("/owner/bookings/{$booking->id}/reschedule", [
                'schedule_id' => $slot2->id,
                'alasan'      => 'Permintaan langsung customer walk-in di studio',
            ]);

        $response->assertSessionHas('sukses');
        $booking->refresh();

        // 1. Reschedule succeeded and moved to slot2
        $this->assertSame($slot2->id, (int) $booking->idschedule);
        $this->assertSame($date2, Carbon::parse($booking->tanggalbooking)->toDateString());
        $this->assertSame(substr($slot2->jam_mulai, 0, 5), substr($booking->jam, 0, 5));

        // 2. Status remains paid
        $this->assertSame(BookingState::STATUS_PAID, $booking->status);

        // 3. Payment reference consistent
        $this->assertSame($payment->id, (int) $booking->idpayment);

        // 4. Old slot no longer occupied
        $this->assertFalse(BookingRules::isSlotOccupied($slot1->id));

        // 5. New slot is occupied
        $this->assertTrue(BookingRules::isSlotOccupied($slot2->id));
    }

    public function test_owner_reschedule_case_b_to_occupied_slot_is_rejected(): void
    {
        $date = Carbon::tomorrow('Asia/Jakarta')->addDays(7)->format('Y-m-d');

        $slot1 = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $date,
            'jam_mulai'   => '13:00:00',
            'jam_selesai' => '14:00:00',
            'status'      => 'tersedia',
        ]);

        $slot2 = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $date,
            'jam_mulai'   => '14:00:00',
            'jam_selesai' => '15:00:00',
            'status'      => 'tersedia',
        ]);

        $booking1 = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $slot1->id,
            'namapelanggan'      => 'User One',
            'nomorhp'            => '08123456704',
            'email'              => 'userone@example.com',
            'tanggalbooking'     => $date,
            'jam'                => $slot1->jam_mulai,
            'status'             => BookingState::STATUS_PAID,
            'booking_code'       => 'BK-OWN-RES-02A',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
        ]);

        $booking2 = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $slot2->id,
            'namapelanggan'      => 'User Two Occupying Slot 2',
            'nomorhp'            => '08123456705',
            'email'              => 'usertwo@example.com',
            'tanggalbooking'     => $date,
            'jam'                => $slot2->jam_mulai,
            'status'             => BookingState::STATUS_PAID,
            'booking_code'       => 'BK-OWN-RES-02B',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
        ]);

        // Case B: Owner tries to reschedule booking1 to already occupied slot2
        $response = $this->actingAs($this->owner)
            ->post("/owner/bookings/{$booking1->id}/reschedule", [
                'schedule_id' => $slot2->id,
            ]);

        $response->assertSessionHasErrors('error');
        $booking1->refresh();

        // Booking 1 must remain on slot1 without partial mutation
        $this->assertSame($slot1->id, (int) $booking1->idschedule);
        $this->assertSame(substr($slot1->jam_mulai, 0, 5), substr($booking1->jam, 0, 5));
    }

    public function test_owner_reschedule_case_c_cancelled_booking_is_rejected_without_fatal_error(): void
    {
        $date = Carbon::tomorrow('Asia/Jakarta')->addDays(8)->format('Y-m-d');

        $slot1 = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $date,
            'jam_mulai'   => '15:00:00',
            'jam_selesai' => '16:00:00',
            'status'      => 'tersedia',
        ]);

        $slot2 = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $date,
            'jam_mulai'   => '16:00:00',
            'jam_selesai' => '17:00:00',
            'status'      => 'tersedia',
        ]);

        $cancelledBooking = Booking::create([
            'idtenant'           => $this->tenant->id,
            'idlayanan'          => $this->service->id,
            'idschedule'         => $slot1->id,
            'namapelanggan'      => 'Cancelled User',
            'nomorhp'            => '08123456706',
            'email'              => 'cancelleduser@example.com',
            'tanggalbooking'     => $date,
            'jam'                => $slot1->jam_mulai,
            'status'             => BookingState::STATUS_CANCELLED,
            'booking_code'       => 'BK-OWN-RES-03',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token'   => Booking::generateSecureToken(),
        ]);

        // Case C: Owner tries to reschedule a cancelled booking.
        // Must be rejected with a clean validation error and NOT crash with undefined constant STATUS_REFUNDED.
        $response = $this->actingAs($this->owner)
            ->post("/owner/bookings/{$cancelledBooking->id}/reschedule", [
                'schedule_id' => $slot2->id,
            ]);

        $response->assertSessionHasErrors('error');
        $cancelledBooking->refresh();
        $this->assertSame(BookingState::STATUS_CANCELLED, $cancelledBooking->status);
        $this->assertSame($slot1->id, (int) $cancelledBooking->idschedule);
    }

    /**
     * Requirement 4 & 5: Concurrency / duplicate booking attempt protection.
     * Ensures only one booking succeeds and no duplicate active booking is created.
     */
    public function test_concurrent_duplicate_booking_attempt_results_in_only_one_active_booking(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(9)->format('Y-m-d');
        $slot = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => $targetDate,
            'jam_mulai'   => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status'      => 'tersedia',
        ]);

        // Attempt 1: Customer 1 books the slot via full checkout flow
        $res1 = $this->withSession([
            'booking' => [
                'tenant_id'    => $this->tenant->id,
                'service_id'   => $this->service->id,
                'tanggal'      => $targetDate,
                'jam'          => ['10:00'],
                'schedule_ids' => [$slot->id],
            ],
        ])->post('/' . $this->tenant->slug . '/booking/checkout', [
            'namapelanggan' => 'First Concurrent User',
            'nomorhp'       => '08123456711',
            'email'         => 'first@example.com',
            'catatan'       => null,
        ]);

        $res1->assertStatus(302);
        $this->assertDatabaseHas('bookings', [
            'email'      => 'first@example.com',
            'idschedule' => $slot->id,
            'status'     => BookingState::STATUS_PENDING,
        ]);

        // Attempt 2: Customer 2 attempts to book the exact same slot immediately
        $res2 = $this->withSession([
            'booking' => [
                'tenant_id'    => $this->tenant->id,
                'service_id'   => $this->service->id,
                'tanggal'      => $targetDate,
                'jam'          => ['10:00'],
                'schedule_ids' => [$slot->id],
            ],
        ])->post('/' . $this->tenant->slug . '/booking/checkout', [
            'namapelanggan' => 'Second Concurrent User',
            'nomorhp'       => '08123456712',
            'email'         => 'second@example.com',
            'catatan'       => null,
        ]);

        // Attempt 2 must be cleanly rejected
        $res2->assertSessionHasErrors('jam');

        // Exactly 1 active booking must exist for this schedule
        $activeBookingsCount = Booking::withoutGlobalScopes()
            ->where('idschedule', $slot->id)
            ->whereIn('status', [BookingState::STATUS_PENDING, BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
            ->count();

        $this->assertSame(1, $activeBookingsCount);
    }
}
