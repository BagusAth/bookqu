<?php

declare(strict_types=1);

namespace App\Domain\Payout;

final readonly class PayoutAccountData
{
    public function __construct(
        public string $bankKode,
        public string $nomorRekening,
        public string $namaPemilik,
    ) {}
}
