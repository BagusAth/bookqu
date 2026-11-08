<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Extra amount for slots that start inside a time window on given weekdays (FR-SCHEDULE-012).
 * A rule without services applies to every service of the tenant.
 */
class PeakHourRule extends Model
{
    use BelongsToTenant;

    protected $fillable = ['idtenant', 'name', 'days', 'starts_at', 'ends_at', 'extra_amount'];

    protected function casts(): array
    {
        return ['days' => 'array', 'extra_amount' => 'decimal:2'];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'peak_hour_rule_service', 'peak_hour_rule_id', 'service_id');
    }
}
