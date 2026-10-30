<?php

namespace App\Models;

use App\Domain\CustomerAccess\AccessProfile;
use App\Traits\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property AccessProfile $profile
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $revoked_at
 * @property array<int, int> $booking_ids
 */
class CustomerAccessGrant extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $hidden = ['secret_hash', 'recipient'];

    protected function casts(): array
    {
        return [
            'profile' => AccessProfile::class,
            'booking_ids' => 'array',
            'recipient' => 'encrypted',
            'family_version' => 'integer',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
