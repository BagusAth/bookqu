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
}
