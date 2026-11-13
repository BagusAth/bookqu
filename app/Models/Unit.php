<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A court, room or tool that several services can use and that is never double-booked
 * (FR-UNIT-001..004, ADR-0017 decision 4).
 *
 * @property int $id
 * @property int $idtenant
 * @property string $name
 * @property string $type
 * @property int $kapasitas
 * @property string|null $lokasi
 * @property string|null $image_path
 * @property bool $is_active
 */
class Unit extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['idtenant', 'name', 'type', 'kapasitas', 'lokasi', 'image_path', 'is_active'];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_unit', 'idunit', 'idservice');
    }
}
