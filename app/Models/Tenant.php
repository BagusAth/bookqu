<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'iduser',
        'namabisnis',
        'slug',
        'jenisbisnis',
        'alamat',
        'deskripsi',
        'logo_path',
        'nomorhp',
        'payment_mode',
        'midtrans_status',
        'midtrans_environment',
        'midtrans_sandbox_merchant_id',
        'midtrans_sandbox_client_key',
        'midtrans_sandbox_server_key',
        'midtrans_prod_merchant_id',
        'midtrans_prod_client_key',
        'midtrans_prod_server_key',
        'saldo_platform',
        'weekend_price_type',
        'weekend_price_value',
        'cancel_before_hours',
        'reschedule_before_hours',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iduser');
    }
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'idtenant');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'idtenant');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'idtenant');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'idtenant');
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(OwnerBlockedDate::class, 'idtenant');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(OwnerPayout::class, 'idtenant');
    }
}
