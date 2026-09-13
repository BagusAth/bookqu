<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Staff extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = [
        'idtenant',
        'name',
        'email',
        'phone',
        'role',
        'availability_schedule',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_staff', 'idstaff', 'idservice')->withTimestamps();
    }
}
