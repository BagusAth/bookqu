<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'namalayanan',
        'harga',
        'durasi',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'idlayanan');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'idlayanan');
    }
}
