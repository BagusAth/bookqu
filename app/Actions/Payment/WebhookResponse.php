<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransitionOutcome;

/**
 * Maps Outcome intake results to the webhook response every provider receives.
 * A payment of another provider answers like a missing one, so a webhook cannot
 * probe which order ids exist.
 */
final class WebhookResponse
{
    /**
     * @return array{success: bool, code: int, message: string}
     */
    public static function forRejection(?string $rejection): array
    {
        [$code, $message] = match ($rejection) {
            OutcomeIntakeResult::REJECTED_PAYMENT_NOT_FOUND, OutcomeIntakeResult::REJECTED_PROVIDER_MISMATCH => [404, 'Payment not found'],
            OutcomeIntakeResult::REJECTED_TENANT_INVALID => [422, 'Tenant isolation violation'],
            OutcomeIntakeResult::REJECTED_AMOUNT_MISSING => [422, 'Successful transaction is missing amount or currency'],
            OutcomeIntakeResult::REJECTED_CURRENCY_MISMATCH => [422, 'Transaction currency mismatch'],
            OutcomeIntakeResult::REJECTED_AMOUNT_MISMATCH => [422, 'Transaction amount mismatch'],
            default => [422, 'Provider outcome rejected'],
        };

        return ['success' => false, 'code' => $code, 'message' => $message];
    }

    /**
     * @return array{success: bool, code: int, message: string}
     */
    public static function forAccepted(OutcomeIntakeResult $intake): array
    {
        $message = 'OK';

        if ($intake->transition?->outcome === PaymentTransitionOutcome::AlreadyFinal) {
            $message = $intake->status() === PaymentState::STATUS_SUKSES
                ? 'Payment already processed successfully'
                : 'Payment already failed or cancelled';
        }

        return ['success' => true, 'code' => 200, 'message' => $message];
    }
}
