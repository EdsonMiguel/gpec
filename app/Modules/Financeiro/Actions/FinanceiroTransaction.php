<?php

namespace App\Modules\Financeiro\Actions;

use App\Modules\Auth\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FinanceiroTransaction
{
    public function run(Closure $operation): mixed
    {
        Gate::authorize('administrar');

        return DB::transaction(function () use ($operation): mixed {
            Tenant::whereKey(Auth::user()->tenant_id)->lockForUpdate()->firstOrFail();

            return $operation();
        });
    }
}
