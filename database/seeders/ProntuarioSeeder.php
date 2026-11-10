<?php

namespace Database\Seeders;

use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Actions\AcompanharCasoClinico;
use App\Modules\Sanidade\Models\Doenca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ProntuarioSeeder extends Seeder
{
    public function run(AcompanharCasoClinico $acompanhamento): void
    {
        $doenca = Doenca::firstOrCreate(['nome' => '[Demo] Condição fictícia'], [
            'descricao' => 'Exemplo para demonstrar o vínculo com uma avaliação veterinária.',
            'ativo' => true,
        ]);
        $animal = Animal::ativos()->where('brinco', 'DEMO-0003')->first();
        $referencia = Auth::user()->tenant->created_at->startOfDay();
        $inicio = $referencia->copy()->subDays(4)->toDateString();
        if ($animal === null || $animal->casosClinicos()->exists()
            || ($animal->data_nascimento !== null && $animal->data_nascimento->toDateString() > $inicio)) {
            return;
        }

        $caso = $acompanhamento->abrir([
            'animal_id' => $animal->id,
            'titulo' => '[Demo] Caso em acompanhamento',
            'data_inicio' => $inicio,
            'sintomas_iniciais' => 'Relato fictício para demonstrar o prontuário.',
        ]);
        $acompanhamento->avaliar($caso, [
            'data_avaliacao' => $referencia->copy()->subDays(2)->toDateString(),
            'veterinario' => '[Demo] Profissional fictício',
            'parecer' => 'Registro fictício de avaliação.',
            'doenca_id' => $doenca->ativo ? $doenca->id : null,
            'situacao_diagnostico' => $doenca->ativo ? 'SUSPEITO' : null,
        ]);
    }
}
