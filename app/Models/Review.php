<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'idbooking',
        'rating',
        'komentar',
        'balasan',
        'dibalas_pada',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_hidden' => 'boolean',
            'dibalas_pada' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'idbooking');
    }
}
