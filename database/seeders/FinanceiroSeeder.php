<?php

namespace Database\Seeders;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Financeiro\Actions\RegistrarTransacao;
use App\Modules\Financeiro\Models\ContaOrcamentaria;
use App\Modules\Financeiro\Models\Produto;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class FinanceiroSeeder extends Seeder
{
    public function run(): void
    {
        $reference = Auth::user()->tenant->created_at->startOfDay();
        $year = $reference->year;
        foreach ([
            'ALIMENTOS' => ['Compra de alimentos', 'DESPESA', '1000.00'],
            'MEDICAMENTOS' => ['Compra de medicamentos', 'DESPESA', '500.00'],
            'COMPRA-ANIMAIS' => ['Compra de animais', 'DESPESA', '10000.00'],
            'OUTRAS-DESPESAS' => ['Outras despesas', 'DESPESA', '1000.00'],
            'VENDA-ANIMAIS' => ['Venda de animais', 'RECEITA', '15000.00'],
            'VENDA-PRODUTOS' => ['Venda de outros produtos', 'RECEITA', '3000.00'],
        ] as $code => [$name, $nature, $budget]) {
            ContaOrcamentaria::firstOrCreate(['codigo' => "DEMO-{$code}-{$year}"], [
                'nome' => '[Demo] '.$name, 'natureza' => $nature, 'valor_orcado' => $budget,
                'periodo_inicio' => $reference->copy()->startOfYear(), 'periodo_fim' => $reference->copy()->endOfYear(),
                'ativo' => true, 'descricao' => 'Orçamento fictício para demonstração.',
            ]);
        }
        foreach (['LEITE' => ['[Demo] Leite', 'L'], 'RURAL' => ['[Demo] Produto rural', 'UN']] as $code => [$name, $unit]) {
            Produto::firstOrCreate(['codigo' => 'DEMO-'.$code], ['nome' => $name, 'unidade' => $unit, 'ativo' => true]);
        }
        $food = Alimento::disponiveis()->where('codigo', 'DEMO-RACAO')->first();
        $medicine = Medicamento::where('ativo', true)->where('nome', '[Demo] Produto fictício B')->first();
        $animal = Animal::withTrashed()->where('brinco', 'DEMO-0001')->first();
        $saleAnimal = Animal::withTrashed()->where('brinco', 'DEMO-0002')->first();
        $product = Produto::where('codigo', 'DEMO-LEITE')->where('ativo', true)->first();
        $examples = [
            ['ALIMENTOS', 'COMPRA', 'ALIMENTO', 'alimento_id', $food?->id, '10.000', '5.00'],
            ['MEDICAMENTOS', 'COMPRA', 'MEDICAMENTO', 'medicamento_id', $medicine?->id, '2.000', '10.00'],
            ['COMPRA-ANIMAIS', 'COMPRA', 'ANIMAL', 'animal_id', $animal?->id, '1.000', '2000.00'],
            ['VENDA-ANIMAIS', 'VENDA', 'ANIMAL', 'animal_id', $saleAnimal?->id, '1.000', '2500.00'],
            ['VENDA-PRODUTOS', 'VENDA', 'PRODUTO', 'produto_id', $product?->id, '20.000', '2.50'],
        ];
        $registrar = app(RegistrarTransacao::class);
        foreach ($examples as [$code, $type, $itemType, $field, $id, $quantity, $price]) {
            if ($id === null) {
                continue;
            }
            $conta = ContaOrcamentaria::where('codigo', "DEMO-{$code}-{$year}")->firstOrFail();
            $registrar->handle([
                'chave_idempotencia' => hash('sha256', 'financeiro-demo-'.Auth::user()->tenant_id.'-'.$code.'-'.$year),
                'conta_orcamentaria_id' => $conta->id, 'tipo' => $type, 'descricao' => '[Demo] Transação '.$code,
                'pessoa_nome' => '[Demo] Parceiro comercial', 'data_transacao' => $reference->toDateString(),
                'data_vencimento' => $reference->copy()->addDays(7)->toDateString(), 'status' => 'PENDENTE',
                'observacao' => 'Transação fictícia para demonstração.',
                'itens' => [['tipo_item' => $itemType, $field => $id, 'quantidade' => $quantity, 'valor_unitario' => $price]],
            ]);
        }
    }
}
