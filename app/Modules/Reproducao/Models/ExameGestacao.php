<?php

namespace App\Modules\Reproducao\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExameGestacao extends Model
{
    use BelongsToTenant;

    public const RESULTADOS = ['POSITIVO' => 'Positivo', 'NEGATIVO' => 'Negativo', 'INCONCLUSIVO' => 'Inconclusivo'];

    protected $table = 'exames_gestacao';

    protected $fillable = ['data_exame', 'metodo', 'resultado', 'responsavel', 'observacao'];

    protected function casts(): array
    {
        return ['data_exame' => 'date'];
    }

    public function gestacao(): BelongsTo
    {
        return $this->belongsTo(Gestacao::class);
    }
}
