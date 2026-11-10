<?php

namespace Database\Seeders;

use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Database\Seeder;

class MedicamentosSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['A' => 'ML', 'B' => 'DOSE', 'C' => 'G'] as $codigo => $unidade) {
            Medicamento::firstOrCreate(['nome' => '[Demo] Produto fictício '.$codigo], [
                'principio_ativo' => 'Produto fictício, sem formulação clínica.', 'unidade' => $unidade, 'ativo' => true,
            ]);
        }
    }
}
