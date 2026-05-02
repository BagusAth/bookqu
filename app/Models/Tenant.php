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
        'nomorhp',
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
}
