<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdditionalItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'name',
        'description',
        'price',
        'stock',
        'is_unlimited',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_unlimited' => 'boolean',
            'is_active' => 'boolean',
            'stock' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'additional_item_service', 'idadditional_item', 'idservice')->withTimestamps();
    }
}
