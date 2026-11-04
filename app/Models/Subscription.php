<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $trial_berakhir
 * @property Carbon|null $langganan_mulai
 * @property Carbon|null $langganan_berakhir
 */
class Subscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'source_payment_id',
        'idtenant',
        'idplan',
        'status',
        'trial_berakhir',
        'langganan_mulai',
        'langganan_berakhir',
    ];

    protected function casts(): array
    {
        return [
            'trial_berakhir' => 'datetime',
            'langganan_mulai' => 'datetime',
            'langganan_berakhir' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'idplan');
    }
}
