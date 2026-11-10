<?php

namespace App\Modules\Agenda\Models;

use App\Modules\Auth\Models\User;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compromisso extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const APLICACAO = 'manejo_sanitario';

    public const PARTO = 'gestacao';

    public const FINANCEIRO = 'transacao';

    public const ORIGENS = [
        self::APLICACAO => 'Aplicação sanitária',
        self::PARTO => 'Parto previsto',
        self::FINANCEIRO => 'Vencimento financeiro',
    ];

    public const STATUS = ['PENDENTE' => 'Pendente', 'CONCLUIDO' => 'Concluído', 'CANCELADO' => 'Cancelado'];

    protected $fillable = ['origem_type', 'origem_id', 'titulo', 'data_prevista', 'status'];

    protected function casts(): array
    {
        return ['data_prevista' => 'date'];
    }

    public function origem(): MorphTo
    {
        return $this->morphTo();
    }

    public function urlOrigem(): string
    {
        return match ($this->origem_type) {
            self::APLICACAO => route('sanidade.index', ['aplicacao_id' => $this->origem_id]),
            self::PARTO => route('gestacoes.show', $this->origem_id),
            self::FINANCEIRO => route('transacoes.show', $this->origem_id),
        };
    }

    public function scopeVisiveis(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('origem_type', '!=', self::FINANCEIRO);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', 'PENDENTE');
    }

    public function atrasado(): bool
    {
        return $this->status === 'PENDENTE' && $this->data_prevista->lt(today());
    }
}
