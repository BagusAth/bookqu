<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'iduser', 'id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'idtenant', 'id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'idtenant', 'id');
    }
}
