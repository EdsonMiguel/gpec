<?php

namespace App\Support\Tenancy;

use App\Modules\Auth\Models\Tenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);
        static::saving(function (Model $model): void {
            $tenantId = Auth::user()?->tenant_id;
            if ($tenantId === null
                || ($model->exists && (int) $model->getRawOriginal('tenant_id') !== (int) $tenantId)
                || ($model->tenant_id !== null && (int) $model->tenant_id !== (int) $tenantId)) {
                throw new AuthorizationException('Operação fora da fazenda autenticada.');
            }
            $model->tenant_id = $tenantId;
        });
        static::deleting(function (Model $model): void {
            if (Auth::user()?->tenant_id === null
                || (int) $model->getRawOriginal('tenant_id') !== (int) Auth::user()->tenant_id) {
                throw new AuthorizationException('Operação fora da fazenda autenticada.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
