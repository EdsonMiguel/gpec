<?php

namespace Database\Seeders;

use App\Modules\Fazendas\Models\Fazenda;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Fazenda::whereIn('codigo', ['DEMO-BOA-VISTA', 'DEMO-SANTA-CLARA'])->get() as $fazenda) {
            foreach ([
                ['codigo' => 'DEMO-PASTO', 'nome' => '[Demo] Pasto principal', 'tipo' => 'PASTO', 'area_ha' => '45.00'],
                ['codigo' => 'DEMO-PIQUETE', 'nome' => '[Demo] Piquete maternidade', 'tipo' => 'PIQUETE', 'area_ha' => '12.50'],
                ['codigo' => 'DEMO-CURRAL', 'nome' => '[Demo] Curral de manejo', 'tipo' => 'CURRAL', 'area_ha' => '0.75'],
                ['codigo' => 'DEMO-CONFINAMENTO', 'nome' => '[Demo] Confinamento', 'tipo' => 'CONFINAMENTO', 'area_ha' => '3.25'],
            ] as $data) {
                $fazenda->areas()->firstOrCreate(['codigo' => $data['codigo']], [
                    ...$data, 'ativo' => true, 'descricao' => 'Área fictícia para demonstração.',
                ]);
            }
        }
    }
}
