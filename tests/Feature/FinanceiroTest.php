<?php

namespace Tests\Feature;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\TipoAlimento;
use App\Modules\Auth\Models\User;
use App\Modules\Financeiro\Models\ContaOrcamentaria;
use App\Modules\Financeiro\Models\ItemTransacao;
use App\Modules\Financeiro\Models\Produto;
use App\Modules\Financeiro\Models\Transacao;
use App\Modules\Financeiro\Support\Money;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinanceiroTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ContaOrcamentaria $despesa;

    private ContaOrcamentaria $receita;

    private Alimento $alimento;

    private Medicamento $medicamento;

    private Animal $animal;

    private Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($this->admin);
        $this->despesa = ContaOrcamentaria::create($this->contaData());
        $this->receita = ContaOrcamentaria::create($this->contaData(['codigo' => 'RECEITA', 'nome' => 'Vendas', 'natureza' => 'RECEITA']));
        $tipo = TipoAlimento::create(['codigo' => 'RACAO', 'nome' => 'Ração', 'ativo' => true]);
        $this->alimento = Alimento::create(['codigo' => 'ALIMENTO', 'nome' => 'Ração de teste', 'tipo_alimento_id' => $tipo->id, 'unidade' => 'KG', 'ativo' => true]);
        $this->medicamento = Medicamento::create(['nome' => 'Medicamento fictício', 'unidade' => 'ML', 'ativo' => true]);
        $this->animal = Animal::create(['brinco' => 'ANIMAL-001', 'sexo' => 'FEMEA', 'categoria' => 'VACA', 'status' => 'ATIVO']);
        $this->produto = Produto::create(['codigo' => 'LEITE', 'nome' => 'Leite de teste', 'unidade' => 'L', 'ativo' => true]);
    }

    private function contaData(array $attributes = []): array
    {
        return ['codigo' => 'DESPESA', 'nome' => 'Compras', 'natureza' => 'DESPESA', 'valor_orcado' => '10000.00', 'periodo_inicio' => today()->startOfYear()->toDateString(), 'periodo_fim' => today()->endOfYear()->toDateString(), 'ativo' => 1, ...$attributes];
    }

    private function data(array $attributes = []): array
    {
        return [
            'chave_idempotencia' => (string) Str::uuid(), 'conta_orcamentaria_id' => $this->despesa->id,
            'tipo' => 'COMPRA', 'descricao' => 'Compra de teste', 'pessoa_nome' => 'Fornecedor de teste',
            'data_transacao' => today()->toDateString(), 'data_vencimento' => today()->addDays(7)->toDateString(), 'status' => 'PENDENTE',
            'itens' => [['tipo_item' => 'ALIMENTO', 'alimento_id' => $this->alimento->id, 'quantidade' => '2.500', 'valor_unitario' => '10.99']],
            ...$attributes,
        ];
    }

    public function test_decimal_rounding_and_currency_formatting_are_exact(): void
    {
        $this->assertSame('0.30', Money::itemTotal('3.000', '0.10'));
        $this->assertSame('27.48', Money::itemTotal('2.500', '10.99'));
        $this->assertSame('0.01', Money::itemTotal('0.001', '5.00'));
        $this->assertSame('999999999999.99', Money::itemTotal('1', '999999999999.99'));
        $this->assertSame('R$ 999.999.999.999,99', Money::format('999999999999.99'));
        $this->assertSame('-R$ 1.234,50', Money::format('-1234.50'));
    }

    public function test_purchase_calculates_all_item_totals_on_server_and_preserves_snapshots(): void
    {
        $this->post('/transacoes', $this->data(['itens' => [
            ['tipo_item' => 'ALIMENTO', 'alimento_id' => $this->alimento->id, 'quantidade' => '2.500', 'valor_unitario' => '10.99'],
            ['tipo_item' => 'MEDICAMENTO', 'medicamento_id' => $this->medicamento->id, 'quantidade' => '3.000', 'valor_unitario' => '0.10'],
        ]]))->assertSessionHasNoErrors()->assertRedirect();
        $record = Transacao::firstOrFail();
        $this->assertSame('27.78', $record->valor_total);
        $this->assertSame('DESPESA', $record->natureza);
        $this->assertSame(2, $record->itens()->count());
        $item = $record->itens()->where('tipo_item', 'ALIMENTO')->firstOrFail();
        $this->assertSame('KG', $item->unidade);
        $this->alimento->update(['nome' => 'Nome posterior']);
        $this->assertSame('Ração de teste', $item->fresh()->descricao);
        $this->delete('/alimentos/'.$this->alimento->id)->assertSessionHasErrors('alimento');
    }

    public function test_animal_purchases_sales_and_other_products_are_supported(): void
    {
        foreach ([['COMPRA', $this->despesa], ['VENDA', $this->receita]] as [$type, $account]) {
            $this->post('/transacoes', $this->data(['tipo' => $type, 'conta_orcamentaria_id' => $account->id,
                'itens' => [['tipo_item' => 'ANIMAL', 'animal_id' => $this->animal->id, 'quantidade' => '1', 'valor_unitario' => '2500.00']],
            ]))->assertSessionHasNoErrors();
        }
        $this->post('/transacoes', $this->data(['tipo' => 'VENDA', 'conta_orcamentaria_id' => $this->receita->id,
            'itens' => [['tipo_item' => 'PRODUTO', 'produto_id' => $this->produto->id, 'quantidade' => '10', 'valor_unitario' => '2.15']],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(3, Transacao::count());
        $this->assertSame('21.50', Transacao::latest('id')->firstOrFail()->valor_total);
        $this->assertSame('ATIVO', $this->animal->fresh()->status);
    }

    public function test_free_items_require_description_and_round_each_line_before_summing(): void
    {
        $item = ['tipo_item' => 'OUTRO', 'descricao' => 'Serviço de teste', 'unidade' => 'UN', 'quantidade' => '0.001', 'valor_unitario' => '5.00'];
        $this->post('/transacoes', $this->data(['itens' => [$item, $item]]))->assertSessionHasNoErrors();
        $this->assertSame('0.02', Transacao::firstOrFail()->valor_total);
        unset($item['descricao']);
        $this->post('/transacoes', $this->data(['itens' => [$item]]))->assertSessionHasErrors('itens.0.descricao');
        $this->assertSame(1, Transacao::count());
    }

    public function test_retries_are_idempotent_and_changed_payloads_cannot_reuse_a_key(): void
    {
        $data = $this->data();
        $first = $this->post('/transacoes', $data)->assertSessionHasNoErrors();
        $this->post('/transacoes', $data)->assertSessionHasNoErrors()->assertRedirect($first->headers->get('Location'));
        $changed = $data;
        $changed['itens'][0]['valor_unitario'] = '15.00';
        $this->post('/transacoes', $changed)->assertSessionHasErrors('chave_idempotencia');
        $record = Transacao::firstOrFail();
        $this->post('/transacoes/'.$record->id.'/efetivar', ['data_efetivacao' => today()->toDateString(), 'forma_pagamento' => 'PIX'])->assertSessionHasNoErrors();
        $this->post('/transacoes', $data)->assertSessionHasNoErrors();
        $this->assertSame('EFETIVADA', $record->fresh()->status);
        $this->assertSame(1, Transacao::count());
    }

    public function test_settlement_and_cancellation_keep_original_values_and_remove_report_totals(): void
    {
        $this->post('/transacoes', $this->data())->assertSessionHasNoErrors();
        $record = Transacao::firstOrFail();
        $this->despesa->update(['ativo' => false]);
        $payment = ['data_efetivacao' => today()->toDateString(), 'forma_pagamento' => 'PIX'];
        $this->post('/transacoes/'.$record->id.'/efetivar', $payment)->assertSessionHasNoErrors();
        $this->post('/transacoes/'.$record->id.'/efetivar', $payment)->assertSessionHasNoErrors();
        $this->get('/transacoes')->assertOk()->assertViewHas('despesas', '27.48')->assertViewHas('aPagar', '0');
        $this->post('/transacoes/'.$record->id.'/cancelar', ['motivo_cancelamento' => 'Lançamento duplicado'])->assertSessionHasNoErrors();
        $this->assertSame('CANCELADA', $record->fresh()->status);
        $this->assertSame('27.48', $record->fresh()->valor_total);
        $this->assertNotNull($record->fresh()->data_efetivacao);
        $this->assertSame('PIX', $record->fresh()->forma_pagamento);
        $this->get('/transacoes')->assertOk()->assertViewHas('despesas', '0')->assertViewHas('aPagar', '0');
        $this->post('/transacoes/'.$record->id.'/efetivar', $payment)->assertSessionHasErrors('transacao');
        $this->assertSame(1, $record->itens()->count());
    }

    public function test_account_nature_period_and_activity_are_checked(): void
    {
        $this->post('/transacoes', $this->data(['tipo' => 'VENDA']))->assertSessionHasErrors('conta_orcamentaria_id');
        $this->despesa->update(['periodo_inicio' => today()->addDay()]);
        $this->post('/transacoes', $this->data())->assertSessionHasErrors('data_transacao');
        $this->despesa->update(['ativo' => false]);
        $this->post('/transacoes', $this->data())->assertSessionHasErrors('conta_orcamentaria_id');
        $this->assertSame(0, Transacao::count());
    }

    public function test_used_accounts_and_products_cannot_be_deleted_or_retyped(): void
    {
        $this->post('/transacoes', $this->data())->assertSessionHasNoErrors();
        $this->delete('/contas-orcamentarias/'.$this->despesa->id)->assertSessionHasErrors('conta');
        $this->put('/contas-orcamentarias/'.$this->despesa->id, $this->contaData(['natureza' => 'RECEITA']))->assertSessionHasErrors('conta');
        $this->put('/contas-orcamentarias/'.$this->despesa->id, $this->contaData(['valor_orcado' => '20000.00']))->assertSessionHasNoErrors();
        $this->assertSame('20000.00', $this->despesa->fresh()->valor_orcado);
        $this->post('/transacoes', $this->data(['itens' => [['tipo_item' => 'PRODUTO', 'produto_id' => $this->produto->id, 'quantidade' => 1, 'valor_unitario' => 2]]]))->assertSessionHasNoErrors();
        $this->delete('/produtos/'.$this->produto->id)->assertSessionHasErrors('produto');
        $this->put('/produtos/'.$this->produto->id, ['codigo' => 'LEITE', 'nome' => 'Leite', 'unidade' => 'KG', 'ativo' => 1])->assertSessionHasErrors('unidade');
    }

    public function test_catalog_crud_and_all_financial_views_render(): void
    {
        $this->post('/contas-orcamentarias', $this->contaData(['codigo' => 'NOVA', 'nome' => 'Nova conta']))->assertSessionHasNoErrors();
        $account = ContaOrcamentaria::where('codigo', 'NOVA')->firstOrFail();
        $this->delete('/contas-orcamentarias/'.$account->id)->assertRedirect();
        $this->post('/produtos', ['codigo' => 'NOVO', 'nome' => 'Novo produto', 'unidade' => 'UN', 'ativo' => 1])->assertSessionHasNoErrors();
        $product = Produto::where('codigo', 'NOVO')->firstOrFail();
        $this->put('/produtos/'.$product->id, ['codigo' => 'NOVO', 'nome' => 'Produto editado', 'unidade' => 'KG', 'ativo' => 0])->assertSessionHasNoErrors();
        $this->delete('/produtos/'.$product->id)->assertRedirect();
        $this->post('/transacoes', $this->data())->assertSessionHasNoErrors();
        $record = Transacao::firstOrFail();
        foreach (['/transacoes', '/transacoes/create', '/transacoes/'.$record->id, '/contas-orcamentarias', '/contas-orcamentarias/create', '/contas-orcamentarias/'.$this->despesa->id.'/edit', '/produtos', '/produtos/create', '/produtos/'.$this->produto->id.'/edit'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->put('/transacoes/'.$record->id, [])->assertStatus(405);
        $this->delete('/transacoes/'.$record->id)->assertStatus(405);
    }

    public function test_invalid_amounts_dates_and_tampered_totals_are_rejected_atomically(): void
    {
        $this->post('/transacoes', $this->data(['valor_total' => '0.01']))->assertSessionHasErrors('valor_total');
        $this->post('/transacoes', $this->data(['natureza' => 'RECEITA', 'tenant_id' => 999]))->assertSessionHasErrors(['natureza', 'tenant_id']);
        $this->post('/transacoes', $this->data(['status' => 'EFETIVADA']))->assertSessionHasErrors(['data_efetivacao', 'forma_pagamento']);
        $this->post('/transacoes', $this->data(['data_transacao' => today()->addDay()->toDateString()]))->assertSessionHasErrors('data_transacao');
        $this->post('/transacoes', $this->data(['itens' => [['tipo_item' => 'OUTRO', 'descricao' => 'Teste', 'quantidade' => '-1', 'valor_unitario' => '10']]]))->assertSessionHasErrors('itens.0.quantidade');
        $this->post('/transacoes', $this->data(['itens' => [['tipo_item' => 'OUTRO', 'descricao' => 'Teste', 'quantidade' => '0.001', 'valor_unitario' => '0.01']]]))->assertSessionHasErrors('itens.0.valor_unitario');
        $this->post('/transacoes', $this->data(['itens' => [['tipo_item' => 'OUTRO', 'descricao' => 'Teste', 'quantidade' => '999999999', 'valor_unitario' => '999999999999.99']]]))->assertSessionHasErrors('itens.0.valor_unitario');
        $this->assertSame(0, Transacao::count());
        $this->assertSame(0, ItemTransacao::count());
    }

    public function test_animal_items_require_one_distinct_animal_and_only_matching_reference(): void
    {
        $item = ['tipo_item' => 'ANIMAL', 'animal_id' => $this->animal->id, 'quantidade' => 2, 'valor_unitario' => '100'];
        $this->post('/transacoes', $this->data(['itens' => [$item]]))->assertSessionHasErrors('itens.0.quantidade');
        $item['quantidade'] = 1;
        $this->post('/transacoes', $this->data(['itens' => [$item, $item]]))->assertSessionHasErrors('itens.1.animal_id');
        $this->post('/transacoes', $this->data(['itens' => [[...$item, 'alimento_id' => $this->alimento->id]]]))->assertSessionHasErrors('itens.0.alimento_id');
        $this->animal->delete();
        $this->post('/transacoes', $this->data(['itens' => [$item]]))->assertSessionHasNoErrors();
    }

    public function test_other_tenant_data_and_financials_are_inaccessible(): void
    {
        $this->post('/transacoes', $this->data())->assertSessionHasNoErrors();
        $record = Transacao::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->get('/transacoes/'.$record->id)->assertNotFound();
        $this->post('/transacoes/'.$record->id.'/cancelar', ['motivo_cancelamento' => 'Teste'])->assertNotFound();
        $this->get('/contas-orcamentarias/'.$this->despesa->id.'/edit')->assertNotFound();
        $this->get('/produtos/'.$this->produto->id.'/edit')->assertNotFound();
        $this->post('/transacoes', $this->data())->assertSessionHasErrors(['conta_orcamentaria_id', 'itens.0.alimento_id']);
        $this->assertSame(0, Transacao::count());
    }

    public function test_financials_are_exclusive_to_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Vaqueiro', 'tenant_id' => $this->admin->tenant_id]));
        foreach (['/transacoes', '/transacoes/create', '/contas-orcamentarias', '/produtos'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/transacoes', $this->data())->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertDontSee('>Financeiro<', false);
    }

    public function test_database_rejects_cross_tenant_item_foreign_key(): void
    {
        $this->post('/transacoes', $this->data(['itens' => [['tipo_item' => 'PRODUTO', 'produto_id' => $this->produto->id, 'quantidade' => 1, 'valor_unitario' => '10']]]))->assertSessionHasNoErrors();
        $item = ItemTransacao::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $foreign = Produto::create(['codigo' => 'OUTRO', 'nome' => 'Outro produto', 'unidade' => 'UN', 'ativo' => true]);
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('itens_transacao')->where('id', $item->id)->update(['produto_id' => $foreign->id]));
    }

    public function test_already_paid_sale_can_be_registered_with_payment_details(): void
    {
        $this->post('/transacoes', $this->data([
            'tipo' => 'VENDA', 'conta_orcamentaria_id' => $this->receita->id, 'status' => 'EFETIVADA',
            'data_efetivacao' => today()->toDateString(), 'forma_pagamento' => 'DINHEIRO',
            'itens' => [['tipo_item' => 'PRODUTO', 'produto_id' => $this->produto->id, 'quantidade' => '10', 'valor_unitario' => '2.15']],
        ]))->assertSessionHasNoErrors();
        $record = Transacao::firstOrFail();
        $this->assertSame('EFETIVADA', $record->status);
        $this->assertSame('RECEITA', $record->natureza);
        $this->assertSame('21.50', $record->valor_total);
        $this->get('/transacoes')->assertOk()->assertViewHas('receitas', fn (string $value): bool => bccomp($value, '21.50', 2) === 0);
    }

    public function test_database_requires_account_nature_to_match_the_transaction(): void
    {
        $this->post('/transacoes', $this->data())->assertSessionHasNoErrors();
        $record = Transacao::firstOrFail();
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('transacoes')->where('id', $record->id)->update(['tipo' => 'VENDA', 'natureza' => 'RECEITA']));
    }
}
