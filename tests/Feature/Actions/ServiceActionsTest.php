<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Service\CreateService;
use App\Actions\Service\DeleteService;
use App\Actions\Service\ToggleServiceStatus;
use App\Actions\Service\UpdateService;
use App\Models\Booking;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::create([
            'iduser'      => $this->user->id,
            'namabisnis'  => 'Action Test Spa',
            'slug'        => 'action-test-spa',
            'jenisbisnis' => 'Spa',
            'nomorhp'     => '08123456789',
            'alamat'      => 'Jl. Test No. 1',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    public function test_create_service_action_creates_service_and_clears_cache(): void
    {
        $action = app(CreateService::class);

        $service = $action->execute($this->tenant, [
            'namalayanan' => 'Aromatherapy Massage',
            'harga'       => 150000,
            'durasi'      => 60,
            'deskripsi'   => 'Relaksasi tubuh dan pikiran',
        ]);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertDatabaseHas('services', [
            'id'          => $service->id,
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Aromatherapy Massage',
            'harga'       => 150000,
            'durasi'      => 60,
        ]);
    }

    public function test_create_service_enforces_plan_quota_limit(): void
    {
        $plan = Plan::create([
            'namapaket'    => 'small',
            'hargabulanan' => 50000,
            'maxlayanan'   => 1,
            'maxbooking'   => 50,
            'isunlimited'  => false,
        ]);

        Subscription::create([
            'idtenant' => $this->tenant->id,
            'idplan'   => $plan->id,
            'status'   => 'active',
        ]);

        $action = app(CreateService::class);

        $action->execute($this->tenant, [
            'namalayanan' => 'Layanan Pertama',
            'harga'       => 50000,
            'durasi'      => 30,
        ]);

        $this->expectException(ValidationException::class);

        $action->execute($this->tenant, [
            'namalayanan' => 'Layanan Kedua Yang Melebihi Kuota',
            'harga'       => 75000,
            'durasi'      => 45,
        ]);
    }

    public function test_update_service_action_modifies_attributes(): void
    {
        $service = Service::create([
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Refleksi Kaki',
            'harga'       => 80000,
            'durasi'      => 45,
        ]);

        $action = app(UpdateService::class);

        $updated = $action->execute($this->tenant, $service, [
            'namalayanan' => 'Refleksi Kaki Premium',
            'harga'       => 120000,
            'durasi'      => 60,
            'deskripsi'   => 'Durasi lebih lama dengan essential oil',
            'is_active'   => true,
        ]);

        $this->assertEquals('Refleksi Kaki Premium', $updated->namalayanan);
        $this->assertEquals(120000, $updated->harga);
        $this->assertEquals(60, $updated->durasi);
    }

    public function test_toggle_service_status_action_switches_active_state(): void
    {
        $service = Service::create([
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Totok Wajah',
            'harga'       => 90000,
            'durasi'      => 30,
            'is_active'   => true,
        ]);

        $action = app(ToggleServiceStatus::class);

        $toggled = $action->execute($this->tenant, $service);
        $this->assertFalse($toggled->is_active);

        $toggledAgain = $action->execute($this->tenant, $service);
        $this->assertTrue($toggledAgain->is_active);
    }

    public function test_delete_service_action_blocked_if_active_bookings_exist(): void
    {
        $service = Service::create([
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Facial Deep Clean',
            'harga'       => 200000,
            'durasi'      => 60,
        ]);

        $schedule = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $service->id,
            'tanggal'     => now()->addDays(2)->toDateString(),
            'jam_mulai'   => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status'      => 'tersedia',
        ]);

        Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'tanggalbooking' => now()->addDays(2),
            'jam'            => '10:00:00',
            'namapelanggan'  => 'Pelanggan Aktif',
            'nomorhp'        => '08123456789',
            'email'          => 'pelanggan@example.com',
            'status'         => 'paid',
        ]);

        $action = app(DeleteService::class);

        $this->expectException(ValidationException::class);
        $action->execute($this->tenant, $service);
    }

    public function test_delete_service_action_succeeds_without_active_bookings(): void
    {
        $service = Service::create([
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Layanan Lama',
            'harga'       => 50000,
            'durasi'      => 30,
        ]);

        $action = app(DeleteService::class);
        $name = $action->execute($this->tenant, $service);

        $this->assertEquals('Layanan Lama', $name);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }
}
