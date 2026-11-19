<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Booking\BookingTimeRange;
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
use App\Support\PaymentMethodLabel;
use App\Support\Rupiah;
use App\Support\StatusBadge;
use Carbon\Carbon;

final class GetOwnerBookingPanel
{
    public function __construct(private GetBookingTimeRanges $ranges) {}

    /** @return array<string, mixed> */
    public function execute(Tenant $tenant, int $id, TenantMember $member): array
    {
        $booking = Booking::where('idtenant', $tenant->id)->with(['layanan.units' => fn ($query) => $query->where('units.is_active', true)])->findOrFail($id);
        $payment = Payment::where('idtenant', $tenant->id)->find($booking->idpayment);
        $family = $payment
            ? $payment->bookings()->where('idtenant', $tenant->id)->with(['logs', 'schedule'])->orderBy('tanggalbooking')->orderBy('jam')->get()
            : collect([$booking->load(['logs', 'schedule'])]);
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
            'source' => $walkIn ? 'Dicatat kasir' : 'Online',
            'service' => $service instanceof Service ? $service->namalayanan : 'Layanan dihapus',
            'status' => self::badge('booking', $booking->status),
            'sessions' => $family->map(fn (Booking $row): array => [
                'date' => $timeRanges[$row->id]->start->locale('id')->translatedFormat('l, j M'),
                'time' => str_replace(':', '.', $timeRanges[$row->id]->label()),
            ])->values()->all(),
            'participants' => $booking->peserta,
            'unit' => $service instanceof Service ? $service->units->pluck('name')->implode(', ') : null,
            'customer' => [
                'name' => $booking->namapelanggan, 'phone' => $booking->nomorhp, 'email' => $booking->email,
                'whatsapp_url' => $phone === '' ? null : 'https://wa.me/'.$phone,
                'panel_url' => route('owner.customers.panel', CustomerIdentity::forBooking($booking)),
            ],
            'payment' => [
                'status' => self::badge('payment', $payment->status ?? 'Belum tercatat'),
                'method' => PaymentMethodLabel::for($payment?->metode), 'amount' => $amount,
                'breakdown' => isset($breakdown['base_subtotal']) ? [
                    ['label' => 'Subtotal sesi', 'amount' => self::rupiah((float) $breakdown['base_subtotal'])],
                ] : [],
                'paid_at' => $payment?->balance_credited_at ? Carbon::parse($payment->balance_credited_at)->format('d/m/Y H:i') : null,
                'deadline' => $payment?->status === PaymentState::STATUS_PENDING && $payment->expired_at ? Carbon::parse($payment->expired_at)->format('d/m/Y H:i') : null,
                'refund' => $refund ? ['status' => self::badge('refund', $refund->status), 'amount' => Rupiah::format($refund->jumlah)]
                    : ($request ? ['status' => self::badge('refund_request', $request->status->value), 'amount' => null] : null),
            ],
            'refund_reason' => TenantPermission::allowsRole($member->role, 'refunds.decide') ? $request?->reason : null,
            'note' => $booking->catatan,
            'walkin_method' => $walkIn ? $payment->metode : null,
            'history' => $this->history($family->flatMap(fn (Booking $row) => $row->logs)->filter(fn ($log) => $log instanceof BookingLog)->values()->all(), $tenant),
            'actions' => $this->actions($booking, $payment, $request, $member, $timeRanges[$booking->id], $family),
            'action_hint' => match (true) {
                $payment !== null && BookingRules::onlinePaymentHoldEnd($booking, $payment) !== null => 'Tindakan tersedia setelah pembayaran selesai.',
                $family->count() > 1 && $booking->status === BookingState::STATUS_PAID
                    && $timeRanges[$booking->id]->start->isFuture()
                    && TenantPermission::allowsRole($member->role, 'bookings.reschedule') => 'Booking beberapa slot tidak dapat dipindah jadwal.',
                default => null,
            },
        ];
    }

    /**
     * @param  iterable<Booking>  $family
     * @return list<array<string, mixed>>
     */
    private function actions(Booking $booking, ?Payment $payment, ?RefundRequest $request, TenantMember $member, BookingTimeRange $range, iterable $family): array
    {
        if ($booking->idpayment && $payment === null) {
            return [];
        }
        if ($payment && BookingRules::onlinePaymentHoldEnd($booking, $payment) !== null) {
            return filled($booking->email) && filled($payment->email_pembayar)
                ? [['label' => 'Kirim ulang link', 'url' => route('owner.bookings.resend-link', $booking->id)]]
                : [];
        }

        $allowed = fn (string $ability): bool => TenantPermission::allowsRole($member->role, $ability);
        $statusUrl = route('owner.bookings.status', $booking->id);
        $service = $booking->layanan;
        $actions = [];
        if ($allowed('bookings.reschedule') && $booking->status === BookingState::STATUS_PAID
            && $range->start->isFuture() && count(collect($family)) === 1 && $service instanceof Service) {
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
        if ($allowed('bookings.complete') && $booking->status === BookingState::STATUS_PAID && BookingRules::sessionHasStarted($range)
            && BookingState::canTransition($booking->status, BookingState::STATUS_COMPLETED)) {
            $actions[] = [
                'label' => 'Tandai selesai', 'url' => $statusUrl, 'method' => 'PATCH', 'fields' => ['status' => 'completed'],
                'confirm' => ['title' => 'Tandai booking selesai?', 'message' => 'Pastikan sesi pelanggan sudah selesai.'],
            ];
        }

        if ($allowed('bookings.no_show') && $booking->status === BookingState::STATUS_PAID
            && collect($family)->every(fn (Booking $row): bool => BookingState::canTransition($row->status, BookingState::STATUS_NO_SHOW))
            && BookingRules::sessionHasEnded(collect($family)->values()->all())) {
            $actions[] = [
                'label' => 'Tidak datang', 'url' => $statusUrl, 'method' => 'PATCH',
                'fields' => ['status' => BookingState::STATUS_NO_SHOW],
                'confirm' => ['title' => 'Tandai tidak datang?', 'message' => 'Pastikan pelanggan tidak datang pada sesi ini.'],
            ];
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
                $refundAmount = RefundRequestState::from($decision)->amount((float) $payment?->jumlah);
                $actions[] = [
                    'label' => $label, 'url' => route('owner.refunds.decide', $request->id), 'method' => 'PATCH',
                    'fields' => ['decision' => $decision],
                    'reason' => $decision === 'rejected' ? ['name' => 'reason', 'label' => 'Alasan penolakan'] : null,
                    'confirm' => ['title' => $label.'?', 'message' => $refundAmount === null
                        ? 'Booking tetap berlaku.'
                        : 'Booking dibatalkan. Admin akan memproses refund '.self::rupiah($refundAmount).'.'],
                ];
            }
        }

        if ($allowed('bookings.view') && filled($payment ? $payment->email_pembayar : $booking->email)) {
            $actions[] = ['label' => 'Kirim ulang link booking', 'url' => route('owner.bookings.resend-link', $booking->id)];
        }

        return array_map(function (array $action) use ($booking): array {
            if (isset($action['reason'])) {
                $action['form_key'] = 'booking-reason-'.$booking->id.'-'.(
                    $action['fields']['decision'] ?? $action['fields']['status'] ?? 'request-cancel'
                );
            }

            return $action;
        }, $actions);
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

            $description = $log->description ?? $log->event_label;
            if ($log->event === 'rescheduled' && ($metadata['role'] ?? null) === 'owner' && array_key_exists('reason', $metadata)) {
                $description = 'Jadwal diubah oleh '.$actor.'. Alasan: '.($metadata['reason'] ?? '—');
            }
            if (isset($metadata['price_difference'])) {
                $difference = (int) $metadata['price_difference'];
                $description .= ' Selisih harga: '.($difference < 0 ? '−' : ($difference > 0 ? '+' : '')).self::rupiah(abs($difference)).'.';
            }

            return ['at' => $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'), 'text' => $description, 'actor' => $actor];
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
        return Rupiah::format($amount);
    }
}
