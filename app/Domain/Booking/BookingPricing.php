<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Domain\Schedule\PriceRules;
use App\Domain\Schedule\SlotPricing;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * The single booking pricing rule (ADR-0019 decision 1, FR-BOOKING-023). The checkout page,
 * the checkout fingerprint, the storefront placement and walk-ins all quote through here, and
 * placement stores the result on the payment as the frozen breakdown.
 *
 * Order: slot price (SlotPricing) × participants per slot, then add-ons (BQ-150), then the
 * promo discount (BQ-149).
 */
final class BookingPricing
{
    public const BREAKDOWN_VERSION = 1;

    /**
     * @param  iterable<object|array<string, mixed>>  $schedules  slots with id, tanggal, jam_mulai, harga_override
     * @param  list<array{id: int, name: string, quantity: int, price: float, unlimited: bool}>  $items
     * @return array{version: int, lines: list<array{schedule_id: int, date: string, start: string, unit_price: int, participants: int, amount: int, source: string, peak: list<array{name: string, extra: float}>}>, base_subtotal: int, add_ons: list<array<string, mixed>>, promo: array{id: int, code: string, discount: int}|null, total: int}
     */
    public static function quote(Service $service, iterable $schedules, PriceRules $rules, int $participants = 1, array $items = [], ?Voucher $voucher = null): array
    {
        $participants = max(1, $participants);
        $lines = [];

        foreach ($schedules as $schedule) {
            $slot = is_array($schedule) ? (object) $schedule : $schedule;
            $date = $slot->tanggal instanceof Carbon ? $slot->tanggal->toDateString() : Carbon::parse((string) $slot->tanggal)->toDateString();
            $start = PriceRules::time((string) $slot->jam_mulai);
            $price = SlotPricing::price($service, $date, $start, $slot->harga_override ?? null, $rules);

            $lines[] = [
                'schedule_id' => (int) $slot->id,
                'date' => $date,
                'start' => $start,
                'unit_price' => $price['price'],
                'participants' => $participants,
                'amount' => $price['price'] * $participants,
                'source' => $price['source'],
                'peak' => $price['peak'],
            ];
        }

        $baseSubtotal = array_sum(array_column($lines, 'amount'));

        $addOns = [];
        foreach ($items as $item) {
            $addOns[] = [
                'id' => $item['id'], 'name' => $item['name'], 'quantity' => $item['quantity'],
                'unit_price' => $item['price'], 'amount' => (int) round($item['price'] * $item['quantity']),
                'unlimited' => $item['unlimited'],
            ];
        }
        $subtotal = $baseSubtotal + array_sum(array_column($addOns, 'amount'));
        $promo = null;
        if ($voucher !== null) {
            if (! $voucher->isValid($subtotal, (int) $service->id)) {
                throw ValidationException::withMessages(['voucher_code' => 'Kode promo tidak berlaku untuk pesanan ini.']);
            }
            $promo = ['id' => (int) $voucher->id, 'code' => $voucher->code, 'discount' => (int) round($voucher->discountOn($baseSubtotal))];
        }

        return [
            'version' => self::BREAKDOWN_VERSION,
            'lines' => $lines,
            'base_subtotal' => $baseSubtotal,
            'add_ons' => $addOns,
            'promo' => $promo,
            'total' => max(0, $subtotal - ($promo['discount'] ?? 0)),
        ];
    }

    /**
     * The frozen amount of one slot of a booking group, or null when the payment predates the
     * stored breakdown.
     */
    public static function frozenSlotAmount(?Payment $payment, int $scheduleId): ?int
    {
        $breakdown = $payment?->price_breakdown;
        if (! is_array($breakdown) || ! isset($breakdown['lines']) || ! is_array($breakdown['lines'])) {
            return null;
        }

        foreach ($breakdown['lines'] as $line) {
            if ((int) ($line['schedule_id'] ?? 0) === $scheduleId) {
                return (int) $line['amount'];
            }
        }

        return null;
    }
}
