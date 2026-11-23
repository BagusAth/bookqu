<?php

namespace App\View\Components;

use App\Domain\Subscription\EntitlementRules;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PlanFeatures extends Component
{
    /** @var list<string> */
    public array $features;

    public string $key;

    public function __construct(public ?Plan $plan = null, public string $planKey = '', public ?Plan $previous = null)
    {
        $this->key = strtolower(trim((string) ($plan->namapaket ?? $planKey)));
        $labels = $this->labels($plan, $this->key);
        $this->features = array_values($previous
            ? array_diff_assoc($labels, $this->labels($previous, strtolower(trim($previous->namapaket))))
            : $labels);
    }

    /** @return array<array-key, string> */
    private function labels(?Plan $plan, string $key): array
    {
        $bookings = ! $plan ? 'Booking bulanan sesuai paket' : (($plan->isunlimited || $plan->maxbooking <= 0)
            ? 'Booking tanpa batas' : 'Hingga '.number_format((int) $plan->maxbooking).' booking/bulan');
        $services = ! $plan ? 'Layanan sesuai paket' : ($plan->maxlayanan <= 0
            ? 'Layanan tanpa batas' : 'Hingga '.number_format((int) $plan->maxlayanan).' layanan');

        return array_map(
            fn (string $feature): string => strtr($feature, [':bookings' => $bookings, ':services' => $services]),
            EntitlementRules::advertisedFeatures($key),
        );
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.plan-features');
    }
}
