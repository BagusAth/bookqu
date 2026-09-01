<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // P0-01: Use TenantContext instead of session('current_tenant_id')
        if (\App\Support\TenantContext::hasTenant()) {
            $builder->where('idtenant', \App\Support\TenantContext::getTenantId());
        }
    }
}
