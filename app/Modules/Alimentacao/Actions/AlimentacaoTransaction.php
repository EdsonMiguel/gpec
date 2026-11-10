<?php

namespace App\Modules\Alimentacao\Actions;

use App\Modules\Auth\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlimentacaoTransaction
{
    public function run(Closure $operation): mixed
    {
        return DB::transaction(function () use ($operation): mixed {
            Tenant::whereKey(Auth::user()?->tenant_id)->lockForUpdate()->firstOrFail();

            return $operation();
        });
    }
}
