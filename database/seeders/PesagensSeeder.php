<?php

namespace Database\Seeders;

use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Database\Seeder;

class PesagensSeeder extends Seeder
{
    public function run(): void
    {
        $motivo = MotivoPesagem::where('codigo', 'ACOMPANHAMENTO')->firstOrFail();
        $pesos = ['BEZERRO' => 100, 'BEZERRA' => 90, 'GARROTE' => 200, 'NOVILHO' => 280, 'NOVILHA' => 260, 'BOI' => 450, 'VACA' => 420, 'TOURO' => 650];

        foreach (Animal::whereIn('brinco', AnimaisSeeder::brincos())->orderBy('brinco')->get() as $animal) {
            foreach ([60, 30, 1] as $index => $daysAgo) {
                $date = $animal->created_at->copy()->startOfDay()->subDays($daysAgo)->setTime(8, 0);
                Pesagem::firstOrCreate(['animal_id' => $animal->id, 'data_pesagem' => $date], [
                    'motivo_pesagem_id' => $motivo->id,
                    'peso' => ($pesos[$animal->categoria] + $index * 18).'.50',
                    'observacao' => '[Demo] Pesagem fictícia de acompanhamento.',
                ]);
            }
        }
    }
}
