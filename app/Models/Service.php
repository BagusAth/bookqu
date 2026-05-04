<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
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

    protected $casts = [
        'harga' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'kapasitas' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }
}
