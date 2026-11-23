<?php

namespace App\Models;

use App\Domain\Payment\RefundRequestState;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property RefundRequestState $status */
final class RefundRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'idtenant', 'idpayment', 'booking_id', 'reason', 'bank_nama', 'nomor_rekening',
        'nama_pemilik_rekening', 'status', 'decided_by', 'decision_reason', 'decided_at', 'last_reminded_on',
    ];

    protected $hidden = ['bank_nama', 'nomor_rekening', 'nama_pemilik_rekening'];

    protected function casts(): array
    {
        return [
            'bank_nama' => 'encrypted', 'nomor_rekening' => 'encrypted', 'nama_pemilik_rekening' => 'encrypted',
            'status' => RefundRequestState::class, 'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function maskedAccount(): string
    {
        return '•••• '.substr((string) $this->nomor_rekening, -4);
    }
}
