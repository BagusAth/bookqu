<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerPayout extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'jumlah',
        'status',
        'requested_at',
        'processed_at',
        'bank_nama',
        'nomor_rekening',
        'nama_pemilik_rekening',
        'rejection_reason',
        'transfer_reference',
        'proof_path',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }
}
