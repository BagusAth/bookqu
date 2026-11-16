<?php

declare(strict_types=1);

namespace App\Contracts\Integrations;

use App\Domain\Integrations\BookingIntegrationEvent;
use App\Models\OwnerIntegration as Settings;

interface OwnerIntegration
{
    public function connect(Settings $settings, string $code, string $verifier): void;

    public function test(Settings $settings): void;

    public function disconnect(Settings $settings): void;

    public function deliver(Settings $settings, BookingIntegrationEvent $event): void;
}
