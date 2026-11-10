<?php

namespace Database\Seeders;

use App\Modules\Fazendas\Models\Fazenda;
use Illuminate\Database\Seeder;

class FazendasSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['codigo' => 'DEMO-BOA-VISTA', 'nome' => '[Demo] Fazenda Boa Vista', 'municipio' => 'Uberaba', 'uf' => 'MG', 'area_total_ha' => '350.00'],
            ['codigo' => 'DEMO-SANTA-CLARA', 'nome' => '[Demo] Fazenda Santa Clara', 'municipio' => 'Ribeirão Preto', 'uf' => 'SP', 'area_total_ha' => '180.50'],
        ] as $data) {
            Fazenda::firstOrCreate(['codigo' => $data['codigo']], [
                ...$data, 'ativo' => true, 'observacao' => 'Cadastro fictício para demonstração do sistema.',
            ]);
        }
    }
}
