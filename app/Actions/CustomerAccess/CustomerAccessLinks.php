<?php

namespace App\Actions\CustomerAccess;

use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessProfile;
use App\Domain\CustomerAccess\IssuedGrant;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;

final class CustomerAccessLinks
{
    public function canonical(string $path): string
    {
        $origin = rtrim((string) config('app.url'), '/');
        if (! app()->environment('local', 'testing')) {
            $origin = preg_replace('/\Ahttp:/', 'https:', $origin);
        }

        return $origin.'/'.ltrim($path, '/');
    }

    public function entry(IssuedGrant $issued): string
    {
        return $this->canonical(route('customer.access.show', [], false)).'#'.$issued->credential;
    }

    public function destination(AccessContext $context): string
    {
        if ($context->grant->profile === AccessProfile::Management) {
            $path = $context->payment !== null
                ? route('booking.manage.payment', ['order_id' => $context->payment->order_id], false)
                : route('booking.manage', ['booking_code' => $context->bookings->first()->booking_code], false);

            return $this->canonical($path);
        }

        abort_unless($context->payment !== null, 404);

        return $this->payment($context->payment, $context->payment->status === 'sukses' ? 'invoice' : 'payment');
    }

    public function payment(Payment $payment, string $page): string
    {
        $tenant = $payment->tenant;
        abort_unless($tenant instanceof Tenant, 404);

        return CustomerBookingRoutes::canonical('customer.booking.'.$page, [
            'slug_usaha' => $tenant->slug, 'payment' => $payment->order_id,
        ]);
    }
}
