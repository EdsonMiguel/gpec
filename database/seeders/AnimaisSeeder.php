<?php

namespace Database\Seeders;

use App\Modules\Fazendas\Models\Fazenda;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use Illuminate\Database\Seeder;

class AnimaisSeeder extends Seeder
{
    public static function brincos(): array
    {
        return array_map(fn (int $number): string => sprintf('DEMO-%04d', $number), range(1, 16));
    }

    public function run(): void
    {
        $reference = Fazenda::where('codigo', 'DEMO-BOA-VISTA')->firstOrFail()->created_at->startOfDay();
        $lotes = Lote::whereIn('nome', ['[Demo] Cria', '[Demo] Recria', '[Demo] Engorda'])->get()->keyBy('nome');
        $categorias = [
            ['categoria' => 'BEZERRO', 'sexo' => 'MACHO', 'meses' => 6, 'lote' => 'Cria'],
            ['categoria' => 'BEZERRA', 'sexo' => 'FEMEA', 'meses' => 6, 'lote' => 'Cria'],
            ['categoria' => 'GARROTE', 'sexo' => 'MACHO', 'meses' => 15, 'lote' => 'Recria'],
            ['categoria' => 'NOVILHO', 'sexo' => 'MACHO', 'meses' => 24, 'lote' => 'Recria'],
            ['categoria' => 'NOVILHA', 'sexo' => 'FEMEA', 'meses' => 24, 'lote' => 'Recria'],
            ['categoria' => 'BOI', 'sexo' => 'MACHO', 'meses' => 36, 'lote' => 'Engorda'],
            ['categoria' => 'VACA', 'sexo' => 'FEMEA', 'meses' => 60, 'lote' => 'Cria'],
            ['categoria' => 'TOURO', 'sexo' => 'MACHO', 'meses' => 60, 'lote' => 'Cria'],
        ];

        foreach ($categorias as $index => $data) {
            for ($number = 1; $number <= 2; $number++) {
                $sequence = $index * 2 + $number;
                $brinco = sprintf('DEMO-%04d', $sequence);
                Animal::withTrashed()->firstOrCreate(['brinco' => $brinco], [
                    'nome_ou_numero' => 'Animal demonstração '.$sequence,
                    'categoria' => $data['categoria'], 'sexo' => $data['sexo'],
                    'raca' => $number === 1 ? 'Nelore' : 'Angus',
                    'data_nascimento' => $reference->copy()->subMonthsNoOverflow($data['meses']),
                    'status' => $sequence === 16 ? 'VENDIDO' : 'ATIVO',
                    'lote_id' => $sequence === 16 ? null : $lotes['[Demo] '.$data['lote']]->id,
                ]);
            }
        }
    }
}
