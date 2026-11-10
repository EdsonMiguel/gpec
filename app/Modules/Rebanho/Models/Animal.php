<?php

namespace App\Modules\Rebanho\Models;

use App\Modules\Alimentacao\Models\Refeicao;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Reproducao\Models\Gestacao;
use App\Modules\Sanidade\Models\CasoClinico;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const CATEGORIAS = ['BEZERRO', 'BEZERRA', 'GARROTE', 'NOVILHO', 'NOVILHA', 'BOI', 'VACA', 'TOURO'];

    public const STATUS = ['ATIVO', 'VENDIDO', 'MORTO', 'DESCARTADO', 'TRANSFERIDO'];

    protected $table = 'animais';

    protected $fillable = ['brinco', 'nome_ou_numero', 'categoria', 'raca', 'sexo', 'data_nascimento', 'status', 'lote_id', 'pai_id', 'mae_id'];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date', 'pai_id' => 'integer', 'mae_id' => 'integer', 'gestacao_nascimento_id' => 'integer'];
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pai_id')->withTrashed();
    }

    public function mae(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mae_id')->withTrashed();
    }

    public function gestacaoNascimento(): BelongsTo
    {
        return $this->belongsTo(Gestacao::class, 'gestacao_nascimento_id');
    }

    public function pesagens(): HasMany
    {
        return $this->hasMany(Pesagem::class);
    }

    public function manejos(): HasMany
    {
        return $this->hasMany(ManejoSanitario::class);
    }

    public function casosClinicos(): HasMany
    {
        return $this->hasMany(CasoClinico::class);
    }

    public function refeicoes(): HasMany
    {
        return $this->hasMany(Refeicao::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', 'ATIVO');
    }
}
