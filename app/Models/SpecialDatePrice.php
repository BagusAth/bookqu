<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The price of a service on one date, replacing its base price and the weekend rule
 * (FR-SCHEDULE-012).
 *
 * @property Carbon $tanggal
 */
class SpecialDatePrice extends Model
{
    use BelongsToTenant;

    protected $fillable = ['idtenant', 'service_id', 'tanggal', 'price'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'price' => 'decimal:2'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
