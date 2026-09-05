<?php

namespace Tests\Feature\Customer;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingReviewTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Service $service;
    protected Schedule $schedule;
    protected Payment $payment;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create([
            'iduser' => $owner->id,
            'namabisnis' => 'Studio Cantik',
            'cancel_before_hours' => 24,
            'reschedule_before_hours' => 24,
        ]);

        $this->service = Service::factory()->create([
            'idtenant' => $this->tenant->id,
            'harga' => 150000,
            'durasi' => 60,
        ]);

        $this->schedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '15:00:00',
            'status' => 'tersedia',
        ]);

        $this->payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'jumlah' => 150000,
            'status' => 'sukses',
        ]);

        $this->booking = Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'idpayment' => $this->payment->id,
            'status' => 'completed',
            'tanggalbooking' => $this->schedule->tanggal,
            'jam' => $this->schedule->jam_mulai,
            'booking_code' => 'BKQ-REV-12345',
            'cancellation_token' => 'valid-cancel-token-rev',
            'reschedule_token' => 'valid-reschedule-token-rev',
        ]);
    }

    public function test_customer_can_see_review_form_when_booking_is_completed(): void
    {
        $response = $this->get('/manage/' . $this->booking->booking_code . '?token=' . $this->booking->cancellation_token);

        $response->assertStatus(200);
        $response->assertSee('Berikan Ulasan Layanan');
        $response->assertSee('Penilaian Bintang');
    }

    public function test_customer_does_not_see_review_form_when_booking_is_not_completed(): void
    {
        $this->booking->update(['status' => 'paid']);

        $response = $this->get('/manage/' . $this->booking->booking_code . '?token=' . $this->booking->cancellation_token);

        $response->assertStatus(200);
        $response->assertDontSee('Berikan Ulasan Layanan');
    }

    public function test_customer_can_submit_review_for_completed_booking(): void
    {
        $response = $this->post('/manage/' . $this->booking->booking_code . '/review', [
            'token' => $this->booking->cancellation_token,
            'rating' => 5,
            'komentar' => 'Pelayanan sangat ramah dan tempatnya bersih!',
        ]);

        $response->assertRedirect('/manage/' . $this->booking->booking_code . '?token=' . $this->booking->cancellation_token);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'idtenant' => $this->tenant->id,
            'idbooking' => $this->booking->id,
            'rating' => 5,
            'komentar' => 'Pelayanan sangat ramah dan tempatnya bersih!',
            'is_hidden' => false,
        ]);

        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $this->booking->id,
            'event' => 'reviewed',
        ]);
    }

    public function test_customer_cannot_submit_review_for_uncompleted_booking(): void
    {
        $this->booking->update(['status' => 'paid']);

        $response = $this->post('/manage/' . $this->booking->booking_code . '/review', [
            'token' => $this->booking->cancellation_token,
            'rating' => 5,
            'komentar' => 'Bagus',
        ]);

        $response->assertSessionHasErrors('review');
        $this->assertDatabaseMissing('reviews', ['idbooking' => $this->booking->id]);
    }

    public function test_customer_cannot_submit_review_with_invalid_token(): void
    {
        $response = $this->post('/manage/' . $this->booking->booking_code . '/review', [
            'token' => 'invalid-token-xyz',
            'rating' => 5,
            'komentar' => 'Bagus',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('reviews', ['idbooking' => $this->booking->id]);
    }

    public function test_customer_cannot_submit_duplicate_review(): void
    {
        Review::create([
            'idtenant' => $this->tenant->id,
            'idbooking' => $this->booking->id,
            'rating' => 4,
            'komentar' => 'Sudah direview',
        ]);

        $response = $this->post('/manage/' . $this->booking->booking_code . '/review', [
            'token' => $this->booking->cancellation_token,
            'rating' => 5,
            'komentar' => 'Mau review lagi',
        ]);

        $response->assertSessionHasErrors('review');
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $responseZero = $this->post('/manage/' . $this->booking->booking_code . '/review', [
            'token' => $this->booking->cancellation_token,
            'rating' => 0,
        ]);
        $responseZero->assertSessionHasErrors('rating');

        $responseSix = $this->post('/manage/' . $this->booking->booking_code . '/review', [
            'token' => $this->booking->cancellation_token,
            'rating' => 6,
        ]);
        $responseSix->assertSessionHasErrors('rating');

        $responseEmpty = $this->post('/manage/' . $this->booking->booking_code . '/review', [
            'token' => $this->booking->cancellation_token,
            'rating' => '',
        ]);
        $responseEmpty->assertSessionHasErrors('rating');
    }

    public function test_submitted_review_and_owner_reply_are_visible_on_manage_page(): void
    {
        Review::create([
            'idtenant' => $this->tenant->id,
            'idbooking' => $this->booking->id,
            'rating' => 5,
            'komentar' => 'Pengalaman luar biasa!',
            'balasan' => 'Terima kasih banyak kak atas kepercayaannya!',
            'dibalas_pada' => now(),
        ]);

        $response = $this->get('/manage/' . $this->booking->booking_code . '?token=' . $this->booking->cancellation_token);

        $response->assertStatus(200);
        $response->assertSee('Ulasan Anda');
        $response->assertSee('Pengalaman luar biasa!');
        $response->assertSee('Tanggapan dari ' . $this->tenant->namabisnis);
        $response->assertSee('Terima kasih banyak kak atas kepercayaannya!');
        $response->assertDontSee('Berikan Ulasan Layanan');
    }
}
