<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'namapaket',
        'hargabulanan',
        'maxlayanan',
        'maxbooking',
        'isunlimited',
    ];

    protected function casts(): array
    {
        return [
            'hargabulanan' => 'decimal:2',
            'isunlimited' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'idplan');
    }
}
