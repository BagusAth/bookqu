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
            // P0-01: Use TenantContext
            if (app(\App\Support\TenantContext::class)->hasTenant() && $model->getAttribute('idtenant') === null) {
                $model->setAttribute('idtenant', app(\App\Support\TenantContext::class)->getTenantId());
            }
        });
    }
}
