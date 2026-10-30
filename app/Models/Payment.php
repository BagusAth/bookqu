<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $hidden = ['manage_token'];

    protected $fillable = [
        'idtenant',
        'idplan',
        'idbooking',
        'tipe',
        'jumlah',
        'status',
        'metode',
        'provider',
        'external_id',
        'order_id',
        'manage_token',
        'snap_token',
        'payment_url',
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
            'customer_access_version' => 'integer',
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
     *
     * @deprecated Use bookings() for booking payments.
     */
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
        return $this->status === 'pending' && ! $this->isExpired();
    }

    /**
     * Override route model binding to bypass TenantScope.
     * Binding runs before TenantMiddleware populates the TenantContext,
     * so we must query globally here. Controllers will verify idtenant.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // scope-bypass: route binding resolves a public reference; customer middleware and controllers verify grant and tenant ownership.
        return static::withoutGlobalScope(TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
