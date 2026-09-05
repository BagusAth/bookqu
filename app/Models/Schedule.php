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
     * Single source of truth to determine slot availability status:
     * AVAILABLE, BOOKED, BLOCKED, or UNAVAILABLE.
     */
    public function getAvailabilityStatus(?\Carbon\Carbon $now = null): string
    {
        $now = $now ?? \Carbon\Carbon::now();

        // 1. Slot is explicitly blocked
        if ($this->status === 'diblokir') {
            return self::STATUS_BLOCKED;
        }

        // 2. Slot is not marked 'tersedia'
        if ($this->status !== 'tersedia') {
            return self::STATUS_UNAVAILABLE;
        }

        $slotDate = $this->tanggal instanceof \Carbon\Carbon
            ? $this->tanggal->toDateString()
            : \Carbon\Carbon::parse($this->tanggal)->toDateString();

        // 3. Date is blocked by owner
        $isBlocked = OwnerBlockedDate::where('idtenant', $this->idtenant)
            ->whereDate('tanggal', $slotDate)
            ->exists();
        if ($isBlocked) {
            return self::STATUS_BLOCKED;
        }

        // 4. Date/time has already passed
        $slotDateTime = \Carbon\Carbon::parse($slotDate . ' ' . $this->jam_mulai);
        if ($slotDateTime->lessThanOrEqualTo($now)) {
            return self::STATUS_UNAVAILABLE;
        }

        // 5. Service is inactive or has inactive staff/resource fulfillment
        $service = $this->relationLoaded('layanan') && $this->layanan
            ? $this->layanan
            : Service::withoutGlobalScopes()->find($this->idlayanan);

        if ($service && (!$service->is_active || !$service->hasActiveFulfillment())) {
            return self::STATUS_UNAVAILABLE;
        }

        // 6. Slot is already booked by an active booking (pending, paid, completed)
        $hasActiveBooking = $this->relationLoaded('bookings')
            ? $this->bookings->whereIn('status', ['pending', 'paid', 'completed'])->isNotEmpty()
            : Booking::withoutGlobalScopes()
                ->where('idschedule', $this->id)
                ->whereIn('status', ['pending', 'paid', 'completed'])
                ->exists();

        if ($hasActiveBooking) {
            return self::STATUS_BOOKED;
        }

        return self::STATUS_AVAILABLE;
    }

    /**
     * Check if the schedule slot is currently available for booking.
     */
    public function isAvailable(?\Carbon\Carbon $now = null): bool
    {
        return $this->getAvailabilityStatus($now) === self::STATUS_AVAILABLE;
    }
}
