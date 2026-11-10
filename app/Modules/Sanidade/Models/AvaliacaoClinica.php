<?php

namespace App\Modules\Sanidade\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvaliacaoClinica extends Model
{
    use BelongsToTenant;

    public const DIAGNOSTICOS = [
        'SUSPEITO' => 'Suspeito',
        'CONFIRMADO' => 'Confirmado',
        'DESCARTADO' => 'Descartado',
        'INCONCLUSIVO' => 'Inconclusivo',
    ];

    protected $table = 'avaliacoes_clinicas';

    protected $fillable = ['data_avaliacao', 'veterinario', 'sintomas', 'parecer', 'orientacao', 'doenca_id', 'situacao_diagnostico'];

    protected function casts(): array
    {
        return ['data_avaliacao' => 'date'];
    }

    public function doenca(): BelongsTo
    {
        return $this->belongsTo(Doenca::class);
    }
}
