<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model): void {
            if (session()->has('current_tenant_id') && $model->getAttribute('idtenant') === null) {
                $model->setAttribute('idtenant', session('current_tenant_id'));
            }
        });
    }
}
