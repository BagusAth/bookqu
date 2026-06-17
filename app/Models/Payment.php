<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'idplan',
        'idbooking',
        'tipe',
        'jumlah',
        'status',
        'metode',
        'external_id',
        'order_id',
        'snap_token',
        'expired_at',
        'nama_pembayar',
        'email_pembayar',
        'hp_pembayar',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'expired_at' => 'datetime',
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'idbooking');
    }

    /**
     * Cek apakah pembayaran sudah kedaluwarsa.
     */
    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    /**
     * Cek apakah pembayaran masih bisa diproses.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
