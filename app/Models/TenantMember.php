<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\TenantMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMember extends Model
{
    /** @use HasFactory<TenantMemberFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = ['idtenant', 'iduser', 'role', 'invited_by', 'invited_at', 'accepted_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['invited_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iduser');
    }
}
