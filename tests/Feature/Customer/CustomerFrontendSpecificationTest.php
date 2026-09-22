<?php

namespace Tests\Feature\Customer;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerFrontendSpecificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Service $service;
    protected string $tomorrow;
    protected Schedule $schedule1;
    protected Schedule $schedule2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->user = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Studio Foto Bagus',
            'jenisbisnis' => 'Studio Foto',
            'slug' => 'studio-foto-bagus',
            'alamat' => 'Jl. Merdeka No. 10',
            'nomorhp' => '081299998888',
        ]);

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Foto Wisuda Personal',
            'harga' => 150000,
            'durasi' => 60,
            'is_active' => true,
        ]);

        $this->tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $this->schedule1 = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        $this->schedule2 = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '11:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'tersedia',
        ]);
    }

    public function test_program_selection_contains_prescribed_copy_and_affordance(): void
    {
        $response = $this->get('/' . $this->tenant->slug);

        $response->assertStatus(200);
        $response->assertSee('Pilih Layanan');
        $response->assertSee('Pilih salah satu layanan untuk memulai pemesanan.');
        $response->assertSee('placeholder="Cari layanan..."', false);
        $response->assertSee('Pilih Layanan Ini');
        $response->assertSee('Foto Wisuda Personal');
        $response->assertSee('Rp 150.000');
    }

    public function test_date_selection_view_renders_canonical_copy(): void
    {
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ]
        ]);

        $response = $this->get('/' . $this->tenant->slug . '/booking/date');

        $response->assertStatus(200);
        $response->assertSee('Pilih Tanggal');
        $response->assertSee('Pilih tanggal yang tersedia untuk reservasi Anda.');
        $response->assertSee('Kembali ke Pilih Layanan');
    }

    public function test_time_selection_provides_range_labels_and_prices(): void
    {
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
            ]
        ]);

        $response = $this->get('/' . $this->tenant->slug . '/booking/time');

        $response->assertStatus(200);
        $response->assertSee('Pilih Waktu');
        $response->assertSee('10:00 – 11:00');
        $response->assertSee('11:00 – 12:00');
        $response->assertSee('Rp 150.000');
        $response->assertSee('Lanjut ke Data Pemesan');
        $response->assertSee('Kembali ke Pilih Tanggal');
    }

    public function test_checkout_view_renders_mobile_reservation_review_card_and_canonical_copy(): void
    {
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
                'jam' => ['10:00', '11:00'],
                'schedule_ids' => [$this->schedule1->id, $this->schedule2->id],
            ]
        ]);

        $response = $this->get('/' . $this->tenant->slug . '/booking/checkout');

        $response->assertStatus(200);
        $response->assertSee('Data Pemesan');
        $response->assertSee('Pastikan nama, email, dan nomor WhatsApp Anda sudah benar.');
        $response->assertSee('Detail Reservasi');
        $response->assertSee('Simpan data kontak di perangkat ini untuk pemesanan berikutnya.');
        $response->assertSee('Lanjut ke Pembayaran');
        $response->assertSee('Kembali ke Pilih Waktu');
        $response->assertSee('2 sesi (120 menit)');
        // Ensure obsolete voucher input is removed
        $response->assertDontSee('voucher_code_input');
    }

    public function test_payment_page_renders_bayar_sekarang_and_no_auto_click_script(): void
    {
        $orderId = 'ORD-' . strtoupper(Str::random(10));
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'order_id' => $orderId,
            'jumlah' => 300000,
            'status' => 'pending',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'snap_token' => 'mock_snap_token_' . Str::random(16),
            'manage_token' => Str::random(32),
            'expires_at' => now()->addMinutes(15),
        ]);

        Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule1->id,
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'namapelanggan' => 'Ahmad Customer',
            'email' => 'ahmad@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'pending',
            'payment_id' => $payment->id,
        ]);

        $response = $this->get('/' . $this->tenant->slug . '/booking/payment/' . $orderId);

        $response->assertStatus(200);
        $response->assertSee('Bayar Sekarang');
        $response->assertSee('Bayar sebelum');
        $response->assertSee('Rp 300.000');
        // Auto-click script must NOT exist
        $response->assertDontSee('payButton.click()');

        // Expiration time must be formatted in WIB (Asia/Jakarta)
        $expectedWib = now('Asia/Jakarta')->addMinutes(15)->format('H:i');
        $response->assertSee($expectedWib . ' WIB');

        // Cancel action must point to the slug route, not http://{slug}
        $expectedCancelUrl = route(\App\Support\CustomerBookingRoutes::name('customer.booking.cancel'), [$this->tenant->slug, $payment]);
        $response->assertSee('action="' . $expectedCancelUrl . '"', false);
        $response->assertDontSee('action="http://' . $this->tenant->slug, false);

        // Back and new reservation links must point to slug program route
        $expectedProgramUrl = route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $this->tenant->slug);
        $response->assertSee('href="' . $expectedProgramUrl . '"', false);
        $response->assertDontSee('href="http://' . $this->tenant->slug . '"', false);
    }

    public function test_customer_can_cancel_payment_and_redirects_to_program_selection(): void
    {
        $orderId = 'ORD-' . strtoupper(Str::random(10));
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'order_id' => $orderId,
            'jumlah' => 150000,
            'status' => 'pending',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'manage_token' => Str::random(32),
            'expired_at' => now()->addMinutes(15),
        ]);

        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule1->id,
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'namapelanggan' => 'Test Customer',
            'email' => 'test@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'pending',
            'idpayment' => $payment->id,
        ]);

        $cancelUrl = '/' . $this->tenant->slug . '/booking/payment/' . $orderId . '/cancel';
        $response = $this->post($cancelUrl);

        $response->assertRedirect('/' . $this->tenant->slug);
        $response->assertSessionHas('info', 'Transaksi berhasil dibatalkan. Anda dapat memilih layanan atau jadwal baru.');

        $this->assertEquals('gagal', $payment->fresh()->status);
        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    public function test_invoice_and_management_share_links_do_not_leak_manage_tokens(): void
    {
        $orderId = 'ORD-' . strtoupper(Str::random(10));
        $manageToken = 'SECRET_TOKEN_' . Str::random(24);

        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'order_id' => $orderId,
            'jumlah' => 150000,
            'status' => 'sukses',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'manage_token' => $manageToken,
            'expires_at' => now()->addMinutes(15),
        ]);

        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule1->id,
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'namapelanggan' => 'Budi Share',
            'email' => 'budi@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'paid',
            'idpayment' => $payment->id,
            'payment_id' => $payment->id,
        ]);

        // 1. Check Invoice View
        $responseInvoice = $this->get('/' . $this->tenant->slug . '/booking/payment/' . $orderId . '/invoice');
        $responseInvoice->assertStatus(200);
        $responseInvoice->assertSee('Bukti Reservasi & Pembayaran');
        $responseInvoice->assertSee('Tambahkan ke Google Calendar');
        $responseInvoice->assertSee('Bagikan ke WhatsApp');

        // Verify that Google Calendar URL does NOT contain the secret manage token
        $contentInvoice = $responseInvoice->getContent();
        preg_match('/href="([^"]*calendar\.google\.com[^"]*)"/', $contentInvoice, $matchesGCalInv);
        if (!empty($matchesGCalInv[1])) {
            $this->assertStringNotContainsString($manageToken, $matchesGCalInv[1]);
        }

        // Verify that WhatsApp share URL does NOT contain the secret manage token
        preg_match('/href="([^"]*whatsapp\.com[^"]*)"/', $contentInvoice, $matchesWAInv);
        if (!empty($matchesWAInv[1])) {
            $this->assertStringNotContainsString($manageToken, $matchesWAInv[1]);
        }

        // 2. Check Payment Management View
        $responseManage = $this->get('/manage/payment/' . $orderId . '?token=' . $manageToken);
        $responseManage->assertStatus(200);

        // Google Calendar href in manage view should NOT contain manage token
        $contentManage = $responseManage->getContent();
        preg_match('/href="([^"]*calendar\.google\.com[^"]*)"/', $contentManage, $matchesGCal);
        if (!empty($matchesGCal[1])) {
            $this->assertStringNotContainsString($manageToken, $matchesGCal[1]);
        }

        // WhatsApp share href in manage view should NOT contain manage token
        preg_match('/href="([^"]*whatsapp\.com[^"]*)"/', $contentManage, $matchesWA);
        if (!empty($matchesWA[1])) {
            $this->assertStringNotContainsString($manageToken, $matchesWA[1]);
        }
    }

    public function test_multi_slot_management_displays_prescriptive_notice(): void
    {
        $orderId = 'ORD-' . strtoupper(Str::random(10));
        $manageToken = Str::random(32);

        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'order_id' => $orderId,
            'jumlah' => 300000,
            'status' => 'sukses',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'manage_token' => $manageToken,
            'expires_at' => now()->addMinutes(15),
        ]);

        Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule1->id,
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'namapelanggan' => 'Sari Multislot',
            'email' => 'sari@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'paid',
            'idpayment' => $payment->id,
            'payment_id' => $payment->id,
        ]);

        Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule2->id,
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'namapelanggan' => 'Sari Multislot',
            'email' => 'sari@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '11:00',
            'status' => 'paid',
            'idpayment' => $payment->id,
            'payment_id' => $payment->id,
        ]);

        $response = $this->get('/manage/payment/' . $orderId . '?token=' . $manageToken);

        $response->assertStatus(200);
        $response->assertSee('Reservasi ini terdiri dari beberapa sesi yang berurutan. Perubahan atau pembatalan per sesi tidak tersedia.');
        $response->assertDontSee('Ubah Jadwal');
        $response->assertDontSee('Batalkan Reservasi');
    }

    public function test_single_slot_management_displays_reschedule_and_cancel_buttons(): void
    {
        $orderId = 'ORD-' . strtoupper(Str::random(10));
        $manageToken = Str::random(32);

        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'order_id' => $orderId,
            'jumlah' => 150000,
            'status' => 'sukses',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'manage_token' => $manageToken,
            'expires_at' => now()->addMinutes(15),
        ]);

        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule1->id,
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'namapelanggan' => 'Rian Singleslot',
            'email' => 'rian@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => now()->addDays(3)->toDateString(),
            'jam' => '10:00',
            'status' => 'paid',
            'idpayment' => $payment->id,
            'payment_id' => $payment->id,
        ]);

        $response = $this->get('/manage/payment/' . $orderId . '?token=' . $manageToken);

        $response->assertStatus(200);
        $response->assertSee('Ubah Jadwal');
        $response->assertSee('Batalkan Reservasi');
        $response->assertSee('Yakin ingin membatalkan reservasi ini?');
        $response->assertSee('Ya, Batalkan Reservasi');
    }
}
