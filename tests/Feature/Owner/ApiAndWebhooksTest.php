<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Integrations\BookingIntegrationEvent;
use App\Infrastructure\Dns\DnsLookup;
use App\Infrastructure\Integrations\SignedWebhook;
use App\Jobs\DeliverOwnerWebhook;
use App\Models\OwnerApiKey;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OwnerWebhookFailed;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

final class ApiAndWebhooksTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['modules.api' => 'released']);
        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        app(CreateTrialSubscription::class)->execute($this->tenant);
    }

    public function test_api_key_is_tenant_scoped_rate_limited_and_revocable(): void
    {
        $mine = Service::factory()->create(['idtenant' => $this->tenant->id]);
        $other = Tenant::factory()->create();
        $foreign = Service::factory()->create(['idtenant' => $other->id]);
        $foreignKey = OwnerApiKey::factory()->create(['idtenant' => $other->id]);
        $this->actingAs($this->owner)->delete(route('owner.api.keys.revoke', $foreignKey->id))->assertNotFound();
        $created = $this->post(route('owner.api.keys.store'), ['name' => 'Integrasi saya', 'idtenant' => $other->id])->assertOk();
        $token = $created->viewData('credential');
        $key = DB::table('owner_api_keys')->where('idtenant', $this->tenant->id)->first();
        $this->assertSame(hash('sha256', $token), $key->digest);
        $this->get(route('owner.api.index'))->assertOk()->assertDontSee($token);

        $this->withToken($token)->getJson('/api/v1/services')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonMissing(['id' => $foreign->id]);
        for ($request = 2; $request <= 60; $request++) {
            $this->getJson('/api/v1/services')->assertOk();
        }
        $this->getJson('/api/v1/services')->assertTooManyRequests();
        $this->delete(route('owner.api.keys.revoke', $key->id))->assertRedirect();
        $this->getJson('/api/v1/services')->assertUnauthorized();
    }

    public function test_webhook_checks_dns_twice_and_signs_the_actual_body(): void
    {
        Bus::fake([DeliverOwnerWebhook::class]);
        Http::preventStrayRequests();
        Http::fake(['https://callback.example.test/*' => Http::response(status: 204)]);
        $dns = $this->mock(DnsLookup::class);
        $dns->shouldReceive('ipv4')->with('callback.example.test')->andReturn(['8.8.8.8'], ['127.0.0.1'], ['8.8.8.8']);
        $dns->shouldReceive('ipv6')->with('callback.example.test')->andReturn([]);
        $this->actingAs($this->owner)->post(route('owner.api.webhooks.store'), ['name' => 'Private', 'url' => 'https://127.0.0.1/hook'])
            ->assertSessionHasErrors('url');
        $secret = $this->post(route('owner.api.webhooks.store'), ['name' => 'Public', 'url' => 'https://callback.example.test/hook'])
            ->assertOk()->viewData('credential');

        event(new BookingIntegrationEvent('booking.paid', $this->tenant->id, 321));
        $job = Bus::dispatched(DeliverOwnerWebhook::class)->sole();
        $this->attemptFailure($job);
        Http::assertNothingSent();
        $job->handle(app(TenantContext::class), app(SignedWebhook::class));
        Http::assertSent(function (Request $request) use ($secret): bool {
            $time = $request->header('X-BookQu-Timestamp')[0];

            return $request->hasHeader('X-BookQu-Signature', 'sha256='.hash_hmac('sha256', $time.'.'.$request->body(), $secret))
                && $request->hasHeader('X-BookQu-Event-Id', $request['id'])
                && $request['subject_id'] === 321;
        });
        $this->assertDatabaseHas('webhook_deliveries', ['id' => $job->deliveryId, 'attempts' => 2, 'http_status' => 204, 'status' => 'delivered']);
    }

    public function test_exhausted_webhook_notifies_the_owner_and_logs_are_pruned_after_30_days(): void
    {
        Bus::fake([DeliverOwnerWebhook::class]);
        Notification::fake();
        Http::fake(['https://8.8.8.8/*' => Http::response(status: 500)]);
        $this->actingAs($this->owner)->post(route('owner.api.webhooks.store'), ['name' => 'Public', 'url' => 'https://8.8.8.8/hook'])->assertOk();

        event(new BookingIntegrationEvent('booking.paid', $this->tenant->id, 321));
        $job = Bus::dispatched(DeliverOwnerWebhook::class)->sole();
        for ($attempt = 0; $attempt < $job->tries; $attempt++) {
            $this->attemptFailure($job);
        }
        $job->failed(null);
        $job->failed(null);
        Http::assertSentCount(5);
        Notification::assertSentToTimes($this->owner, OwnerWebhookFailed::class, 1);
        $this->assertDatabaseHas('webhook_deliveries', ['id' => $job->deliveryId, 'attempts' => 5, 'http_status' => 500, 'status' => 'failed']);

        $this->travel(31)->days();
        $this->artisan('webhooks:prune')->assertSuccessful();
        $this->assertDatabaseMissing('webhook_deliveries', ['id' => $job->deliveryId]);
    }

    private function attemptFailure(DeliverOwnerWebhook $job): void
    {
        try {
            $job->handle(app(TenantContext::class), app(SignedWebhook::class));
            $this->fail('The job must retry when the endpoint is refused or fails.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Webhook delivery failed.', $exception->getMessage());
        }
    }
}
