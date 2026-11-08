<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * One open interval of a weekday (ISO 1 = Monday … 7 = Sunday). Several rows on one day
 * describe breaks (FR-SCHEDULE-011).
 */
class OpeningHour extends Model
{
    use BelongsToTenant;

    protected $fillable = ['idtenant', 'day_of_week', 'opens_at', 'closes_at'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }
}
