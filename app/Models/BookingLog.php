<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'event',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    // ── Static factory for quick logging ──────────────────────────────────────

    public static function record(int $bookingId, string $event, ?string $description = null, array $metadata = []): self
    {
        return static::create([
            'booking_id'  => $bookingId,
            'event'       => $event,
            'description' => $description,
            'metadata'    => empty($metadata) ? null : $metadata,
        ]);
    }

    // ── Human-readable event labels ──────────────────────────────────────────

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created'         => 'Booking Dibuat',
            'payment_pending' => 'Menunggu Pembayaran',
            'payment_success' => 'Pembayaran Berhasil',
            'payment_failed'  => 'Pembayaran Gagal',
            'cancelled'       => 'Booking Dibatalkan',
            'rescheduled'     => 'Jadwal Diubah',
            'viewed'          => 'Booking Dilihat',
            default           => ucfirst($this->event),
        };
    }

    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'created'         => 'plus-circle',
            'payment_pending' => 'clock',
            'payment_success' => 'check-circle',
            'payment_failed'  => 'x-circle',
            'cancelled'       => 'ban',
            'rescheduled'     => 'calendar',
            'viewed'          => 'eye',
            default           => 'info',
        };
    }

    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created'         => 'indigo',
            'payment_pending' => 'amber',
            'payment_success' => 'emerald',
            'payment_failed'  => 'red',
            'cancelled'       => 'red',
            'rescheduled'     => 'blue',
            'viewed'          => 'gray',
            default           => 'gray',
        };
    }
}
