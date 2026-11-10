<?php

namespace Database\Seeders;

use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Actions\AcompanharGestacao;
use App\Modules\Reproducao\Models\Gestacao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ReproducaoSeeder extends Seeder
{
    public function run(AcompanharGestacao $acompanhamento): void
    {
        $referencia = Auth::user()->tenant->created_at->startOfDay();
        $dataCobertura = $referencia->copy()->subDays(50)->toDateString();
        $touro = Animal::ativos()->where('brinco', 'DEMO-0015')->where('sexo', 'MACHO')->first();

        foreach ([
            ['DEMO-0013', 'INSEMINACAO', 'POSITIVO'],
            ['DEMO-0014', 'MONTA', 'NEGATIVO'],
        ] as [$brinco, $tipo, $resultado]) {
            $matriz = Animal::ativos()->where('brinco', $brinco)->where('sexo', 'FEMEA')->first();
            if ($matriz === null || Gestacao::where('matriz_id', $matriz->id)->exists()
                || ($matriz->data_nascimento !== null && $matriz->data_nascimento->toDateString() >= $dataCobertura)) {
                continue;
            }
            if ($tipo === 'MONTA' && ($touro === null
                || ($touro->data_nascimento !== null && $touro->data_nascimento->toDateString() >= $dataCobertura))) {
                continue;
            }

            $gestacao = $acompanhamento->iniciar([
                'matriz_id' => $matriz->id,
                'reprodutor_id' => $tipo === 'MONTA' ? $touro->id : null,
                'tipo' => $tipo,
                'data_cobertura' => $dataCobertura,
                'referencia_semen' => $tipo === 'INSEMINACAO' ? 'DEMO-SEMEN-01' : null,
                'observacao' => '[Demo] Cobertura fictícia.',
            ]);
            $acompanhamento->registrarExame($gestacao, [
                'data_exame' => $referencia->copy()->subDays(5)->toDateString(),
                'metodo' => 'Ultrassonografia',
                'resultado' => $resultado,
                'data_prevista_parto' => $resultado === 'POSITIVO' ? $referencia->copy()->addDays(235)->toDateString() : null,
                'observacao' => '[Demo] Exame fictício.',
            ]);
        }
    }
}
