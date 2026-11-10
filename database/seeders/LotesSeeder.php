<?php

namespace Database\Seeders;

use App\Modules\Rebanho\Models\Lote;
use Illuminate\Database\Seeder;

class LotesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Cria', 'Recria', 'Engorda'] as $nome) {
            Lote::firstOrCreate(['nome' => '[Demo] '.$nome], ['descricao' => 'Lote fictício de '.$nome.' para demonstração.']);
        }
    }
}
