<?php

namespace Database\Seeders;

use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Database\Seeder;

class ManejosSanitariosSeeder extends Seeder
{
    public function run(): void
    {
        $medicamentos = Medicamento::whereIn('nome', ['[Demo] Produto fictício A', '[Demo] Produto fictício B', '[Demo] Produto fictício C'])->get()->keyBy('nome');

        foreach (Animal::whereIn('brinco', AnimaisSeeder::brincos())->orderBy('brinco')->get() as $animal) {
            $index = (int) substr($animal->brinco, 5) - 1;
            $reference = $animal->created_at->copy()->startOfDay();
            $medicamento = $medicamentos['[Demo] Produto fictício '.($index % 2 === 0 ? 'A' : 'B')];

            ManejoSanitario::firstOrCreate([
                'animal_id' => $animal->id, 'medicamento_id' => $medicamento->id,
                'data_aplicacao' => $reference->copy()->subDays(45),
                'tipo' => $index % 2 === 0 ? 'VERMIFUGO' : 'VACINA',
            ], ['dosagem' => '1.000', 'unidade' => $medicamento->unidade, 'proxima_aplicacao' => $reference->copy()->subDays(15)->toDateString()]);

            ManejoSanitario::firstOrCreate([
                'animal_id' => $animal->id, 'medicamento_id' => $medicamento->id,
                'data_aplicacao' => $reference->copy()->subDays(14),
                'tipo' => $index % 2 === 0 ? 'VERMIFUGO' : 'VACINA',
            ], [
                'dosagem' => '1.000', 'unidade' => $medicamento->unidade,
                'proxima_aplicacao' => $reference->copy()->addDays([-1, 3, 20][$index % 3])->toDateString(),
            ]);
        }
    }
}
