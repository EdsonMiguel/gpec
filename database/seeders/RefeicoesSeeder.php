<?php

namespace Database\Seeders;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\Refeicao;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Database\Seeder;

class RefeicoesSeeder extends Seeder
{
    public function run(): void
    {
        $alimentos = Alimento::disponiveis()->whereIn('codigo', ['DEMO-SILAGEM', 'DEMO-RACAO', 'DEMO-SUPLEMENTO'])->orderBy('codigo')->limit(2)->get();
        if ($alimentos->isEmpty()) {
            return;
        }

        foreach (Animal::ativos()->whereIn('brinco', AnimaisSeeder::brincos())->get() as $animal) {
            foreach ([[2, 7, 'MANHA'], [1, 16, 'TARDE']] as [$daysAgo, $hour, $turno]) {
                $refeicao = Refeicao::firstOrCreate([
                    'animal_id' => $animal->id, 'data_fornecimento' => $animal->created_at->copy()->startOfDay()->subDays($daysAgo)->setTime($hour, 0),
                ], ['turno' => $turno, 'observacao' => '[Demo] Refeição fictícia para demonstração; quantidades ilustrativas.']);
                if (! $refeicao->wasRecentlyCreated) {
                    continue;
                }
                foreach ($alimentos as $alimento) {
                    $refeicao->itens()->create(['alimento_id' => $alimento->id, 'quantidade' => '1.000', 'unidade' => $alimento->unidade]);
                }
            }
        }
    }
}
