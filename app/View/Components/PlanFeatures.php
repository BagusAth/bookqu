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

    public function __construct(public ?Plan $plan = null, public string $planKey = '')
    {
        $this->key = strtolower(trim((string) ($plan->namapaket ?? $planKey)));
        $bookings = ! $plan ? 'Booking bulanan sesuai paket' : (($plan->isunlimited || $plan->maxbooking <= 0)
            ? 'Unlimited booking' : 'Hingga '.number_format((int) $plan->maxbooking).' booking/bulan');
        $services = ! $plan ? 'Layanan sesuai paket' : ($plan->maxlayanan <= 0
            ? 'Unlimited layanan' : 'Hingga '.number_format((int) $plan->maxlayanan).' layanan');
        $labels = EntitlementRules::advertisedFeatures($this->key);
        $this->features = array_values(array_map(fn (string $feature): string => strtr($feature, [':bookings' => $bookings, ':services' => $services]), $labels));
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.plan-features');
    }
}
