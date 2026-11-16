<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OwnerApiKey extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['idtenant', 'name', 'prefix', 'digest', 'revoked_at'];

    protected $hidden = ['digest'];

    protected function casts(): array
    {
        return ['revoked_at' => 'immutable_datetime'];
    }
}
