<?php

namespace Database\Seeders;

use App\Modules\Ponderal\Models\MotivoPesagem;
use Illuminate\Database\Seeder;

class MotivosPesagemSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'ACOMPANHAMENTO' => ['Acompanhamento de crescimento', 'Acompanhamento periódico do peso do animal.'],
            'NASCIMENTO' => ['Nascimento', 'Registro do peso ao nascer.'],
            'DESMAMA' => ['Desmama', 'Registro do peso na desmama.'],
            'ENTRADA' => ['Entrada na propriedade', 'Registro do peso na chegada à propriedade.'],
            'SAIDA' => ['Saída da propriedade', 'Registro do peso na venda ou transferência.'],
            'REPRODUCAO' => ['Avaliação reprodutiva', 'Pesagem para acompanhamento reprodutivo.'],
        ] as $codigo => [$nome, $descricao]) {
            MotivoPesagem::firstOrCreate(['codigo' => $codigo], ['nome' => $nome, 'descricao' => $descricao, 'ativo' => true]);
        }
    }
}
