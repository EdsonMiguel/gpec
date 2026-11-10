<?php

namespace Tests\Feature;

use App\Modules\Agenda\Models\Compromisso;
use App\Modules\Auth\Models\User;
use App\Modules\Financeiro\Models\ContaOrcamentaria;
use App\Modules\Financeiro\Models\Transacao;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Models\Gestacao;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Animal $animal;

    private Medicamento $medicamento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($this->admin);
        $this->animal = Animal::create(['brinco' => 'AGENDA-01', 'sexo' => 'FEMEA', 'categoria' => 'VACA', 'status' => 'ATIVO']);
        $this->medicamento = Medicamento::create(['nome' => 'Produto fictício', 'unidade' => 'ML', 'ativo' => true]);
    }

    public function test_dashboard_shows_upcoming_reminders_and_links_to_the_exact_application(): void
    {
        $manejo = $this->manejo(today()->subDays(2)->toDateString(), today()->addDay()->toDateString());
        $registro = Compromisso::firstOrFail();
        $this->assertTrue($registro->origem->is($manejo));

        $this->get('/dashboard')->assertOk()->assertSeeText('Agenda geral')->assertDontSeeText('Agenda sanitária')
            ->assertSeeText('Brinco AGENDA-01')->assertViewHas('compromissosTotal', 1);
        $this->get($registro->urlOrigem())->assertOk()
            ->assertSeeText('Aplicação de origem do compromisso')
            ->assertViewHas('manejos', fn ($manejos): bool => $manejos->count() === 1 && $manejos->first()->is($manejo));

        $this->patch('/agenda/'.$registro->id, ['status' => 'CONCLUIDO', 'retorno' => 'dashboard'])->assertRedirect('/dashboard');
        $this->assertSame('CONCLUIDO', $registro->fresh()->status);
        $this->get('/dashboard')->assertOk()->assertViewHas('compromissosTotal', 0);
        $this->get('/agenda?status=CONCLUIDO')->assertOk()->assertSeeText('Concluído')->assertSeeText('Produto fictício');
        $this->assertNotNull($manejo->fresh()->proxima_aplicacao);
    }

    public function test_cancelled_and_deleted_reminders_are_not_recreated_by_updates_to_the_source(): void
    {
        $manejo = $this->manejo(today()->subDays(2)->toDateString(), today()->addDay()->toDateString());
        $registro = Compromisso::firstOrFail();
        $this->patch('/agenda/'.$registro->id, ['status' => 'CANCELADO'])->assertSessionHasNoErrors()->assertRedirect('/agenda');
        $manejo->update(['proxima_aplicacao' => today()->addDays(2)->toDateString()]);
        $this->assertSame('CANCELADO', $registro->fresh()->status);
        $this->patch('/agenda/'.$registro->id, ['status' => 'CONCLUIDO'])->assertSessionHasErrors('status');
        $this->delete('/agenda/'.$registro->id)->assertRedirect('/agenda');
        $manejo->update(['proxima_aplicacao' => today()->addDays(3)->toDateString()]);
        $this->assertSame(0, Compromisso::count());
        $this->assertSoftDeleted($registro);
        $this->assertDatabaseHas('manejos_sanitarios', ['id' => $manejo->id]);
    }

    public function test_new_application_completes_the_previous_reminder_and_archiving_cancels_the_latest(): void
    {
        $antigo = $this->manejo(today()->subDays(3)->toDateString(), today()->subDay()->toDateString());
        $novo = $this->manejo(today()->toDateString(), today()->addDays(10)->toDateString());
        $this->assertSame('CONCLUIDO', Compromisso::whereMorphedTo('origem', $antigo)->firstOrFail()->status);
        $this->assertSame('PENDENTE', Compromisso::whereMorphedTo('origem', $novo)->firstOrFail()->status);
        $this->manejo(today()->subDays(4)->toDateString(), today()->addDays(2)->toDateString());
        $this->assertSame(2, Compromisso::count());
        $this->get('/dashboard')->assertOk()->assertViewHas('compromissosTotal', 0);
        $this->delete('/animais/'.$this->animal->id)->assertRedirect('/animais');
        $this->assertSame('CANCELADO', Compromisso::whereMorphedTo('origem', $novo)->firstOrFail()->status);
    }

    public function test_gestation_reschedules_one_reminder_and_birth_completes_it(): void
    {
        $gestacao = $this->gestacao();
        $item = Compromisso::whereMorphedTo('origem', $gestacao)->firstOrFail();
        $this->assertSame(route('gestacoes.show', $gestacao), $item->urlOrigem());
        $novaData = today()->addDays(15)->toDateString();
        $this->post('/gestacoes/'.$gestacao->id.'/exames', [
            'data_exame' => today()->toDateString(), 'metodo' => 'Exame de teste',
            'resultado' => 'POSITIVO', 'data_prevista_parto' => $novaData,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, Compromisso::whereMorphedTo('origem', $gestacao)->count());
        $this->assertSame($novaData, $item->fresh()->data_prevista->toDateString());
        $this->post('/gestacoes/'.$gestacao->id.'/encerrar', [
            'data_encerramento' => today()->toDateString(), 'status' => 'PARTO',
        ])->assertSessionHasNoErrors();
        $this->assertSame('CONCLUIDO', $item->fresh()->status);
    }

    public function test_financial_reminders_are_private_and_follow_transaction_status(): void
    {
        $transacao = $this->transacao();
        $item = Compromisso::whereMorphedTo('origem', $transacao)->firstOrFail();
        $this->assertSame(route('transacoes.show', $transacao), $item->urlOrigem());
        $this->get('/dashboard')->assertOk()->assertSeeText('Compra reservada');

        $this->actingAs(User::factory()->create(['role' => 'Vaqueiro', 'tenant_id' => $this->admin->tenant_id]));
        $this->get('/dashboard')->assertOk()->assertDontSeeText('Compra reservada')->assertViewHas('compromissosTotal', 0);
        $this->get('/agenda')->assertOk()->assertDontSeeText('Vencimento financeiro');
        $this->patch('/agenda/'.$item->id, ['status' => 'CONCLUIDO'])->assertNotFound();
        $this->delete('/agenda/'.$item->id)->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->get('/agenda')->assertOk()->assertDontSeeText('Compra reservada');
        $this->delete('/agenda/'.$item->id)->assertNotFound();

        $this->actingAs($this->admin);
        $this->post('/transacoes/'.$transacao->id.'/efetivar', [
            'data_efetivacao' => today()->toDateString(), 'forma_pagamento' => 'PIX',
        ])->assertSessionHasNoErrors();
        $this->assertSame('CONCLUIDO', $item->fresh()->status);
    }

    public function test_backfill_imports_only_active_sources_with_pending_dates(): void
    {
        $this->manejo(today()->subDays(3)->toDateString(), today()->subDay()->toDateString());
        $ultimoManejo = $this->manejo(today()->toDateString(), today()->addDays(4)->toDateString());
        $gestacao = $this->gestacao();
        $transacao = $this->transacao(['descricao' => str_repeat('A', 255)]);
        $this->transacao(['data_vencimento' => null]);

        $migration = require database_path('migrations/2026_11_10_000003_create_compromissos_table.php');
        $migration->down();
        $migration->up();

        $this->assertSame(3, Compromisso::pendentes()->count());
        foreach ([$ultimoManejo, $gestacao, $transacao] as $origem) {
            $this->assertSame(1, Compromisso::whereMorphedTo('origem', $origem)->count());
        }
    }

    private function manejo(string $data, string $proxima): ManejoSanitario
    {
        return ManejoSanitario::create([
            'animal_id' => $this->animal->id, 'medicamento_id' => $this->medicamento->id,
            'tipo' => 'VACINA', 'dosagem' => 1, 'unidade' => 'ML', 'data_aplicacao' => $data, 'proxima_aplicacao' => $proxima,
        ]);
    }

    private function gestacao(): Gestacao
    {
        return Gestacao::create([
            'matriz_id' => $this->animal->id, 'tipo' => 'INSEMINACAO', 'data_cobertura' => today()->subDays(30)->toDateString(),
            'status' => 'CONFIRMADA', 'data_confirmacao' => today()->subDay()->toDateString(), 'data_prevista_parto' => today()->addDays(10)->toDateString(),
        ]);
    }

    private function transacao(array $dados = []): Transacao
    {
        $conta = ContaOrcamentaria::firstOrCreate(['codigo' => 'AGENDA'], [
            'nome' => 'Agenda', 'natureza' => 'DESPESA', 'valor_orcado' => 100,
            'periodo_inicio' => today()->startOfYear()->toDateString(), 'periodo_fim' => today()->endOfYear()->toDateString(), 'ativo' => true,
        ]);

        return Transacao::create([
            'conta_orcamentaria_id' => $conta->id, 'natureza' => 'DESPESA', 'tipo' => 'COMPRA', 'descricao' => 'Compra reservada',
            'pessoa_nome' => 'Fornecedor', 'status' => 'PENDENTE', 'data_transacao' => today()->toDateString(),
            'data_vencimento' => today()->addDays(2)->toDateString(), 'valor_total' => '10.00', 'moeda' => 'BRL',
            'chave_idempotencia' => (string) Str::uuid(), 'conteudo_hash' => str_repeat('a', 64), ...$dados,
        ]);
    }
}
