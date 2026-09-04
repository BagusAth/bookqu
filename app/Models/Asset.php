<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'idtenant',
        'title',
        'category',
        'file_path',
        'file_size',
        'dimensions',
        'mime_type',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return '';
        }
        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }
        return Storage::url($this->file_path);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }
}
