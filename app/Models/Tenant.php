<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;
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
        'theme_color',
        'button_style',
        'font_family',
        'card_style',
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

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'idtenant')->latestOfMany();
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(OwnerBlockedDate::class, 'idtenant');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(OwnerPayout::class, 'idtenant');
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }
        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://') || str_starts_with($this->logo_path, '/')) {
            return $this->logo_path;
        }
        return \Illuminate\Support\Facades\Storage::url($this->logo_path);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (empty($this->banner_path)) {
            return null;
        }
        if (str_starts_with($this->banner_path, 'http://') || str_starts_with($this->banner_path, 'https://') || str_starts_with($this->banner_path, '/')) {
            return $this->banner_path;
        }
        return \Illuminate\Support\Facades\Storage::url($this->banner_path);
    }
}
