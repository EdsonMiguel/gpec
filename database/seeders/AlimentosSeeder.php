<?php

namespace Database\Seeders;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\TipoAlimento;
use Illuminate\Database\Seeder;

class AlimentosSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['codigo' => 'DEMO-SILAGEM', 'nome' => '[Demo] Silagem', 'tipo' => 'VOLUMOSO'],
            ['codigo' => 'DEMO-RACAO', 'nome' => '[Demo] Ração', 'tipo' => 'CONCENTRADO'],
            ['codigo' => 'DEMO-SUPLEMENTO', 'nome' => '[Demo] Suplemento', 'tipo' => 'SUPLEMENTO'],
        ] as $data) {
            Alimento::firstOrCreate(['codigo' => $data['codigo']], [
                'nome' => $data['nome'], 'tipo_alimento_id' => TipoAlimento::where('codigo', $data['tipo'])->firstOrFail()->id,
                'unidade' => 'KG', 'ativo' => true, 'descricao' => 'Alimento fictício para demonstração, sem formulação nutricional.',
            ]);
        }
    }
}
