<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Customer\CustomerIdentity;
use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\RefundRequestState;
use App\Domain\Team\TenantPermission;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundRequest;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\StatusBadge;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

final class GetOwnerBookingPanel
{
    public function __construct(private GetBookingTimeRanges $ranges) {}

    /** @return array<string, mixed> */
    public function execute(Tenant $tenant, int $id, TenantMember $member): array
    {
        $booking = Booking::where('idtenant', $tenant->id)->with('layanan')->findOrFail($id);
        $payment = Payment::where('idtenant', $tenant->id)->find($booking->idpayment);
        $family = $payment
            ? $payment->bookings()->where('idtenant', $tenant->id)->with('logs')->orderBy('tanggalbooking')->orderBy('jam')->get()
            : collect([$booking->load('logs')]);
        $timeRanges = $this->ranges->execute($family)['sessionRanges'];
        $request = $payment ? RefundRequest::where('idtenant', $tenant->id)->where('idpayment', $payment->id)->latest('id')->first() : null;
        $refund = $payment ? Refund::where('payment_id', $payment->id)->first() : null;
        $walkIn = $payment && str_starts_with((string) $payment->order_id, 'WLK-');
        $phone = preg_replace('/\D/', '', (string) $booking->nomorhp) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }
        $service = $booking->layanan;
        $breakdown = $payment?->price_breakdown;
        $amount = $payment?->status === PaymentState::STATUS_SUKSES ? self::rupiah((float) $payment->jumlah) : '—';

