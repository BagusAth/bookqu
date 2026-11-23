<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Infrastructure\Logging\AlertMailHandler;
use App\Mail\OperationsAlertMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

/**
 * BQ-021 / BQ-173: errors reach the operators by mail, once per message per quiet period.
 */
class OperationsAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('ops-alert-mails');
        config(['logging.alert_email' => 'ops@bookqu.test']);
    }

    public function test_an_error_is_mailed_once_per_quiet_period(): void
    {
        Log::channel('alert')->critical('PaymentLifecycle: provider payment needs a refund', ['payment_id' => 7]);
        Log::channel('alert')->critical('PaymentLifecycle: provider payment needs a refund', ['payment_id' => 8]);

        Mail::assertSent(OperationsAlertMail::class, 1);
        Mail::assertSent(OperationsAlertMail::class, fn (OperationsAlertMail $mail): bool => $mail->hasTo('ops@bookqu.test')
            && $mail->level === 'critical'
            && str_contains($mail->details['Konteks'], '"payment_id":7'));
    }

    public function test_an_unhandled_exception_is_mailed_with_its_location(): void
    {
        config(['logging.channels.stack.channels' => ['single', 'alert']]);

        report(new RuntimeException('Gateway timeout'));

        Mail::assertSent(OperationsAlertMail::class, fn (OperationsAlertMail $mail): bool => $mail->summary === 'RuntimeException: Gateway timeout'
            && str_contains($mail->details['Lokasi'], 'OperationsAlertTest.php'));
    }

    public function test_warnings_and_a_missing_recipient_send_nothing(): void
    {
        Log::channel('alert')->warning('Slow response');
        config(['logging.alert_email' => null]);
        Log::channel('alert')->error('Mail transport failed');

        Mail::assertNothingSent();
    }

    public function test_an_error_storm_is_capped_per_hour(): void
    {
        for ($i = 0; $i < AlertMailHandler::MAX_PER_HOUR + 5; $i++) {
            Log::channel('alert')->error("Distinct failure {$i}");
        }

        Mail::assertSent(OperationsAlertMail::class, AlertMailHandler::MAX_PER_HOUR);
    }
}
