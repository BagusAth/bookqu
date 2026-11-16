<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WebhookEndpoint extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['idtenant', 'name', 'url', 'secret'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted'];
    }
}
