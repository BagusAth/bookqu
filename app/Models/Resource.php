<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Resource extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'name',
        'type',
        'capacity',
        'availability_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_resource', 'idresource', 'idservice')->withTimestamps();
    }
}
