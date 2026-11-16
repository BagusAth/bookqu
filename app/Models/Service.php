<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'idtenant',
        'namalayanan',
        'harga',
        'durasi',
        'deskripsi',
        'is_active',
        'is_popular',
        'image_url',
        'kapasitas',
        'satuan_harga',
        'satuan_durasi',
        'idcategory',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'kapasitas' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'idcategory');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'idlayanan');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'idlayanan');
    }

    /** Units this service uses (FR-UNIT-002). */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'service_unit', 'idservice', 'idunit');
    }

    public function additionalItems(): BelongsToMany
    {
        return $this->belongsToMany(AdditionalItem::class, 'additional_item_service', 'idservice', 'idadditional_item')->withTimestamps();
    }

    /**
     * Withdrawn staff resources no longer gate a service (BQ-157). An inactive unit only stops
     * counting as a conflict (ADR-0017), so it does not make the service unbookable.
     */
    public function hasActiveFulfillment(): bool
    {
        return true;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (empty($this->image_url)) {
            return null;
        }
        if (str_starts_with($this->image_url, 'http://') || str_starts_with($this->image_url, 'https://') || str_starts_with($this->image_url, '/')) {
            return $this->image_url;
        }

        return Storage::url($this->image_url);
    }
}
