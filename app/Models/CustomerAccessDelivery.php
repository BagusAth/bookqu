<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CustomerAccessDelivery extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $hidden = ['recipient'];

    protected function casts(): array
    {
        return [
            'booking_ids' => 'array',
            'recipient' => 'encrypted',
            'family_version' => 'integer',
            'attempts' => 'integer',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
