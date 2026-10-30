<?php

namespace Tests\Concerns;

use App\Actions\CustomerAccess\IssueCustomerAccess;
use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Domain\CustomerAccess\AccessProfile;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Testing\TestResponse;

trait UsesCustomerAccess
{
    protected function grantCustomerAccess(Payment|Booking $subject, AccessProfile $profile = AccessProfile::Management): static
    {
        app(TenantContext::class)->clear();
        $issued = app(IssueCustomerAccess::class)->execute($subject, $profile);
        $this->withSession(['customer_access' => [
            'grant_id' => $issued->grant->id, 'started_at' => now()->timestamp, 'last_seen_at' => now()->timestamp,
        ]]);

        return $this;
    }

    protected function assertCheckoutHandoff(TestResponse $response, Payment $payment): void
    {
        $response->assertRedirect();
        $url = $response->headers->get('Location');
        $this->assertSame('/access', parse_url($url, PHP_URL_PATH));
        $credential = parse_url($url, PHP_URL_FRAGMENT);
        app(TenantContext::class)->clear();
        $context = app(ResolveCustomerAccess::class)->credential($credential);
        $this->assertSame((int) $payment->id, (int) $context->payment->id);
        $this->assertSame(AccessProfile::Checkout, $context->grant->profile);
        $this->assertArrayNotHasKey('credential', session()->get('customer_access', []));
    }
}
