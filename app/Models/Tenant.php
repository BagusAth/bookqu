<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'iduser',
        'namabisnis',
        'slug',
        'jenisbisnis',
        'alamat',
        'nomorhp',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'idtenant');
    }
}
