<?php

namespace Database\Seeders;

use App\Modules\Alimentacao\Models\TipoAlimento;
use Illuminate\Database\Seeder;

class TiposAlimentoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['VOLUMOSO' => 'Volumoso', 'CONCENTRADO' => 'Concentrado', 'SUPLEMENTO' => 'Suplemento', 'OUTRO' => 'Outro'] as $codigo => $nome) {
            TipoAlimento::firstOrCreate(['codigo' => $codigo], ['nome' => $nome, 'ativo' => true]);
        }
    }
}
