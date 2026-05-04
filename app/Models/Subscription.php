<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'idplan');
    }
}
