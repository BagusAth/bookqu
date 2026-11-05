<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'cause',
        'refund_request_id',
        'booking_id',
        'payment_id',
        'jumlah',
        'status',
        'catatan',
        'refund_reference',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /** @return BelongsTo<RefundRequest, $this> */
    public function refundRequest(): BelongsTo
    {
        // scope-bypass: admin reads the request of this refund by its stored foreign key.
        return $this->belongsTo(RefundRequest::class)->withoutGlobalScope(TenantScope::class);
    }

    public function getJumlahFormattedAttribute(): string
    {
        return 'Rp '.number_format((float) $this->jumlah, 0, ',', '.');
    }
}
