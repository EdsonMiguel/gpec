<?php

namespace App\Modules\Sanidade\Models;

use App\Modules\Rebanho\Models\Animal;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CasoClinico extends Model
{
    use BelongsToTenant;

    public const STATUS = ['ABERTO' => 'Aberto', 'EM_ACOMPANHAMENTO' => 'Em acompanhamento', 'ENCERRADO' => 'Encerrado'];

    public const DESFECHOS = [
        'RECUPERADO' => 'Recuperação',
        'DIAGNOSTICO_DESCARTADO' => 'Diagnóstico descartado',
        'TRANSFERIDO' => 'Acompanhamento transferido',
        'OBITO' => 'Óbito',
        'OUTRO' => 'Outro',
    ];

    protected $table = 'casos_clinicos';

    protected $attributes = ['status' => 'ABERTO'];

    protected $fillable = [
        'animal_id', 'titulo', 'data_inicio', 'sintomas_iniciais', 'status',
        'data_encerramento', 'desfecho', 'observacao_encerramento',
    ];

    protected function casts(): array
    {
        return ['data_inicio' => 'date', 'data_encerramento' => 'date'];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class)->withTrashed();
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(AvaliacaoClinica::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->whereIn('status', ['ABERTO', 'EM_ACOMPANHAMENTO']);
    }

    public function estaAtivo(): bool
    {
        return $this->status !== 'ENCERRADO';
    }
}
