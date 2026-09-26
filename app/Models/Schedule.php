<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'idlayanan',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'harga_override',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga_override' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public const STATUS_AVAILABLE   = 'AVAILABLE';
    public const STATUS_BOOKED      = 'BOOKED';
    public const STATUS_BLOCKED     = 'BLOCKED';
    public const STATUS_UNAVAILABLE = 'UNAVAILABLE';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'idlayanan');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'idschedule');
    }

    /**
     * Active booking that holds this schedule slot (pending, paid, completed).
     */
    public function activeBooking(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Booking::class, 'idschedule')
            ->whereIn('status', ['pending', 'paid', 'completed']);
    }

    /**
     * Determine slot availability status: AVAILABLE, BOOKED, BLOCKED, or UNAVAILABLE.
     */
    public function getAvailabilityStatus(?\Carbon\Carbon $now = null): string
    {
        return \App\Domain\Schedule\AvailabilityRules::getSlotAvailabilityStatus($this, $now);
    }

    /**
     * Check if the schedule slot is currently available for booking.
     */
    public function isAvailable(?\Carbon\Carbon $now = null): bool
    {
        return \App\Domain\Schedule\AvailabilityRules::isSlotAvailable($this, $now);
    }
}
