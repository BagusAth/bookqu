<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SingleSlotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_close_reopen_override_and_reset_one_slot(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        app(CreateTrialSubscription::class)->execute($tenant);
        $slot = $this->slot($tenant);
        $other = $this->slot($tenant);
        $url = route('owner.schedule.slots.update', $slot->id);

        $this->actingAs($owner)->patch($url, ['operation' => 'close'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', ['id' => $slot->id, 'status' => 'tidak_tersedia']);
        $this->assertDatabaseHas('schedules', ['id' => $other->id, 'status' => 'tersedia']);
        $this->patch($url, ['operation' => 'open'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', ['id' => $slot->id, 'status' => 'tersedia']);
        $this->patch($url, ['operation' => 'price', 'harga_override' => 125000])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', ['id' => $slot->id, 'harga_override' => 125000]);
        $this->patch($url, ['operation' => 'price', 'harga_override' => null])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', ['id' => $slot->id, 'harga_override' => null]);
    }

    public function test_slot_updates_refuse_an_occupied_or_foreign_slot(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        app(CreateTrialSubscription::class)->execute($tenant);
        $occupied = $this->slot($tenant);
        $foreign = $this->slot(Tenant::factory()->create());
        Booking::factory()->create([
            'idtenant' => $tenant->id,
            'idschedule' => $occupied->id,
            'idlayanan' => $occupied->idlayanan,
            'status' => 'paid',
        ]);

        $this->actingAs($owner);
        foreach (['open', 'close', 'price'] as $operation) {
            $payload = ['operation' => $operation, 'harga_override' => 50000];
            $this->patchJson(route('owner.schedule.slots.update', $occupied->id), $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('slot');
            $this->patchJson(route('owner.schedule.slots.update', $foreign->id), $payload)->assertNotFound();
        }
        $this->assertDatabaseHas('schedules', ['id' => $occupied->id, 'status' => 'tersedia', 'harga_override' => null]);
        $this->assertDatabaseHas('schedules', ['id' => $foreign->id, 'status' => 'tersedia', 'harga_override' => null]);
    }

    private function slot(Tenant $tenant): Schedule
    {
        $service = Service::factory()->create(['idtenant' => $tenant->id]);

        return Schedule::factory()->create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'status' => 'tersedia',
            'harga_override' => null,
        ]);
    }
}
