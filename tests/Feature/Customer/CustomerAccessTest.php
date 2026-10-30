<?php

namespace Tests\Feature\Customer;

use App\Actions\Booking\CancelBooking;
use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Actions\CustomerAccess\DeliverManagementAccess;
use App\Actions\CustomerAccess\IssueCustomerAccess;
use App\Actions\CustomerAccess\RequestManagementDelivery;
use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Actions\CustomerAccess\RevokeCustomerAccess;
use App\Domain\CustomerAccess\AccessProfile;
use App\Domain\CustomerAccess\IssuedGrant;
use App\Mail\BookingGroupInvoiceMail;
use App\Models\Booking;
use App\Models\CustomerAccessDelivery;
use App\Models\CustomerAccessGrant;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        Mail::fake();
        config(['app.url' => 'http://localhost']);
    }

    /** @return array{Payment, Booking} */
    private function reservation(string $status = 'sukses', ?Tenant $tenant = null): array
    {
        $tenant ??= Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'durasi' => 60]);
        $schedule = Schedule::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id,
            'tanggal' => '2026-11-05', 'jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00',
        ]);
        $payment = Payment::factory()->create([
            'idtenant' => $tenant->id, 'status' => $status, 'provider' => 'manual',
            'expired_at' => now()->addHour(), 'manage_token' => bin2hex(random_bytes(32)),
        ]);
        $booking = Booking::factory()->create([
            'idtenant' => $tenant->id, 'idpayment' => $payment->id, 'idlayanan' => $service->id,
            'idschedule' => $schedule->id, 'tanggalbooking' => '2026-11-05',
            'status' => $status === 'sukses' ? 'paid' : 'pending',
        ]);

        return [$payment, $booking];
    }

    private function enter(Payment|Booking $subject, AccessProfile $profile = AccessProfile::Management): IssuedGrant
    {
        app(TenantContext::class)->clear();
        $issued = app(IssueCustomerAccess::class)->execute($subject, $profile);
        $this->postJson('/access', ['credential' => $issued->credential])->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer');

        return $issued;
    }

    /** @return array<string, array{string, string}> */
    public static function storefrontRoutes(): array
    {
        return [
            'payment' => ['GET', ''],
            'invoice' => ['GET', '/invoice'],
            'status' => ['POST', '/check-status'],
            'callback' => ['POST', '/callback'],
            'cancel' => ['POST', '/cancel'],
        ];
    }

    #[DataProvider('storefrontRoutes')]
    public function test_every_storefront_route_requires_a_grant_for_the_exact_payment(string $method, string $suffix): void
    {
        [$payment] = $this->reservation('pending');
        $path = '/'.$payment->tenant->slug.'/booking/payment/'.$payment->order_id.$suffix;

        $this->call($method, $path)->assertNotFound()->assertSee('Halaman tidak tersedia.');

        [$foreign] = $this->reservation('pending');
        $this->enter($foreign, AccessProfile::Checkout);
        $this->call($method, $path)->assertNotFound();

        [$sibling] = $this->reservation('pending', $payment->tenant);
        $this->enter($sibling, AccessProfile::Checkout);
        $this->call($method, $path)->assertNotFound();

        $this->enter($payment, AccessProfile::Checkout);
        $response = $this->call($method, $path);
        if ($suffix === '' || $suffix === '/check-status' || $suffix === '/callback') {
            $response->assertOk();
        } else {
            $response->assertRedirect();
        }
        if ($suffix === '/cancel') {
            $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'gagal']);
        }
    }

    /** @return array<string, array{string, string}> */
    public static function managementRoutes(): array
    {
        return [
            'group' => ['GET', 'group'], 'group invoice' => ['GET', 'group-invoice'],
            'detail' => ['GET', ''], 'invoice' => ['GET', '/invoice'],
            'reschedule' => ['GET', '/reschedule'], 'slots' => ['GET', '/reschedule/slots?tanggal=2026-11-06'],
            'cancel' => ['POST', '/cancel'], 'reschedule write' => ['POST', '/reschedule'],
            'review' => ['POST', '/review'],
        ];
    }

    #[DataProvider('managementRoutes')]
    public function test_management_routes_reject_identifiers_checkout_and_foreign_grants(string $method, string $suffix): void
    {
        [$payment, $booking] = $this->reservation();
        $path = match ($suffix) {
            'group' => '/manage/payment/'.$payment->order_id,
            'group-invoice' => '/manage/payment/'.$payment->order_id.'/invoice',
            default => '/manage/'.$booking->booking_code.$suffix,
        };
        $this->call($method, $path)->assertNotFound();
        $this->enter($payment, AccessProfile::Checkout);
        $this->call($method, $path)->assertNotFound();
        [$foreign] = $this->reservation();
        $this->enter($foreign);
        $this->call($method, $path)->assertNotFound();

        $this->enter($payment);
        $response = $this->call($method, $path);
        if ($method === 'GET') {
            $response->assertOk();
        } else {
            $response->assertRedirect();
        }
    }

    public function test_credentials_are_hash_only_and_session_and_html_contain_no_bearer_material(): void
    {
        [$payment, $booking] = $this->reservation();
        $issued = $this->enter($payment);
        $stored = DB::table('customer_access_grants')->where('id', $issued->grant->id)->first();
        $secret = explode('.', $issued->credential)[1];
        $this->assertSame(hash('sha256', $secret), $stored->secret_hash);
        $this->assertNotSame($payment->email_pembayar, $stored->recipient);
        $this->assertStringNotContainsString($secret, json_encode(session()->all()));
        $this->assertStringNotContainsString('secret_hash', $issued->grant->toJson());
        $this->assertStringNotContainsString('manage_token', $payment->toJson());
        foreach (['/manage/'.$booking->booking_code, '/manage/payment/'.$payment->order_id,
            '/manage/'.$booking->booking_code.'/invoice', '/manage/payment/'.$payment->order_id.'/invoice',
            '/'.$payment->tenant->slug.'/booking/payment/'.$payment->order_id.'/invoice'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            foreach ([$secret, $payment->manage_token, $booking->cancellation_token, $booking->reschedule_token, '?token=', 'name="token"'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $html);
            }
            preg_match_all('/href="([^"<>]*(?:calendar.google.com|api.whatsapp.com)[^"<>]*)"/', $html, $urls);
            foreach ($urls[1] as $url) {
                $this->assertStringNotContainsString('/manage/', urldecode(html_entity_decode($url)));
            }
        }
    }

    public function test_legacy_tokens_are_rejected_even_when_they_match_the_record(): void
    {
        [$payment, $booking] = $this->reservation();
        foreach ([$payment->manage_token, $booking->cancellation_token, $booking->reschedule_token] as $token) {
            $this->get('/manage/'.$booking->booking_code.'?token='.$token)->assertNotFound();
        }
        $this->postJson('/access', ['credential' => $booking->cancellation_token])->assertNotFound();
    }

    public function test_expiry_individual_revocation_family_replacement_and_membership_drift_invalidate_sessions(): void
    {
        [$payment, $booking] = $this->reservation();
        $issued = $this->enter($payment);
        $issued->grant->update(['expires_at' => now()]);
        $this->get('/manage/'.$booking->booking_code)->assertNotFound();
        $issued = $this->enter($payment);
        app(RevokeCustomerAccess::class)->execute($issued->grant);
        $this->get('/manage/'.$booking->booking_code)->assertNotFound();
        $issued = $this->enter($payment);
        app(RevokeCustomerAccess::class)->execute($issued->grant, true);
        $this->get('/manage/'.$booking->booking_code)->assertNotFound();
        $this->assertSame(2, (int) $payment->fresh()->customer_access_version);
        $this->enter($payment);
        [$other, $newMember] = $this->reservation('sukses', $payment->tenant);
        $newMember->update(['idpayment' => $payment->id]);
        $this->get('/manage/'.$booking->booking_code)->assertNotFound();
    }

    public function test_idle_and_absolute_session_timeouts_do_not_extend_grant_expiry(): void
    {
        [$payment, $booking] = $this->reservation();
        $issued = $this->enter($payment);
        $expiry = $issued->grant->expires_at->toISOString();
        $this->travelTo(now()->addMinutes(30));
        $this->get('/manage/'.$booking->booking_code)->assertNotFound();
        $this->enter($payment);
        session()->put('customer_access.started_at', now()->subHours(12)->timestamp);
        $this->get('/manage/'.$booking->booking_code)->assertNotFound();
        $this->assertSame($expiry, $issued->grant->fresh()->expires_at->toISOString());
    }

    public function test_expiry_uses_the_fixed_adr_boundaries(): void
    {
        [$payment] = $this->reservation();
        $checkout = app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Checkout);
        $management = app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Management);
        $this->assertSame(now()->addHours(25)->timestamp, $checkout->grant->expires_at->timestamp);
        $this->assertSame(Carbon::parse('2026-12-05 11:00:00', 'Asia/Jakarta')->timestamp, $management->grant->expires_at->timestamp);
    }

    public function test_queued_delivery_captures_recipient_and_rejects_stale_family_generations(): void
    {
        Queue::fake();
        [$payment] = $this->reservation();
        app(RequestManagementDelivery::class)->execute($payment, 'confirmed');
        // scope-bypass: inspect trusted test fixtures without a request tenant; authorization is exercised through the real resolver.
        $delivery = CustomerAccessDelivery::withoutGlobalScopes()->firstOrFail();
        $originalRecipient = $payment->email_pembayar;
        $payment->update(['email_pembayar' => 'changed@example.test']);
        app(DeliverManagementAccess::class)->execute($delivery->id);
        Mail::assertSent(BookingGroupInvoiceMail::class, fn ($mail) => $mail->hasTo($originalRecipient) && str_contains($mail->manageUrl, '/access#'));
        app(RequestManagementDelivery::class)->execute($payment, 'confirmed');
        // scope-bypass: inspect trusted test fixtures without a request tenant; authorization is exercised through the real resolver.
        $stale = CustomerAccessDelivery::withoutGlobalScopes()->latest('id')->firstOrFail();
        // scope-bypass: inspect trusted test fixtures without a request tenant; authorization is exercised through the real resolver.
        $grant = CustomerAccessGrant::withoutGlobalScopes()->firstOrFail();
        app(RevokeCustomerAccess::class)->execute($grant, true);
        $this->expectException(HttpException::class);
        app(DeliverManagementAccess::class)->execute($stale->id);
    }

    public function test_bootstrap_has_no_external_resources_and_rejects_invalid_attempts_with_rate_limit(): void
    {
        $this->get('/access')->assertOk()->assertSee('history.replaceState', false)
            ->assertDontSee('src=', false)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $this->postJson('/access', ['credential' => 'invalid'])->assertNotFound();
        }
        $this->postJson('/access', ['credential' => 'invalid'])->assertTooManyRequests();
    }

    public function test_custom_domain_cannot_host_private_credentials_or_resume_a_canonical_session(): void
    {
        [$payment] = $this->reservation();
        $issued = $this->enter($payment);
        $payment->tenant->update(['custom_domain' => 'studio.example.test']);
        $this->get('http://studio.example.test/access')->assertNotFound();
        $this->get('http://studio.example.test/booking/payment/'.$payment->order_id)->assertNotFound();
        $this->assertStringStartsWith('http://localhost/access#', app(CustomerAccessLinks::class)->entry($issued));
    }

    public function test_revocation_between_preflight_and_business_write_is_rechecked_under_lock(): void
    {
        [$payment, $booking] = $this->reservation();
        $issued = $this->enter($payment);
        $context = app(ResolveCustomerAccess::class)->execute($issued->grant->id);
        app(RevokeCustomerAccess::class)->execute($issued->grant, true);
        try {
            app(CancelBooking::class)->execute($booking, access: $context);
            $this->fail('Revoked preflight must not authorize cancellation.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'paid']);
    }

    public function test_individually_revoked_source_cannot_mint_a_delayed_reschedule_email(): void
    {
        Queue::fake();
        [$payment] = $this->reservation();
        $issued = $this->enter($payment);
        $source = app(ResolveCustomerAccess::class)->execute($issued->grant->id);
        app(RequestManagementDelivery::class)->execute($payment, 'rescheduled', $source);
        // scope-bypass: inspect trusted test fixtures without a request tenant; authorization is exercised through the real resolver.
        $delivery = CustomerAccessDelivery::withoutGlobalScopes()->firstOrFail();
        app(RevokeCustomerAccess::class)->execute($issued->grant);

        try {
            app(DeliverManagementAccess::class)->execute($delivery->id);
            $this->fail('A revoked source must not issue a new grant.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('customer_access_grants', 1);
        Mail::assertNothingSent();
    }

    public function test_uncertain_delivery_retries_are_bounded_and_do_not_revoke_earlier_links(): void
    {
        Queue::fake();
        [$payment] = $this->reservation();
        app(RequestManagementDelivery::class)->execute($payment, 'confirmed');
        // scope-bypass: inspect trusted test fixtures without a request tenant; authorization is exercised through the real resolver.
        $delivery = CustomerAccessDelivery::withoutGlobalScopes()->firstOrFail();
        Mail::shouldReceive('to->send')->times(3)->andThrow(new \RuntimeException('Transport unavailable'));

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                app(DeliverManagementAccess::class)->execute($delivery->id);
                $this->fail('The fixture transport must fail.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Transport unavailable', $exception->getMessage());
            }
        }
        app(DeliverManagementAccess::class)->execute($delivery->id);
        $this->assertSame(3, $delivery->fresh()->attempts);
        $this->assertDatabaseCount('customer_access_grants', 3);
        // scope-bypass: inspect trusted test fixtures without a request tenant; authorization is exercised through the real resolver.
        foreach (CustomerAccessGrant::withoutGlobalScopes()->get() as $grant) {
            $this->assertSame($grant->id, app(ResolveCustomerAccess::class)->execute($grant->id)->grant->id);
        }
    }

    public function test_management_lifetime_is_capped_at_180_days_for_distant_reservations(): void
    {
        [$payment, $booking] = $this->reservation();
        $booking->update(['tanggalbooking' => now()->addDays(300)->toDateString()]);

        $issued = app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Management);

        $this->assertSame(now()->addDays(180)->timestamp, $issued->grant->expires_at->timestamp);
    }

    public function test_pending_checkout_without_a_payment_deadline_cannot_issue_access(): void
    {
        [$payment] = $this->reservation('pending');
        $payment->update(['expired_at' => null]);

        $this->expectException(\LogicException::class);
        app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Checkout);
    }

    public function test_credential_exchange_requires_csrf_outside_the_testing_bypass(): void
    {
        [$payment] = $this->reservation();
        $issued = app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Checkout);
        $this->app->instance('env', 'local');

        $this->postJson('/access', ['credential' => $issued->credential])->assertStatus(419);
        $this->assertNull(session('customer_access'));
    }
}
