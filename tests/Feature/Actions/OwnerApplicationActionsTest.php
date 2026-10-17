<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Calendar\GetOwnerCalendarData;
use App\Actions\Customer\GetCustomerDetail;
use App\Actions\Customer\SaveCustomerNote;
use App\Actions\Dashboard\GetOwnerDashboardOverview;
use App\Actions\Owner\DeleteOwnerAccount;
use App\Actions\Owner\RequestOwnerPayout;
use App\Actions\Owner\UpdateOwnerAccount;
use App\Actions\Schedule\GenerateScheduleReport;
use App\Actions\Tenant\CreateInitialProfile;
use App\Actions\Tenant\UpdateAppearanceSettings;
use App\Actions\Tenant\UpdateBusinessProfile;
use App\Actions\Tenant\UpdatePaymentSettings;
use App\Models\Booking;
use App\Models\CustomerNote;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OwnerApplicationActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Service $service;
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role'     => 'owner',
            'email'    => 'owner@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->tenant = Tenant::create([
            'iduser'         => $this->user->id,
            'namabisnis'     => 'Glow Clinic',
            'slug'           => 'glow-clinic',
            'jenisbisnis'    => 'Klinik Kecantikan',
            'nomorhp'        => '081234567890',
            'alamat'         => 'Jl. Senopati No. 8',
            'saldo_platform' => 500000,
            'payment_mode'   => 'platform',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->service = Service::create([
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Facial Glow',
            'harga'       => 250000,
            'durasi'      => 60,
        ]);

        $this->schedule = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => now()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status'      => 'tersedia',
        ]);
    }

    public function test_save_customer_note_action(): void
    {
        // Must have at least one booking to be valid customer
        Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $this->service->id,
            'idschedule'     => $this->schedule->id,
            'tanggalbooking' => now()->toDateString(),
            'jam'            => '09:00:00',
            'namapelanggan'  => 'Siti Rahma',
            'nomorhp'        => '08123456789',
            'email'          => 'siti@example.com',
            'status'         => 'paid',
        ]);

        $action = app(SaveCustomerNote::class);
        $note = $action->execute($this->tenant, 'siti@example.com', 'Alergi minyak kayu putih');

        $this->assertInstanceOf(CustomerNote::class, $note);
        $this->assertEquals('Alergi minyak kayu putih', $note->notes);
        $this->assertDatabaseHas('customer_notes', [
            'idtenant'            => $this->tenant->id,
            'customer_identifier' => 'siti@example.com',
            'notes'               => 'Alergi minyak kayu putih',
        ]);
    }

    public function test_get_customer_detail_action(): void
    {
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'tipe'     => 'booking',
            'status'   => 'sukses',
            'jumlah'   => 250000,
            'metode'   => 'qris',
            'order_id' => 'PAY-TEST-001',
        ]);

        Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $this->service->id,
            'idschedule'     => $this->schedule->id,
            'idpayment'      => $payment->id,
            'tanggalbooking' => now()->toDateString(),
            'jam'            => '10:00:00',
            'namapelanggan'  => 'Rina Kusuma',
            'nomorhp'        => '08999888777',
            'email'          => 'rina@example.com',
            'status'         => 'paid',
        ]);

        $action = app(GetCustomerDetail::class);
        $detail = $action->execute($this->tenant, 'rina@example.com');

        $this->assertEquals('rina@example.com', $detail['identifier']);
        $this->assertEquals('Rina Kusuma', $detail['name']);
        $this->assertEquals(250000, $detail['total_spent']);
        $this->assertEquals(1, $detail['total_bookings']);
    }

    public function test_get_owner_dashboard_overview_action(): void
    {
        $action = app(GetOwnerDashboardOverview::class);

        $overview = $action->getOverviewData($this->tenant);
        $this->assertArrayHasKey('totalbooking', $overview);
        $this->assertArrayHasKey('totalrevenue', $overview);
        $this->assertArrayHasKey('datarevenueperbulan', $overview);
        $this->assertArrayHasKey('datarevenueperminggu', $overview);

        $polling = $action->getPollingData($this->tenant);
        $this->assertArrayHasKey('total_bookings', $polling);
        $this->assertArrayHasKey('total_revenue', $polling);
    }

    public function test_update_business_profile_action(): void
    {
        $action = app(UpdateBusinessProfile::class);

        $updated = $action->execute($this->user, $this->tenant, [
            'namabisnis'  => 'Glow Beauty Aesthetic',
            'jenisbisnis' => 'Klinik & Salon',
            'alamat'      => 'Jl. Baru No. 99',
            'nomorhp'     => '081999888777',
            'deskripsi'   => 'Layanan kecantikan terlengkap',
        ]);

        $this->assertEquals('Glow Beauty Aesthetic', $updated->namabisnis);
        $this->assertEquals('glow-beauty-aesthetic', $updated->slug);
    }

    public function test_update_payment_settings_action(): void
    {
        $action = app(UpdatePaymentSettings::class);

        $updated = $action->execute($this->tenant, [
            'payment_mode'                 => 'owner',
            'midtrans_environment'         => 'sandbox',
            'midtrans_sandbox_merchant_id' => 'M123456',
            'midtrans_sandbox_client_key'  => 'SB-Mid-client-xxx',
            'midtrans_sandbox_server_key'  => 'SB-Mid-server-yyy',
        ]);

        $this->assertEquals('owner', $updated->payment_mode);
        $this->assertEquals('pending', $updated->midtrans_status);
        $this->assertEquals('M123456', $updated->midtrans_sandbox_merchant_id);
    }

    public function test_request_owner_payout_action(): void
    {
        $action = app(RequestOwnerPayout::class);

        $payout = $action->execute($this->tenant, 200000);

        $this->assertEquals(200000, $payout->jumlah);
        $this->assertEquals('pending', $payout->status);
        $this->assertEquals(300000, $this->tenant->fresh()->saldo_platform);
    }

    public function test_request_owner_payout_action_rejects_insufficient_balance(): void
    {
        $action = app(RequestOwnerPayout::class);

        $this->expectException(ValidationException::class);
        $action->execute($this->tenant, 9999999);
    }

    public function test_update_appearance_settings_action(): void
    {
        $action = app(UpdateAppearanceSettings::class);

        $updated = $action->execute($this->tenant, [
            'theme_color'  => '#3b82f6',
            'button_style' => 'rounded-full',
            'font_family'  => 'Inter',
            'card_style'   => 'glass',
        ]);

        $this->assertEquals('#3b82f6', $updated->theme_color);
        $this->assertEquals('rounded-full', $updated->button_style);
        $this->assertEquals('Inter', $updated->font_family);
        $this->assertEquals('glass', $updated->card_style);
    }

    public function test_update_owner_account_action(): void
    {
        $action = app(UpdateOwnerAccount::class);

        $updated = $action->execute($this->user, [
            'namalengkap' => 'Owner Sukses Baru',
            'email'       => 'owner_baru@example.com',
            'nomorhp'     => '082223334444',
            'password'    => 'newpassword123',
        ]);

        $this->assertEquals('Owner Sukses Baru', $updated->namalengkap);
        $this->assertEquals('owner_baru@example.com', $updated->email);
        $this->assertTrue(Hash::check('newpassword123', $updated->password));
    }

    public function test_delete_owner_account_action_requires_matching_confirmation(): void
    {
        $action = app(DeleteOwnerAccount::class);

        $this->expectException(ValidationException::class);
        $action->execute($this->user, $this->tenant, 'wrong-confirmation');
    }

    public function test_delete_owner_account_action_deletes_tenant_and_user(): void
    {
        $action = app(DeleteOwnerAccount::class);

        $action->execute($this->user, $this->tenant, 'owner@example.com');

        $this->assertDatabaseMissing('tenants', ['id' => $this->tenant->id]);
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }
}
