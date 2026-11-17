<?php

namespace Tests\Feature\Owner;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00.123456', 'Asia/Jakarta'));
    }

    public function test_payment_countdown_is_an_integer_even_with_fractional_seconds_remaining(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::create(['namapaket' => 'small', 'hargabulanan' => 149000, 'maxlayanan' => 5, 'maxbooking' => 300]);
        $payment = Payment::factory()->create([
            'idtenant' => $tenant->id, 'idplan' => $plan->id, 'tipe' => 'subscription',
            'provider' => 'singapay', 'expired_at' => now()->addSeconds(120)->startOfSecond(),
            'payment_url' => 'https://gateway.example.test/pay',
        ]);

        $response = $this->actingAs($tenant->user)->get(route('owner.checkout.payment', $payment))->assertOk();
        $this->assertSame(1, preg_match('/countdown:\s*(\d+),/', $response->getContent(), $match));
        $this->assertSame('119', $match[1]);
    }

    public function test_subscription_compares_configured_features_and_database_limits_to_the_previous_plan(): void
    {
        $tenant = Tenant::factory()->create();
        foreach (['small', 'medium', 'pro'] as $key) {
            Plan::create(['namapaket' => $key, 'hargabulanan' => 149000, 'maxlayanan' => 7, 'maxbooking' => 123]);
            config(['plans.'.$key.'.features' => ['Fitur bersama '.$key, ':services', ':bookings']]);
        }
        $landing = $this->actingAs($tenant->user)->get('/')->assertOk();
        $subscription = $this->get(route('owner.subscription'))->assertOk();

        foreach (['small', 'medium', 'pro'] as $key) {
            $expected = ['Fitur bersama '.$key, 'Hingga 7 layanan', 'Hingga 123 booking/bulan'];
            foreach (['landing' => $landing, 'owner' => $subscription] as $page => $response) {
                $visible = $page === 'owner' && $key !== 'small' ? ['Fitur bersama '.$key] : $expected;
                $document = new \DOMDocument;
                @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
                $labels = (new \DOMXPath($document))->query('//ul[@data-plan-features="'.$key.'"]//span[@data-feature-label]');
                $this->assertSame($visible, array_map(fn (\DOMNode $node): string => trim($node->textContent), iterator_to_array($labels)));
            }
        }
    }
}
