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

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'service_resource', 'idservice', 'idresource')->withTimestamps();
    }

    public function additionalItems(): BelongsToMany
    {
        return $this->belongsToMany(AdditionalItem::class, 'additional_item_service', 'idservice', 'idadditional_item')->withTimestamps();
    }

    /**
     * Checks if this service has active resource fulfillment.
     * If resources are assigned to this service, at least one must be active.
     */
    public function hasActiveFulfillment(): bool
    {
        // scope-bypass: pivot rows of this service only; resource ids are tenant-validated on write
        $resourceQuery = $this->resources()->withoutGlobalScopes();
        if ($resourceQuery->exists() && ! $this->resources()->withoutGlobalScopes()->where('resources.is_active', true)->exists()) {
            return false;
        }

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
