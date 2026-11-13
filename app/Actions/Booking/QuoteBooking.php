<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingPricing;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Schedule\PriceRules;
use App\Domain\Subscription\EntitlementRules;
use App\Models\AdditionalItem;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

final class QuoteBooking
{
    public function __construct(private BookingPricingReservations $reservations) {}

    /**
     * @param  iterable<Schedule>  $schedules
     * @param  list<array{id: int, quantity: int}>  $selection
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, Service $service, string $date, iterable $schedules, int $participants = 1, ?string $code = null, array $selection = []): array
    {
        $voucher = null;
        $items = [];
        if (filled($code) || $selection !== []) {
            $subscription = $tenant->subscription()->with('plan')->first();
            if (filled($code)) {
                $this->requireFeature($subscription, 'promos', 'voucher_code');
                // scope-bypass: a checkout quote only looks up codes within the resolved tenant.
                $voucher = Voucher::withoutGlobalScopes()->where('idtenant', $tenant->id)
                    ->where('code', strtoupper(trim((string) $code)))->first();
                if ($voucher === null || ($voucher->usage_limit !== null
                    && $voucher->used_count + $this->reservations->held('voucher_id', $voucher->id) >= $voucher->usage_limit)) {
                    throw ValidationException::withMessages(['voucher_code' => 'Kode promo tidak tersedia.']);
                }
            }
            if ($selection !== []) {
                $this->requireFeature($subscription, 'add-ons', 'selected_addons');
                // scope-bypass: selected items must belong to the tenant and be linked to this service.
                $linked = AdditionalItem::withoutGlobalScopes()->where('additional_items.idtenant', $tenant->id)
                    ->where('is_active', true)->whereIn('id', array_column($selection, 'id'))
                    ->whereExists(fn ($link) => $link->selectRaw('1')->from('additional_item_service')
                        ->whereColumn('idadditional_item', 'additional_items.id')->where('idservice', $service->id))
                    ->get()->keyBy('id');

                $quantities = [];
                foreach ($selection as $line) {
                    $id = (int) $line['id'];
                    $quantities[$id] = ($quantities[$id] ?? 0) + (int) $line['quantity'];
                }
                ksort($quantities);
                foreach ($quantities as $id => $quantity) {
                    $item = $linked->get($id);
                    if ($item === null || $quantity < 1 || (! $item->is_unlimited
                        && $quantity + $this->reservations->held('additional_item_id', $id) > $item->stock)) {
                        throw ValidationException::withMessages(['selected_addons' => 'Add-on atau jumlah yang dipilih tidak tersedia.']);
                    }
                    $items[] = [
                        'id' => $id, 'name' => $item->name, 'quantity' => $quantity,
                        'price' => (float) $item->price, 'unlimited' => (bool) $item->is_unlimited,
                    ];
                }
            }
        }

        return BookingPricing::quote($service, $schedules, PriceRules::forTenant($tenant, $date, $date), $participants, $items, $voucher);
    }

    private function requireFeature(?Subscription $subscription, string $feature, string $field): void
    {
        if (ModuleRelease::state($feature) !== 'released' || ! EntitlementRules::canAccessFeature($subscription, $feature)['allowed']) {
            $messages = [$field => 'Fitur ini tidak tersedia untuk paket bisnis.'];
            if ($field === 'voucher_code') {
                $messages['voucher'] = $messages[$field];
            }
            throw ValidationException::withMessages($messages);
        }
    }
}
