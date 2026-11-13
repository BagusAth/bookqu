<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-05 10:20:00', 'Asia/Jakarta'));
        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'is_active' => true, 'durasi' => 60]);
        app(CreateTrialSubscription::class)->execute($this->tenant);
        $this->actingAs($this->owner);
    }

    public function test_shared_owner_cancellation_form_requires_reason_and_confirms_before_submit(): void
    {
        $slot = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id]);
        $booking = Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $slot->id,
            'status' => 'paid',
        ]);

        $panelUrl = route('owner.bookings.panel', $booking->id);
        $this->get(route('owner.bookings'))->assertOk()->assertSee('open-booking-panel');
        $this->get(route('owner.calendar'))->assertOk()->assertSee('open-booking-panel')
            ->assertSee('action.reason.name')->assertSee('required maxlength="500"', false)
            ->assertSee('form.reportValidity()', false)->assertSee('form.requestSubmit()', false);

        $response = $this->getJson($panelUrl)->assertOk();
        $cancel = collect($response->json('actions'))->firstWhere('label', 'Batalkan booking');
        $this->assertSame('cancelled', $cancel['fields']['status']);
        $this->assertSame('reason', $cancel['reason']['name']);
        $this->assertSame('Batalkan booking?', $cancel['confirm']['title']);

        $this->patch($cancel['url'], ['status' => 'cancelled'])->assertSessionHasErrors('error');
        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_service_duration_and_bulk_slot_interval_must_be_multiples_of_five(): void
    {
        $payload = ['namalayanan' => 'Sesi baru', 'harga' => 100000, 'durasi' => 61, 'is_active' => true];
        $this->post('/owner/programs', $payload)->assertSessionHasErrors('durasi');
        $this->put('/owner/programs/'.$this->service->id, $payload)->assertSessionHasErrors('durasi');
        $this->assertDatabaseMissing('services', ['namalayanan' => 'Sesi baru']);

        $payload['durasi'] = 65;
        $this->post('/owner/programs', $payload)->assertSessionHasNoErrors();
        $this->put('/owner/programs/'.$this->service->id, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['id' => $this->service->id, 'durasi' => 65]);

        $slots = [
            'jenisslot' => 'harian', 'idlayanan' => $this->service->id,
            'tanggal' => now()->addDay()->toDateString(), 'jammulai' => '09:00', 'jamselesai' => '11:10',
            'intervalslot' => 61,
        ];
        $this->post(route('owner.schedule.bulk-store'), $slots)->assertSessionHasErrors('intervalslot');
        $this->assertDatabaseCount('schedules', 0);
        $slots['intervalslot'] = 65;
        $this->post(route('owner.schedule.bulk-store'), $slots)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('schedules', 2);
    }
}
