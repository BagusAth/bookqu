<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'idlayanan',
        'idschedule',
        'namapelanggan',
        'nomorhp',
        'email',
        'tanggalbooking',
        'jam',
        'status',
        'idpayment',
        'catatan',
        // Management tokens
        'booking_code',
        'cancellation_token',
        'reschedule_token',
        // Reschedule history
        'rescheduled_from_date',
        'rescheduled_from_time',
        'rescheduled_from_schedule',
    ];

    protected function casts(): array
    {
        return [
            'tanggalbooking'        => 'date',
            'rescheduled_from_date' => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'idlayanan');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'idschedule');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'idpayment');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookingLog::class, 'booking_id')->orderBy('created_at', 'asc');
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class, 'booking_id');
    }

    // ── Token Generation ──────────────────────────────────────────────────────

    /**
     * Generate a unique human-readable booking code: BKQ-20260618-ABCD1234
     */
    public static function generateBookingCode(): string
    {
        $date   = now()->format('Ymd');
        $suffix = strtoupper(Str::random(8));
        $code   = "BKQ-{$date}-{$suffix}";

        // Ensure uniqueness (retry if collision)
        while (static::where('booking_code', $code)->exists()) {
            $suffix = strtoupper(Str::random(8));
            $code   = "BKQ-{$date}-{$suffix}";
        }

        return $code;
    }

    /**
     * Generate a cryptographically secure 64-char token.
     */
    public static function generateSecureToken(): string
    {
        return Str::random(64);
    }

    /**
     * Assign booking_code + both management tokens.
     * Call this after payment is confirmed.
     */
    public function assignManagementTokens(): void
    {
        $this->update([
            'booking_code'       => static::generateBookingCode(),
            'cancellation_token' => static::generateSecureToken(),
            'reschedule_token'   => static::generateSecureToken(),
        ]);
    }

    // ── Business Logic ────────────────────────────────────────────────────────

    /**
     * Get the management URL for the customer.
     */
    public function getManageUrl(): string
    {
        return route('booking.manage', ['booking_code' => $this->booking_code])
            . '?token=' . $this->cancellation_token;
    }

    /**
     * Check if this booking can still be cancelled based on tenant policy.
     */
    public function canBeCancelled(): bool
    {
        if ($this->status !== 'paid') {
            return false;
        }

        $tenant = $this->tenant;
        if (!$tenant) {
            return false;
        }

        $cancelBeforeHours = $tenant->cancel_before_hours ?? 24;
        $bookingDateTime   = Carbon::parse($this->tanggalbooking->toDateString() . ' ' . $this->jam);

        return now()->addHours($cancelBeforeHours)->lessThan($bookingDateTime);
    }

    /**
     * Check if this booking can be rescheduled.
     */
    public function canBeRescheduled(): bool
    {
        if ($this->status !== 'paid') {
            return false;
        }

        $tenant = $this->tenant;
        if (!$tenant) {
            return false;
        }

        $rescheduleBeforeHours = $tenant->reschedule_before_hours ?? 24;
        $bookingDateTime       = Carbon::parse($this->tanggalbooking->toDateString() . ' ' . $this->jam);

        return now()->addHours($rescheduleBeforeHours)->lessThan($bookingDateTime);
    }

    /**
     * Hours remaining until the booking time.
     */
    public function hoursUntilBooking(): float
    {
        $bookingDateTime = Carbon::parse($this->tanggalbooking->toDateString() . ' ' . $this->jam);
        return max(0, now()->diffInHours($bookingDateTime, false));
    }

    /**
     * Formatted price label.
     */
    public function getPriceLabelAttribute(): string
    {
        return 'Rp ' . number_format((float) ($this->payment?->jumlah ?? 0), 0, ',', '.');
    }
}
