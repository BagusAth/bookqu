<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Mail\OperationsAlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Throwable;

/**
 * Mails log records at or above the channel level to OPS_ALERT_EMAIL (BQ-021, BQ-173).
 *
 * The same message is mailed once per quiet period and at most a fixed number of mails leave per
 * hour, so an error storm cannot flood the inbox. Sending never throws back into the logger.
 */
final class AlertMailHandler extends AbstractProcessingHandler
{
    public const QUIET_SECONDS = 900;

    public const MAX_PER_HOUR = 20;

    private static bool $sending = false;

    protected function write(LogRecord $record): void
    {
        $recipient = (string) config('logging.alert_email');

        if ($recipient === '' || self::$sending) {
            return;
        }

        self::$sending = true;

        try {
            $exception = $record->context['exception'] ?? null;
            $summary = $exception instanceof Throwable ? $exception::class.': '.$exception->getMessage() : $record->message;

            if (! Cache::add('ops-alert:'.sha1($summary), true, self::QUIET_SECONDS)
                || RateLimiter::tooManyAttempts('ops-alert-mails', self::MAX_PER_HOUR)) {
                return;
            }
            RateLimiter::hit('ops-alert-mails', 3600);

            Mail::to($recipient)->send(new OperationsAlertMail(strtolower($record->level->getName()), $summary, $this->details($record, $exception)));
        } catch (Throwable) {
            // The record is already in the log file; a failed alert must not break the request.
        } finally {
            self::$sending = false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function details(LogRecord $record, mixed $exception): array
    {
        $details = ['Waktu' => $record->datetime->format('Y-m-d H:i:s T')];

        if (! app()->runningInConsole()) {
            $details['Request'] = request()->method().' '.request()->path();
        }

        if ($exception instanceof Throwable) {
            $details['Lokasi'] = $exception->getFile().':'.$exception->getLine();
            $details['Pesan log'] = $record->message;
        } else {
            $context = json_encode($record->context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            $details['Konteks'] = mb_strimwidth((string) $context, 0, 2000, '…');
        }

        return $details;
    }
}
