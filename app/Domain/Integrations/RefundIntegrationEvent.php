<?php

declare(strict_types=1);

namespace App\Domain\Integrations;

final readonly class RefundIntegrationEvent
{
    public function __construct(public string $name, public int $tenantId, public int $refundId) {}
}
