<?php

namespace App\Modules\Reproducao\Models;

use App\Modules\Rebanho\Models\Animal;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gestacao extends Model
{
    use BelongsToTenant;

    public const TIPOS = ['MONTA' => 'Monta natural', 'INSEMINACAO' => 'Inseminação'];

    public const STATUS = [
        'AGUARDANDO_EXAME' => 'Aguardando confirmação',
        'CONFIRMADA' => 'Gestação confirmada',
        'SEM_PRENHEZ' => 'Sem prenhez',
        'ENCERRADA' => 'Encerrada sem confirmação',
        'PERDA' => 'Perda gestacional',
        'PARTO' => 'Parto registrado',
    ];

    public const ABERTOS = ['AGUARDANDO_EXAME', 'CONFIRMADA'];

    protected $table = 'gestacoes';

    protected $fillable = [
        'matriz_id', 'reprodutor_id', 'tipo', 'data_cobertura', 'referencia_semen', 'responsavel', 'observacao',
        'status', 'data_confirmacao', 'data_prevista_parto', 'data_encerramento', 'observacao_encerramento',
    ];

    protected function casts(): array
    {
        return [
            'matriz_id' => 'integer',
            'reprodutor_id' => 'integer',
            'data_cobertura' => 'date',
            'data_confirmacao' => 'date',
            'data_prevista_parto' => 'date',
            'data_encerramento' => 'date',
        ];
    }

    public function matriz(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'matriz_id')->withTrashed();
    }

    public function reprodutor(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'reprodutor_id')->withTrashed();
    }

    public function exames(): HasMany
    {
        return $this->hasMany(ExameGestacao::class);
    }

    public function crias(): HasMany
    {
        return $this->hasMany(Animal::class, 'gestacao_nascimento_id')->withTrashed();
    }

    public function estaAberta(): bool
    {
        return in_array($this->status, self::ABERTOS, true);
    }
}
