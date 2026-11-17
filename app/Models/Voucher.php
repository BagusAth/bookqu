<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'idtenant',
        'code',
        'discount_type',
        'discount_value',
        'min_spending',
        'max_discount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'applicable_services',
        'is_active',
        'valid_days',
        'valid_from_time',
        'valid_until_time',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_spending' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function isValid(float $amount = 0, ?int $serviceId = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = Carbon::today();
        if ($this->start_date && $today->lessThan($this->start_date)) {
            return false;
        }
        if ($this->end_date && $today->greaterThan($this->end_date)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($this->min_spending > 0 && $amount < $this->min_spending) {
            return false;
        }

        if ($serviceId !== null && ! empty($this->applicable_services) && $this->applicable_services !== 'all') {
            $allowedServices = array_map('intval', explode(',', $this->applicable_services));
            if (! in_array($serviceId, $allowedServices)) {
                return false;
            }
        }

        return true;
    }

    /** Whether the promo has a day or time limit (FR-VOUCHER-006). */
    public function hasQuietHours(): bool
    {
        return ! empty($this->valid_days) || $this->valid_from_time !== null || $this->valid_until_time !== null;
    }

    /**
     * Whether a slot starting at `$start` on `$date` falls inside the promo's days and hours
     * (FR-VOUCHER-006). A promo without limits covers every slot.
     */
    public function coversSlot(string $date, string $start): bool
    {
        if (! empty($this->valid_days)) {
            $days = array_map('intval', explode(',', (string) $this->valid_days));
            if (! in_array(Carbon::parse($date)->dayOfWeekIso, $days, true)) {
                return false;
            }
        }

        $time = substr($start, 0, 5);
        if ($this->valid_from_time !== null && $time < substr((string) $this->valid_from_time, 0, 5)) {
            return false;
        }

        return $this->valid_until_time === null || $time < substr((string) $this->valid_until_time, 0, 5);
    }

    public function calculateDiscount(float $amount): float
    {
        if (! $this->isValid($amount)) {
            return 0;
        }

        return $this->discountOn($amount);
    }

    public function discountOn(float $amount): float
    {
        $discount = $this->discount_type === 'percentage'
            ? $amount * (float) $this->discount_value / 100
            : (float) $this->discount_value;

        return max(0, min($discount, $amount, $this->max_discount ?? $amount));
    }
}
