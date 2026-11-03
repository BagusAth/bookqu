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
        'request_key',
        'payout_account_id',
        'bank_kode',
        'jumlah',
        'status',
        'requested_at',
        'approved_at',
        'approved_by',
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
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function payoutAccount(): BelongsTo
    {
        // scope-bypass: payout references its own snapshot account row regardless of current tenant context
        return $this->belongsTo(TenantPayoutAccount::class, 'payout_account_id')->withoutGlobalScopes()->withTrashed();
    }
}
