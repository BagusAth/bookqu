<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Infrastructure\Payments\SingaPay\SingaPayPaymentGateway;
use App\Models\Payment;
use Closure;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * The array of resolved driver instances.
     *
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array<string, Closure>
     */
    protected array $customCreators = [];

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return (string) config('payment.default', 'singapay');
    }

    /**
     * Resolve a gateway driver instance by name.
     * Defaults to the configured default driver if $driver is null.
     */
    public function driver(?string $driver = null): PaymentGatewayInterface
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Resolve the gateway driver responsible for a specific Payment instance.
     * Ensures historical payments always use their original provider.
     */
    public function forPayment(Payment $payment): PaymentGatewayInterface
    {
        $provider = strtolower(trim((string) ($payment->provider ?: $payment->metode)));

        if ($provider === 'singapay' || $provider === 'midtrans') {
            return $this->driver($provider);
        }

        return $this->driver();
    }

    /**
     * Create a new driver instance.
     *
     * @throws InvalidArgumentException
     */
    protected function createDriver(string $driver): PaymentGatewayInterface
    {
        if (isset($this->customCreators[$driver])) {
            return $this->customCreators[$driver]($this);
        }

        return match ($driver) {
            'singapay' => app(SingaPayPaymentGateway::class),
            'midtrans' => app(MidtransPaymentGateway::class),
            default    => throw new InvalidArgumentException("Unsupported payment driver [{$driver}]."),
        };
    }

    /**
     * Register a custom driver creator Closure.
     */
    public function extend(string $driver, Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }
}
