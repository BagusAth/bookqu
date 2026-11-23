<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @property CarbonImmutable|null $google_expires_at */
final class OwnerIntegration extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'idtenant', 'google_access_token', 'google_refresh_token', 'google_expires_at',
        'google_analytics_id', 'meta_pixel_id',
    ];

    protected $hidden = ['google_access_token', 'google_refresh_token'];

    protected function casts(): array
    {
        return [
            'google_access_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'google_expires_at' => 'immutable_datetime',
        ];
    }
}
