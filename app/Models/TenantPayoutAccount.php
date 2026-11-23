<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $changed_at
 */
class TenantPayoutAccount extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'idtenant',
        'bank_kode',
        'bank_nama',
        'nomor_rekening',
        'nama_pemilik_rekening',
        'is_default',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'changed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function getMaskedNumberAttribute(): string
    {
        $number = (string) $this->nomor_rekening;
        $last4 = strlen($number) > 4 ? substr($number, -4) : $number;

        return '••••'.$last4;
    }
}
