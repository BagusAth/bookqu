<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'idtenant',
        'idlayanan',
        'idschedule',
        'namapelanggan',
        'nomorhp',
        'email',
        'tanggalbooking',
        'jam',
        'status',
        'idpayment',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggalbooking' => 'date',
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

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'idschedule');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'idpayment');
    }
}
