<?php

namespace App\Modules\Sanidade\Models;

use App\Modules\Rebanho\Models\Animal;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ManejoSanitario extends Model
{
    use BelongsToTenant;

    protected $table = 'manejos_sanitarios';

    protected $fillable = ['animal_id', 'medicamento_id', 'tipo', 'dosagem', 'unidade', 'data_aplicacao', 'proxima_aplicacao', 'chave_lancamento', 'hash_lancamento'];

    protected function casts(): array
    {
        return ['dosagem' => 'decimal:3', 'data_aplicacao' => 'date', 'proxima_aplicacao' => 'date'];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class)->withTrashed();
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->whereNotNull('proxima_aplicacao')
            ->whereHas('animal', fn (Builder $animal) => $animal->ativos()->whereNull('deleted_at'))
            ->whereNotExists(function (QueryBuilder $newer): void {
                $newer->selectRaw('1')->from('manejos_sanitarios as posterior')
                    ->whereColumn('posterior.tenant_id', 'manejos_sanitarios.tenant_id')
                    ->whereColumn('posterior.animal_id', 'manejos_sanitarios.animal_id')
                    ->whereColumn('posterior.medicamento_id', 'manejos_sanitarios.medicamento_id')
                    ->where(function (QueryBuilder $date): void {
                        $date->whereColumn('posterior.data_aplicacao', '>', 'manejos_sanitarios.data_aplicacao')
                            ->orWhere(function (QueryBuilder $sameDay): void {
                                $sameDay->whereColumn('posterior.data_aplicacao', 'manejos_sanitarios.data_aplicacao')
                                    ->whereColumn('posterior.id', '>', 'manejos_sanitarios.id');
                            });
                    });
            });
    }
}
