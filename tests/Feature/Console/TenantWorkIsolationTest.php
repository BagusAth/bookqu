<?php

namespace Tests\Feature\Console;

use App\Jobs\SendRefundCustomerUpdate;
use App\Models\Booking;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TenantWorkIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_job_reads_only_its_tenants_booking_and_restores_context(): void
    {
        Mail::spy();
        $booking = Booking::factory()->create();
        $other = Tenant::factory()->create();
        $context = app(TenantContext::class);
        $context->setTenantId((int) $booking->idtenant);

        (new SendRefundCustomerUpdate((int) $other->id, (int) $booking->id, 'processed'))->handle();
        Mail::shouldNotHaveReceived('raw');
        $this->assertSame((int) $booking->idtenant, $context->getTenantId());

        (new SendRefundCustomerUpdate((int) $booking->idtenant, (int) $booking->id, 'processed'))->handle();
        Mail::shouldHaveReceived('raw')->once();
    }
}
