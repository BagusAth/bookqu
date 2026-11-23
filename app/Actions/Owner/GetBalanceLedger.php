<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Domain\Payment\PaymentState;
use App\Domain\Payout\PayoutState;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The balance ledger of the Pembayaran page (owner-portal.md §8.12, AC-PAY-02). There is no ledger
 * table: every movement is read from the records whose writes change `tenants.saldo_platform`, so the
 * ledger total equals the balance by construction:
 *
 * - booking payment in: `TenantBalanceLedger::creditPaidBooking` (payments with `balance_credited_at`);
 * - refund out: `TenantBalanceLedger::debitRefund` (refunds of a credited payment);
 * - payout out: `RequestOwnerPayout` holds the amount when the payout is requested;
 * - payout returned: `ProcessOwnerPayout` gives it back when the payout is rejected or failed.
 */
class GetBalanceLedger
{
    public const PER_PAGE = 20;

    /**
     * @return array{rows: LengthAwarePaginator<int, object>, total: float}
     */
    public function execute(Tenant $tenant, string $pageName = 'mutasi'): array
    {
        $movements = $this->movements((int) $tenant->id);

        $rows = DB::query()->fromSub($movements, 'ledger')
            ->orderByDesc('occurred_at')
            ->orderByDesc('sort_key')
            ->paginate(self::PER_PAGE, ['*'], $pageName)
            ->withQueryString();

        return [
            'rows' => $rows,
            'total' => round((float) DB::query()->fromSub($this->movements((int) $tenant->id), 'ledger')->sum('amount'), 2),
        ];
    }

    private function movements(int $tenantId): Builder
    {
        $bookingCode = '(SELECT b.booking_code FROM bookings b WHERE b.idtenant = payments.idtenant AND (b.idpayment = payments.id OR b.id = payments.idbooking) ORDER BY b.id LIMIT 1)';

        $credits = DB::table('payments')
            ->where('payments.idtenant', $tenantId)
            ->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->whereNotNull('payments.balance_credited_at')
            ->selectRaw("'payment' AS kind, payments.balance_credited_at AS occurred_at, payments.jumlah AS amount, {$bookingCode} AS booking_code, payments.order_id AS reference, payments.id * 4 AS sort_key");

        $refunds = DB::table('refunds')
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->leftJoin('bookings', 'bookings.id', '=', 'refunds.booking_id')
            ->where('payments.idtenant', $tenantId)
            ->whereNotNull('payments.balance_credited_at')
            ->selectRaw("'refund' AS kind, refunds.created_at AS occurred_at, 0 - refunds.jumlah AS amount, bookings.booking_code AS booking_code, payments.order_id AS reference, refunds.id * 4 + 1 AS sort_key");

        $payoutsOut = DB::table('owner_payouts')
            ->where('owner_payouts.idtenant', $tenantId)
            ->selectRaw("'payout' AS kind, COALESCE(owner_payouts.requested_at, owner_payouts.created_at) AS occurred_at, 0 - owner_payouts.jumlah AS amount, NULL AS booking_code, owner_payouts.bank_nama AS reference, owner_payouts.id * 4 + 2 AS sort_key");

        $payoutsBack = DB::table('owner_payouts')
            ->where('owner_payouts.idtenant', $tenantId)
            ->whereIn('owner_payouts.status', [PayoutState::REJECTED, PayoutState::FAILED])
            ->selectRaw("'payout_returned' AS kind, COALESCE(owner_payouts.processed_at, owner_payouts.updated_at) AS occurred_at, owner_payouts.jumlah AS amount, NULL AS booking_code, owner_payouts.bank_nama AS reference, owner_payouts.id * 4 + 3 AS sort_key");

        return $credits->unionAll($refunds)->unionAll($payoutsOut)->unionAll($payoutsBack);
    }
}
