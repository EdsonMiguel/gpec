<?php

namespace Tests\Feature;

use App\Modules\Agenda\Models\Compromisso;
use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\Refeicao;
use App\Modules\Alimentacao\Models\TipoAlimento;
use App\Modules\Auth\Models\User;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LancamentoPorLoteTest extends TestCase
{
    use RefreshDatabase;

    private Lote $lote;

    private Alimento $alimento;

    private Medicamento $medicamento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'Vaqueiro']));
        $this->lote = Lote::create(['nome' => 'Lote de teste']);
        $tipo = TipoAlimento::create(['codigo' => 'TIPO', 'nome' => 'Tipo de teste', 'ativo' => true]);
        $this->alimento = Alimento::create([
            'codigo' => 'ALIM', 'nome' => 'Alimento de teste', 'unidade' => 'KG', 'ativo' => true, 'tipo_alimento_id' => $tipo->id,
        ]);
        $this->medicamento = Medicamento::create(['nome' => 'Medicamento de teste', 'unidade' => 'ML', 'ativo' => true]);
        $this->animal('ATIVO-1');
        $this->animal('ATIVO-2');
        $this->animal('INATIVO', ['status' => 'VENDIDO']);
        $this->animal('ARQUIVADO')->delete();
        $this->animal('FORA', ['lote_id' => null]);
    }

    public function test_meal_and_medication_create_individual_records_only_for_active_lot_members(): void
    {
        $this->get('/refeicoes/create?lote_id='.$this->lote->id)->assertOk()->assertSeeText('2 ativos');
        $this->get('/sanidade?lote_id='.$this->lote->id)->assertOk()->assertSeeText('2 ativos');
        $this->post('/refeicoes', $this->refeicao())->assertSessionHasNoErrors()->assertRedirect('/refeicoes');
        $this->assertSame(2, Refeicao::count());
        foreach (Refeicao::with(['animal', 'itens'])->get() as $refeicao) {
            $this->assertStringStartsWith('ATIVO-', $refeicao->animal->brinco);
            $this->assertSame('1.250', $refeicao->itens->first()->quantidade);
            $this->assertSame('KG', $refeicao->itens->first()->unidade);
            $this->get('/animais/'.$refeicao->animal_id)->assertOk()->assertSeeText('Alimento de teste');
        }
        $this->post('/sanidade', $this->aplicacao())->assertSessionHasNoErrors()->assertRedirect('/sanidade');
        $this->assertSame(2, ManejoSanitario::count());
        $this->assertSame(2, Compromisso::pendentes()->count());
        foreach (ManejoSanitario::with('animal')->get() as $manejo) {
            $this->assertStringStartsWith('ATIVO-', $manejo->animal->brinco);
            $this->assertSame('2.500', $manejo->dosagem);
            $this->assertSame('ML', $manejo->unidade);
        }
    }

    public function test_one_invalid_birth_date_prevents_all_records_and_reminders(): void
    {
        Animal::where('brinco', 'ATIVO-2')->firstOrFail()->update(['data_nascimento' => today()->toDateString()]);
        $this->post('/refeicoes', $this->refeicao(['data_fornecimento' => today()->subDay()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrors('data_fornecimento');
        $this->post('/sanidade', $this->aplicacao(['data_aplicacao' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors('data_aplicacao');
        $this->assertSame(0, Refeicao::count());
        $this->assertSame(0, ManejoSanitario::count());
        $this->assertSame(0, Compromisso::count());
    }

    public function test_existing_meal_for_one_member_prevents_a_partial_batch(): void
    {
        $dados = $this->refeicao();
        $animal = Animal::where('brinco', 'ATIVO-2')->firstOrFail();
        $this->post('/refeicoes', [
            ...$dados, 'destino' => 'ANIMAL', 'lote_id' => null, 'animal_id' => $animal->id,
        ])->assertSessionHasNoErrors();
        $this->post('/refeicoes', [...$dados, 'chave_lancamento' => (string) Str::uuid()])
            ->assertSessionHasErrors('data_fornecimento');
        $this->assertSame(1, Refeicao::count());
    }

    public function test_retries_preserve_the_original_batch_even_after_lot_membership_changes(): void
    {
        $refeicao = $this->refeicao();
        $aplicacao = $this->aplicacao();
        $this->post('/refeicoes', $refeicao)->assertSessionHasNoErrors();
        $this->post('/sanidade', $aplicacao)->assertSessionHasNoErrors();
        $this->animal('NOVO');
        $this->post('/refeicoes', $refeicao)->assertSessionHasNoErrors();
        $this->post('/sanidade', $aplicacao)->assertSessionHasNoErrors();
        $this->assertSame(2, Refeicao::count());
        $this->assertSame(2, ManejoSanitario::count());
        $this->assertSame(2, Compromisso::pendentes()->count());
        $this->post('/sanidade', [...$aplicacao, 'dosagem' => '3.000'])->assertSessionHasErrors('chave_lancamento');
        $this->post('/refeicoes', [...$refeicao, 'turno' => 'OUTRO'])->assertSessionHasErrors('chave_lancamento');
    }

    public function test_empty_foreign_and_conflicting_destinations_are_rejected(): void
    {
        $vazio = Lote::create(['nome' => 'Vazio']);
        $this->post('/refeicoes', $this->refeicao(['lote_id' => $vazio->id]))->assertSessionHasErrors('lote_id');
        $this->post('/sanidade', $this->aplicacao(['lote_id' => $vazio->id]))->assertSessionHasErrors('lote_id');
        $this->post('/sanidade', $this->aplicacao(['brinco' => 'ATIVO-1']))->assertSessionHasErrors('brinco');
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->post('/refeicoes', $this->refeicao())->assertSessionHasErrors('lote_id');
        $this->post('/sanidade', $this->aplicacao())->assertSessionHasErrors('lote_id');
        $this->get('/refeicoes/create?lote_id='.$this->lote->id)->assertNotFound();
        $this->get('/sanidade?lote_id='.$this->lote->id)->assertNotFound();
    }

    private function animal(string $brinco, array $dados = []): Animal
    {
        return Animal::create([
            'brinco' => $brinco, 'sexo' => 'FEMEA', 'categoria' => 'VACA', 'status' => 'ATIVO',
            'lote_id' => $this->lote->id, 'data_nascimento' => '2020-01-01', ...$dados,
        ]);
    }

    private function refeicao(array $dados = []): array
    {
        return [
            'destino' => 'LOTE', 'lote_id' => $this->lote->id, 'chave_lancamento' => (string) Str::uuid(),
            'data_fornecimento' => now()->subMinute()->format('Y-m-d\TH:i'), 'turno' => 'MANHA',
            'itens' => [['alimento_id' => $this->alimento->id, 'quantidade' => '1.250']], ...$dados,
        ];
    }

    private function aplicacao(array $dados = []): array
    {
        return [
            'destino' => 'LOTE', 'lote_id' => $this->lote->id, 'chave_lancamento' => (string) Str::uuid(),
            'medicamento_id' => $this->medicamento->id, 'tipo' => 'TRATAMENTO', 'dosagem' => '2.500',
            'data_aplicacao' => today()->toDateString(), 'proxima_aplicacao' => today()->addDays(7)->toDateString(), ...$dados,
        ];
    }
}
