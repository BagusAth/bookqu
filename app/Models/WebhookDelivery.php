<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WebhookDelivery extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['idtenant', 'endpoint_id', 'event_id', 'event_name', 'attempts', 'http_status', 'elapsed_ms', 'status'];
}
