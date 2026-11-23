<?php

declare(strict_types=1);

namespace App\Domain\Payment;

/**
 * A provider's verified statement about one payment, in BookQu's vocabulary
 * (ADR-0007 decision 1). Provider status words never leave the adapter that
 * builds this object.
 */
final readonly class ProviderOutcome
{
    public const RESULT_UNKNOWN = 'unknown';

    /**
     * @param  list<string>  $references  Payment references in the order the payment should be looked up (order id or provider id).
     * @param  string  $result  `sukses`, `gagal`, `pending` or `unknown`.
     * @param  string|null  $amount  Amount reported by the provider, as a decimal string.
     * @param  string|null  $method  Payment method reported by the provider (stored as `payments.metode`).
     */
    public function __construct(
        public string $provider,
        public array $references,
        public string $result,
        public ?string $amount = null,
        public ?string $currency = null,
        public ?string $transactionId = null,
        public ?string $method = null,
        public ?string $eventTime = null,
    ) {}

    /**
     * The provider could not be asked or gave no usable answer.
     */
    public static function unknown(string $provider, string $reference): self
    {
        return new self($provider, [$reference], self::RESULT_UNKNOWN);
    }

    public function isFinal(): bool
    {
        return in_array($this->result, [PaymentState::STATUS_SUKSES, PaymentState::STATUS_GAGAL], true);
    }
}
