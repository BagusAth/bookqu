<?php

namespace App\Actions\CustomerAccess;

use App\Actions\Payment\PaymentLifecycle;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Domain\Payment\PaymentTransition;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

final class CancelCheckoutPayment
{
    public function execute(AccessContext $context, Payment $payment): void
    {
        DB::transaction(function () use ($context, $payment): void {
            app(ResolveCustomerAccess::class)->underLock($context, AccessOperation::CancelPayment, $payment);
            $payment->refresh();
            abort_unless($payment->isPending(), 422);
            app(PaymentLifecycle::class)->transition($payment, PaymentTransition::CancelledByPayer);
        });
    }
}
