<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Category extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'idtenant',
        'name',
        'slug',
        'description',
        'color',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'idcategory');
    }

    /** Public URL of the category image (FR-CATEGORY-006), or null when none is stored. */
    public function getImageSrcAttribute(): ?string
    {
        if (empty($this->image_url)) {
            return null;
        }

        return str_starts_with($this->image_url, 'http') || str_starts_with($this->image_url, '/')
            ? $this->image_url
            : Storage::disk('public')->url($this->image_url);
    }
}
