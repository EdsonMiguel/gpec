<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Auth::user()?->tenant_id;
        if ($tenantId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }
        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}
