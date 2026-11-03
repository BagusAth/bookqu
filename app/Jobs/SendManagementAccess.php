<?php

namespace App\Jobs;

use App\Actions\CustomerAccess\DeliverManagementAccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SendManagementAccess implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $deliveryId) {}

    public function handle(DeliverManagementAccess $deliver): void
    {
        try {
            $deliver->execute($this->deliveryId);
        } catch (HttpExceptionInterface) {
            // Revoked, stale or corrupt intents fail closed without exposing any credential or recipient.
            Log::warning('Customer access delivery refused', ['delivery_id' => $this->deliveryId]);
        } catch (\Throwable) {
            throw new \RuntimeException('Customer access delivery failed.');
        }
    }
}
