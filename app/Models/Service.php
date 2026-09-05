<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, BelongsToTenant;
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

    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'service_staff', 'idservice', 'idstaff')->withTimestamps();
    }

    public function resources(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'service_resource', 'idservice', 'idresource')->withTimestamps();
    }

    public function additionalItems(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(AdditionalItem::class, 'additional_item_service', 'idservice', 'idadditional_item')->withTimestamps();
    }

    /**
     * Checks if this service has active staff and resource fulfillment.
     * If staff are assigned to this service, at least one must be active.
     * If resources are assigned to this service, at least one must be active.
     */
    public function hasActiveFulfillment(): bool
    {
        $staffQuery = $this->staff()->withoutGlobalScopes();
        if ($staffQuery->exists() && !$this->staff()->withoutGlobalScopes()->where('staff.is_active', true)->exists()) {
            return false;
        }

        $resourceQuery = $this->resources()->withoutGlobalScopes();
        if ($resourceQuery->exists() && !$this->resources()->withoutGlobalScopes()->where('resources.is_active', true)->exists()) {
            return false;
        }

        return true;
    }
}
