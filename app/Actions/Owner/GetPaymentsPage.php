<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payout\PayoutEligibility;
use App\Domain\Payout\PayoutState;
use App\Models\Booking;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Support\StatusBadge;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The Pembayaran page (owner-portal.md §8.12): Ringkasan, Transaksi, Saldo & Pencairan, Rekening and
 * Pengaturan. Only the active tab's lists are loaded. Balance changes stay in the existing payout,
 * payment lifecycle and ledger actions; this only reads.
 */
class GetPaymentsPage
{
    public const TABS = [
        'ringkasan' => 'Ringkasan',
        'transaksi' => 'Transaksi',
        'saldo' => 'Saldo & Pencairan',
        'rekening' => 'Rekening',
        'pengaturan' => 'Pengaturan',
    ];

    public function __construct(private GetBalanceLedger $getBalanceLedger) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, User $user, ?string $tab, array $filters = []): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $activeTab = array_key_exists((string) $tab, self::TABS) ? (string) $tab : 'ringkasan';
        $accounts = TenantPayoutAccount::where('idtenant', $tenant->id)->orderByDesc('is_default')->latest()->get();
        $eligibilityReasons = PayoutEligibility::reasons($tenant, $user);
        $transactionStatuses = [
            PaymentState::STATUS_SUKSES => 'Berhasil',
            PaymentState::STATUS_PENDING => 'Menunggu pembayaran',
            PaymentState::STATUS_GAGAL => 'Gagal',
        ];
        $status = is_string($filters['status'] ?? null) ? $filters['status'] : '';
        $transactionFilters = [
            'search' => is_string($filters['search'] ?? null) ? mb_substr(trim($filters['search']), 0, 100) : '',
            'status' => isset($transactionStatuses[$status]) ? $status : '',
            'from' => self::validDate($filters['from'] ?? null),
            'to' => self::validDate($filters['to'] ?? null),
        ];

        $page = [
            'activeTab' => $activeTab,
            'summary' => $this->summary($tenant),
            'accounts' => $accounts,
            'maxAccounts' => (int) config('payouts.max_accounts', 3),
            'eligibilityReasons' => $eligibilityReasons,
            'payoutRules' => $this->payoutRules($tenant),
            'requestKey' => (string) Str::uuid(),
        ];

        return $page + match ($activeTab) {
            'ringkasan' => ['latest' => $this->transactions($tenant, 5, 'transaksi', false)],
            'transaksi' => [
                'transactions' => $this->transactions($tenant, 20, 'page', true, $transactionFilters),
                'transactionFilters' => $transactionFilters,
                'transactionStatuses' => $transactionStatuses,
            ],
            'saldo' => [
                'ledger' => $this->getBalanceLedger->execute($tenant),
                'payouts' => OwnerPayout::where('idtenant', $tenant->id)->with('payoutAccount')
                    ->latest('requested_at')->latest('id')->paginate(15, ['*'], 'pencairan')->withQueryString()
                    ->through(fn (OwnerPayout $payout): array => $this->payoutRow($payout)),
            ],
            'pengaturan' => ['explanation' => $this->explanation($tenant)],
            default => [],
        };
    }

    private static function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts)) {
            return null;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : null;
    }

    /**
     * @return array{available: float, inProcess: float, withdrawn: float, counts: array<string, int>}
     */
    private function summary(Tenant $tenant): array
    {
        $payouts = OwnerPayout::where('idtenant', $tenant->id);
        $bookingPayments = Payment::where('idtenant', $tenant->id)->where('tipe', PaymentState::TIPE_BOOKING);

        return [
            'available' => (float) $tenant->saldo_platform,
            'inProcess' => (float) (clone $payouts)->whereIn('status', [PayoutState::PENDING, PayoutState::APPROVED])->sum('jumlah'),
            'withdrawn' => (float) (clone $payouts)->where('status', PayoutState::SUCCESS)->sum('jumlah'),
            'counts' => [
                'sukses' => (clone $bookingPayments)->where('status', PaymentState::STATUS_SUKSES)->count(),
                'pending' => (clone $bookingPayments)->where('status', PaymentState::STATUS_PENDING)->count(),
                'gagal' => (clone $bookingPayments)->where('status', PaymentState::STATUS_GAGAL)->count(),
                'refund' => Refund::whereIn('payment_id', (clone $bookingPayments)->select('id'))->distinct()->count('payment_id'),
            ],
        ];
    }

    /**
     * Customer payments with their booking, service and refunded amount.
     *
     * @param  array{search?: string, status?: string, from?: ?string, to?: ?string}  $filters
     * @return LengthAwarePaginator<int, mixed>
     */
    private function transactions(Tenant $tenant, int $perPage, string $pageName, bool $withQuery, array $filters = []): LengthAwarePaginator
    {
        $payments = Payment::where('idtenant', $tenant->id)
            ->where('tipe', PaymentState::TIPE_BOOKING)
            ->when($filters['status'] ?? '', fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['search'] ?? '', function (Builder $query, string $search): void {
                $term = '%'.$search.'%';
                $matchBooking = fn (Builder $bookings): Builder => $bookings->where(fn (Builder $details) => $details
                    ->whereLike('booking_code', $term)->orWhereLike('namapelanggan', $term));
                $query->where(fn (Builder $matching) => $matching->whereLike('order_id', $term)
                    ->orWhereLike('nama_pembayar', $term)
                    ->orWhereHas('bookings', $matchBooking)
                    ->orWhereHas('booking', $matchBooking));
            })
            ->with(['bookings:id,idpayment,idlayanan,booking_code,namapelanggan', 'bookings.layanan:id,namalayanan', 'booking:id,idlayanan,booking_code,namapelanggan', 'booking.layanan:id,namalayanan'])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], $pageName);
        if ($withQuery) {
            $payments->withQueryString();
        }

        /** @var Collection<int, float> $refunded */
        $refunded = Refund::whereIn('payment_id', $payments->getCollection()->pluck('id'))
            ->selectRaw('payment_id, SUM(jumlah) AS total')
            ->groupBy('payment_id')
            ->pluck('total', 'payment_id');

        return $payments->through(function (Payment $payment) use ($refunded): array {
            $bookings = $payment->bookings->isNotEmpty() ? $payment->bookings : collect([$payment->booking])->filter();
            $first = $bookings->first();
            $refund = (float) ($refunded[$payment->id] ?? 0);

            return [
                'orderId' => (string) ($payment->order_id ?: 'PAY-'.$payment->id),
                'time' => $payment->created_at?->timezone('Asia/Jakarta')->translatedFormat('j M Y, H:i'),
                'customer' => (string) ($first->namapelanggan ?? '-'),
                'service' => $bookings->map(fn ($booking): string => (string) ($booking->layanan->namalayanan ?? ''))->filter()->unique()->implode(', ') ?: '-',
                'bookingCode' => (string) ($first->booking_code ?? ''),
                'bookingPanel' => $first instanceof Booking && filled($first->booking_code) ? route('owner.bookings.panel', $first->id) : null,
                'bookingHref' => $first instanceof Booking ? route('owner.bookings', ['panel' => $first->id]) : null,
                'amount' => 'Rp '.number_format((float) $payment->jumlah, 0, ',', '.'),
                'method' => (string) ($payment->metode ?: '-'),
                'status' => array_combine(['label', 'tone'], StatusBadge::for('payment', (string) $payment->status)),
                'refund' => $refund > 0 ? 'Refund Rp '.number_format($refund, 0, ',', '.') : null,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function payoutRow(OwnerPayout $payout): array
    {
        $status = (string) $payout->status;
        $account = $payout->payoutAccount;

        return [
            'id' => (int) $payout->id,
            'requestedAt' => ($payout->requested_at ?? $payout->created_at)?->timezone('Asia/Jakarta')->translatedFormat('j M Y, H:i'),
            'amount' => 'Rp '.number_format((float) $payout->jumlah, 0, ',', '.'),
            'account' => trim(($payout->bank_nama ?? '-').' '.($account instanceof TenantPayoutAccount ? $account->masked_number : '••••'.substr((string) $payout->nomor_rekening, -4))),
            'label' => PayoutState::ownerLabel($status),
            'tone' => PayoutState::ownerTone($status),
            'note' => PayoutState::ownerNote($status),
            // Stages diajukan → disetujui → ditransfer (§8.12).
            'stages' => [
                ['label' => 'Diajukan', 'done' => true],
                ['label' => 'Disetujui', 'done' => $payout->approved_at !== null || $status === PayoutState::SUCCESS || $status === PayoutState::FAILED],
                ['label' => 'Ditransfer', 'done' => $status === PayoutState::SUCCESS],
            ],
            'rejectionReason' => $payout->rejection_reason,
            'reference' => $payout->transfer_reference,
            'proofUrl' => $payout->proof_path ? route('owner.payouts.proof', $payout->id) : null,
        ];
    }

    /**
     * @return array{min: int, cap: int|null}
     */
    private function payoutRules(Tenant $tenant): array
    {
        $cap = config('payouts.max_amount_per_request');

        return ['min' => (int) config('payouts.min_amount', 10000), 'cap' => $cap === null ? null : (int) $cap];
    }

    /**
     * @return array<string, mixed>
     */
    private function explanation(Tenant $tenant): array
    {
        return [
            'deadlineMinutes' => PaymentRules::deadlineMinutes(PaymentState::TIPE_BOOKING),
            'refundsEnabled' => (bool) $tenant->refunds_enabled,
            'cancelBeforeHours' => (int) $tenant->cancel_before_hours,
            'rescheduleBeforeHours' => (int) $tenant->reschedule_before_hours,
        ];
    }
}
