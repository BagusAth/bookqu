<?php

namespace Tests\Feature\Customer;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreeStepBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Service $service;
    protected string $tomorrow;
    protected Schedule $morningSchedule;
    protected Schedule $morningSchedule2;
    protected Schedule $afternoonSchedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        config(['payment.default' => 'midtrans']);

        $this->user = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Creative Hub Studio',
            'jenisbisnis' => 'Sewa Lapangan Badminton',
            'slug' => 'creative-hub-studio',
            'alamat' => 'Jl. Olahraga No. 10',
            'nomorhp' => '081234567890',
        ]);

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Badminton Court Rental',
            'harga' => 35000,
            'durasi' => 60,
            'is_active' => true,
        ]);

        $this->tomorrow = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        // Morning Session Slot 1 (10:00 - 11:00 AM)
        $this->morningSchedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        // Morning Session Slot 2 (11:00 - 12:00 AM)
        $this->morningSchedule2 = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '11:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'tersedia',
        ]);

        // Afternoon Session Slot (14:00 - 15:00 PM)
        $this->afternoonSchedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '15:00:00',
            'status' => 'tersedia',
        ]);
    }

    /**
     * Test Step 1: Program selection page renders and lets customer select a service.
     */
    public function test_step_1_customer_selects_program(): void
    {
        $response = $this->get('/creative-hub-studio');
        $response->assertStatus(200);
        $response->assertSee('Badminton Court Rental');
        $response->assertSee('Creative Hub Studio');
        $response->assertSee('Program');

        $responseSelect = $this->post('/creative-hub-studio/booking/select-program', [
            'service_id' => $this->service->id,
        ]);

        $responseSelect->assertRedirect();
        $this->assertEquals($this->service->id, session('booking.service_id'));
    }

    /**
     * Test Step 2: Schedule view renders with 3-step progress, horizontal dates,
     * morning/afternoon sessions, and summary card.
     */
    public function test_step_2_customer_can_view_schedule_with_date_and_time_sessions(): void
    {
        $response = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ],
        ])->get('/creative-hub-studio/booking/schedule');

        $response->assertStatus(200);
        $response->assertSee('Schedule');
        $response->assertSee('Program');
        $response->assertSee('Confirm');
        $response->assertSee('Morning Sessions');
        $response->assertSee('Afternoon Sessions');
        $response->assertSee('10:00 – 11:00');
        $response->assertSee('Badminton Court Rental');
        $response->assertSee('Creative Hub Studio');
        $response->assertSee('Checkout');
        $response->assertSee('Secure payment powered by BookQu');
    }

    /**
     * Test Step 2 JSON API: Asynchronous slot fetching when selecting a date.
     */
    public function test_step_2_slots_api_returns_grouped_morning_and_afternoon_sessions(): void
    {
        $response = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ],
        ])->getJson('/creative-hub-studio/booking/schedule/slots?tanggal=' . $this->tomorrow);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_blocked' => false,
            'date' => $this->tomorrow,
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['morning']);
        $this->assertEquals('10:00', $data['morning'][0]['time']);
        $this->assertNotEmpty($data['afternoon']);
        $this->assertEquals('14:00', $data['afternoon'][0]['time']);
    }

    /**
     * Test Step 2 Submission: Customer selects schedule (date + slot) and proceeds to Step 3 Confirm.
     */
    public function test_step_2_customer_submits_schedule_and_redirects_to_confirm(): void
    {
        $response = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ],
        ])->post('/creative-hub-studio/booking/select-schedule', [
            'tanggal' => $this->tomorrow,
            'jam' => ['10:00'],
            'schedule_ids' => [$this->morningSchedule->id],
        ]);

        $response->assertRedirect('/creative-hub-studio/booking/checkout');
        $this->assertEquals($this->tomorrow, session('booking.tanggal'));
        $this->assertEquals(['10:00'], (array) session('booking.jam'));
        $this->assertEquals($this->morningSchedule->id, session('booking.schedule_id'));
    }

    /**
     * Test Step 3: Confirm view renders customer info form and completes booking.
     */
    public function test_step_3_customer_confirms_booking_and_creates_reservation(): void
    {
        $response = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
                'jam' => ['10:00'],
                'schedule_id' => $this->morningSchedule->id,
                'schedule_ids' => [$this->morningSchedule->id],
            ],
        ])->get('/creative-hub-studio/booking/checkout');

        $response->assertStatus(200);
        $response->assertSee('Data Pemesan');
        $response->assertSee('Confirm Reservasi');
        $response->assertSee('Badminton Court Rental');

        // Submit checkout
        $responseSubmit = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
                'jam' => ['10:00'],
                'schedule_id' => $this->morningSchedule->id,
                'schedule_ids' => [$this->morningSchedule->id],
            ],
        ])->post('/creative-hub-studio/booking/checkout', [
            'namapelanggan' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'nomorhp' => '081298765432',
        ]);

        $responseSubmit->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->morningSchedule->id,
            'namapelanggan' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'status' => 'pending',
        ]);
    }

    /**
     * Test Step 2 Multi-Session Selection: Customer selects 2 consecutive sessions (e.g. 2 hours),
     * views updated checkout summary with aggregated duration & price, and completes multi-booking.
     */
    public function test_step_2_customer_can_select_multiple_contiguous_sessions_and_proceed_to_checkout(): void
    {
        // 1. Submit 2 contiguous morning sessions (10:00 - 11:00 and 11:00 - 12:00)
        $responseSelect = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ],
        ])->post('/creative-hub-studio/booking/select-schedule', [
            'tanggal' => $this->tomorrow,
            'jam' => ['10:00', '11:00'],
            'schedule_ids' => [$this->morningSchedule->id, $this->morningSchedule2->id],
        ]);

        $responseSelect->assertRedirect('/creative-hub-studio/booking/checkout');
        $this->assertEquals(['10:00', '11:00'], session('booking.jam'));
        $this->assertEquals([$this->morningSchedule->id, $this->morningSchedule2->id], session('booking.schedule_ids'));

        // 2. View Checkout: assert 2 sessions (120 minutes) and aggregated price Rp 70.000
        $responseCheckout = $this->withSession(session()->all())
            ->get('/creative-hub-studio/booking/checkout');

        $responseCheckout->assertStatus(200);
        $responseCheckout->assertSee('2 sesi (120 menit)');
        $responseCheckout->assertSee('70.000');
        $responseCheckout->assertDontSee('berbeda dengan harga standar');

        // 3. Process Checkout with Customer Details
        $responseSubmit = $this->withSession(session()->all())
            ->post('/creative-hub-studio/booking/checkout', [
                'namapelanggan' => 'Ahmad Multisesi',
                'email' => 'ahmad@example.com',
                'nomorhp' => '081234567890',
            ]);

        $responseSubmit->assertRedirect();

        // 4. Assert both bookings created in database for the two distinct schedules
        $this->assertDatabaseHas('bookings', [
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->morningSchedule->id,
            'namapelanggan' => 'Ahmad Multisesi',
            'email' => 'ahmad@example.com',
        ]);

        $this->assertDatabaseHas('bookings', [
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->morningSchedule2->id,
            'namapelanggan' => 'Ahmad Multisesi',
            'email' => 'ahmad@example.com',
        ]);

        // Assert bookings share the same payment of Rp 70.000
        $this->assertDatabaseHas('payments', [
            'idtenant' => $this->tenant->id,
            'jumlah' => 70000,
            'status' => 'pending',
        ]);
    }

    /**
     * Regression Test: Stale session slots from a different date (e.g. 13:00 on yesterday)
     * must NEVER contaminate new date selection or cause "Jam 13:00 sudah tidak tersedia" error.
     */
    public function test_stale_session_slots_from_different_date_do_not_contaminate_new_schedule_selection(): void
    {
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        // Create a stale slot on today
        $staleSchedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $today,
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '14:00:00',
            'status' => 'tersedia',
        ]);

        // User previously selected 13:00 on today
        $sessionData = [
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $today,
                'jam' => ['13:00'],
                'schedule_id' => $staleSchedule->id,
                'schedule_ids' => [$staleSchedule->id],
            ],
        ];

        // Customer views schedule for tomorrow
        $responseView = $this->withSession($sessionData)
            ->get('/creative-hub-studio/booking/schedule?tanggal=' . $this->tomorrow);

        $responseView->assertStatus(200);
        // HTML must NOT render hardcoded stale hidden input for schedule 13:00
        $responseView->assertDontSee('value="' . $staleSchedule->id . '"', false);

        // Customer submits new tomorrow schedule (10:00 & 11:00)
        $responseSubmit = $this->withSession($sessionData)
            ->post('/creative-hub-studio/booking/select-schedule', [
                'tanggal' => $this->tomorrow,
                'jam' => ['10:00', '11:00'],
                'schedule_ids' => [$this->morningSchedule->id, $this->morningSchedule2->id],
            ]);

        // Must redirect to checkout cleanly without session errors for 13:00
        $responseSubmit->assertRedirect('/creative-hub-studio/booking/checkout');
        $responseSubmit->assertSessionHasNoErrors();

        $this->assertEquals($this->tomorrow, session('booking.tanggal'));
        $this->assertEquals(['10:00', '11:00'], session('booking.jam'));
        $this->assertEquals([$this->morningSchedule->id, $this->morningSchedule2->id], session('booking.schedule_ids'));
    }
}
