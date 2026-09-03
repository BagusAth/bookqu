<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'idlayanan',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'harga_override',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga_override' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'idlayanan');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'idschedule');
    }
}
