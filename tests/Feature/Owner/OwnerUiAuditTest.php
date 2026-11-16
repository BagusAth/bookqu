<?php

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class OwnerUiAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        Mail::fake();
        Notification::fake();
    }

    public function test_deleting_a_calendar_slot_returns_to_calendar_and_removes_the_slot(): void
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => now()->addDay()]);

        $this->actingAs($tenant->user)->delete(route('owner.schedule.slots.destroy', $slot->id), ['return_to' => 'calendar'])
            ->assertRedirect('/owner/calendar')->assertSessionHas('sukses');
        $this->assertDatabaseMissing('schedules', ['id' => $slot->id]);
    }

    public function test_owner_pages_render_when_optional_service_or_account_relations_are_missing(): void
    {
        $tenant = Tenant::factory()->create();
        $booking = Booking::factory()->create(['idtenant' => $tenant->id, 'namapelanggan' => 'Pelanggan Tanpa Relasi']);
        View::composer('owner.bookings', function ($view): void {
            $view->getData()['daftarbooking']->getCollection()->each(fn (Booking $row) => $row->setRelation('layanan', null));
        });
        View::composer('owner.settings', function ($view): void {
            $view->getData()['tenant']->setRelation('user', null);
        });

        $this->actingAs($tenant->user)->get(route('owner.bookings'))->assertOk()
            ->assertSee($booking->namapelanggan)->assertSeeText('Layanan dihapus');
        $this->get(route('owner.settings'))->assertOk()->assertSee($tenant->user->email);
    }
}
