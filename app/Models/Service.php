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
        'is_active',
        'is_popular',
        'image_url',
        'kapasitas',
        'satuan_harga',
        'satuan_durasi',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'kapasitas' => 'integer',
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
