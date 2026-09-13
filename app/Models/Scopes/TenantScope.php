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
        $tenantContext = app(\App\Support\TenantContext::class);

        // P0-03: TenantScope FAIL-OPEN BEHAVIOR FIX
        // If TenantContext is empty, we must NOT return all tenant data.
        if (!$tenantContext->hasTenant()) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($builder->getModel()->getTable() . '.idtenant', $tenantContext->getTenantId());
    }
}