        return [
            'code' => $booking->booking_code ?? 'BKQ-'.$booking->id,
            'source' => $walkIn ? 'Walk-in' : 'Online',
            'service' => $service instanceof Service ? $service->namalayanan : 'Layanan dihapus',
            'status' => self::badge('booking', $booking->status),
            'sessions' => $family->map(fn (Booking $row): array => [
                'date' => Carbon::parse($row->tanggalbooking)->format('d/m/Y'),
                'time' => $timeRanges[$row->id]->label(),
            ])->values()->all(),
            'participants' => $breakdown['lines'][0]['participants'] ?? null,
            'unit' => null,
            'customer' => [
                'name' => $booking->namapelanggan, 'phone' => $booking->nomorhp, 'email' => $booking->email,
                'whatsapp_url' => $phone === '' ? null : 'https://wa.me/'.$phone,
                'panel_url' => route('owner.customers.panel', CustomerIdentity::forBooking($booking)),
            ],
            'payment' => [
                'status' => self::badge('payment', $payment->status ?? 'Belum tercatat'),
                'method' => $payment?->metode, 'amount' => $amount,
                'breakdown' => isset($breakdown['base_subtotal']) ? [
                    ['label' => 'Subtotal sesi', 'amount' => self::rupiah((float) $breakdown['base_subtotal'])],
                ] : [],
                'paid_at' => $payment?->balance_credited_at ? Carbon::parse($payment->balance_credited_at)->format('d/m/Y H:i') : null,
                'deadline' => $payment?->status === PaymentState::STATUS_PENDING && $payment->expired_at ? Carbon::parse($payment->expired_at)->format('d/m/Y H:i') : null,
                'refund' => $refund ? ['status' => self::badge('refund', $refund->status), 'amount' => $refund->jumlah_formatted]
                    : ($request ? ['status' => self::badge('refund_request', $request->status->value), 'amount' => null] : null),
            ],
            'refund_reason' => TenantPermission::allowsRole($member->role, 'refunds.decide') ? $request?->reason : null,
            'note' => $booking->catatan,
            'walkin_method' => $walkIn ? $payment->metode : null,
            'history' => $this->history($family->flatMap(fn (Booking $row) => $row->logs)->filter(fn ($log) => $log instanceof BookingLog)->values()->all(), $tenant),
            'actions' => $this->actions($booking, $payment, $request, $member, $timeRanges[$booking->id]->start, $family),
        ];
    }

    /**
     * @param  iterable<Booking>  $family
     * @return list<array<string, mixed>>
     */
    private function actions(Booking $booking, ?Payment $payment, ?RefundRequest $request, TenantMember $member, CarbonImmutable $start, iterable $family): array
    {
        if ($booking->idpayment && $payment === null || $payment && BookingRules::onlinePaymentHoldEnd($booking, $payment) !== null) {
            return [];
        }

        $allowed = fn (string $ability): bool => TenantPermission::allowsRole($member->role, $ability);
        $statusUrl = route('owner.bookings.status', $booking->id);
        $service = $booking->layanan;
        $actions = [];
        if ($allowed('bookings.reschedule') && $booking->status === BookingState::STATUS_PAID
            && $start->isFuture() && count(collect($family)) === 1 && $service instanceof Service) {
            $actions[] = [
                'label' => 'Pindah jadwal',
                'reschedule' => [
                    'id' => $booking->id, 'booking_code' => $booking->booking_code,
                    'namapelanggan' => $booking->namapelanggan, 'idschedule' => $booking->idschedule,
                    'tanggalbooking' => Carbon::parse($booking->tanggalbooking)->toDateString(), 'jam' => $booking->jam,
                    'layanan' => ['namalayanan' => $service->namalayanan],
                ],
            ];
        }
        if ($allowed('bookings.complete') && $booking->status === BookingState::STATUS_PAID && $start->isPast()
            && BookingState::canTransition($booking->status, BookingState::STATUS_COMPLETED)) {
            $actions[] = ['label' => 'Tandai selesai', 'url' => $statusUrl, 'method' => 'PATCH', 'fields' => ['status' => 'completed']];
        }

        if (collect($family)->every(fn (Booking $row): bool => BookingState::canTransition($row->status, BookingState::STATUS_CANCELLED))
            && ($booking->status === BookingState::STATUS_PAID || ($payment && PaymentRules::providerOf($payment) === 'manual'))) {
            $reason = ['name' => 'reason', 'label' => 'Alasan pembatalan'];
            if ($allowed('bookings.cancel')) {
                $fullRefund = $payment?->status === PaymentState::STATUS_SUKSES && PaymentRules::providerOf($payment) !== 'manual' && (float) $payment->jumlah > 0;
                $actions[] = [
                    'label' => 'Batalkan booking', 'url' => $statusUrl, 'method' => 'PATCH', 'tone' => 'danger',
                    'fields' => ['status' => 'cancelled'], 'reason' => $reason,
                    'confirm' => ['title' => 'Batalkan booking?', 'message' => $fullRefund
                        ? 'Pelanggan mendapat refund penuh '.self::rupiah((float) $payment->jumlah).'.'
                        : 'Booking akan dibatalkan.'],
                ];
            } elseif ($allowed('bookings.request-cancel')) {
                $actions[] = ['label' => 'Ajukan pembatalan', 'url' => route('owner.bookings.cancellation-requests.store', $booking->id), 'reason' => $reason];
            }
        }

        if ($request?->status === RefundRequestState::Open && $allowed('refunds.decide')) {
            foreach (['accepted_full' => 'Terima 100%', 'accepted_half' => 'Terima 50%', 'rejected' => 'Tolak refund'] as $decision => $label) {
                $actions[] = [
                    'label' => $label, 'url' => route('owner.refunds.decide', $request->id), 'method' => 'PATCH',
                    'fields' => ['decision' => $decision],
                    'reason' => $decision === 'rejected' ? ['name' => 'reason', 'label' => 'Alasan penolakan'] : null,
                    'confirm' => ['title' => $label.'?', 'message' => $decision === 'rejected' ? 'Booking tetap berlaku.' : 'Booking dibatalkan dan refund diproses oleh admin.'],
                ];
            }
        }

        return $actions;
    }

    /** @param list<BookingLog> $logs
     * @return list<array{at: ?string, text: string, actor: string}>
     */
    private function history(array $logs, Tenant $tenant): array
    {
        $actorIds = collect($logs)->pluck('metadata.actor_iduser')->filter()->unique();
        $names = User::whereIn('id', $actorIds)->where(function ($query) use ($tenant): void {
            $query->where('id', $tenant->iduser)->orWhereIn('id', TenantMember::where('idtenant', $tenant->id)->select('iduser'));
        })->pluck('namalengkap', 'id');

        return collect($logs)->sortBy('created_at')->map(function (BookingLog $log) use ($names): array {
            $metadata = $log->getAttribute('metadata');
            $role = $metadata['actor_role'] ?? null;
            $actor = match ($role) {
                'system' => 'Sistem', 'customer' => 'Pelanggan', null => 'Tidak tercatat',
                default => ($names[$metadata['actor_iduser'] ?? 0] ?? 'Anggota tim').' ('.ucfirst((string) $role).')',
            };

            return ['at' => $log->created_at ? Carbon::parse($log->created_at)->format('d/m/Y H:i') : null, 'text' => $log->description ?? $log->event_label, 'actor' => $actor];
        })->values()->all();
    }

    /** @return array{label: string, tone: string} */
    private static function badge(string $type, string $status): array
    {
        [$label, $tone] = StatusBadge::for($type, $status);

        return compact('label', 'tone');
    }

    private static function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
