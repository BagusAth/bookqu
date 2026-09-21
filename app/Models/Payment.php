<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, BelongsToTenant;

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
        'manage_token',
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

    /**
     * Relasi utama 1 Payment -> Banyak Booking (Multi-Slot Support).
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'idpayment');
    }

    /**
     * Backward-compatibility relasi single booking.
     * @deprecated Use bookings() for booking payments.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'idbooking');
    }

    /**
     * Dapatkan URL manajemen reservasi customer level payment.
     */
    public function getManageUrl(): string
    {
        if (!$this->order_id) {
            return '#';
        }

        return route('booking.manage.payment', ['order_id' => $this->order_id])
            . ($this->manage_token ? '?token=' . $this->manage_token : '');
    }

    /**
     * Dapatkan URL invoice reservasi customer level payment.
     */
    public function getInvoiceUrl(): string
    {
        if (!$this->order_id) {
            return '#';
        }

        return route('booking.manage.payment.invoice', ['order_id' => $this->order_id])
            . ($this->manage_token ? '?token=' . $this->manage_token : '');
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

    /**
     * Override route model binding to bypass TenantScope.
     * Binding runs before TenantMiddleware populates the TenantContext,
     * so we must query globally here. Controllers will verify idtenant.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
