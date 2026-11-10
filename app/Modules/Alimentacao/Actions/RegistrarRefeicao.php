<?php

namespace App\Modules\Alimentacao\Actions;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\Refeicao;
use App\Support\Lancamentos\SelecionarAnimais;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegistrarRefeicao
{
    public function __construct(private AlimentacaoTransaction $transaction, private SelecionarAnimais $selecao) {}

    /** @return Collection<int, Refeicao> */
    public function handle(array $dados): Collection
    {
        Gate::authorize('registrar-manejo');

        return $this->transaction->run(function () use ($dados): Collection {
            $chave = $dados['chave_lancamento'] ?? null;
            $hash = hash('sha256', json_encode(Arr::except($dados, ['chave_lancamento', 'brinco']), JSON_THROW_ON_ERROR));
            if ($chave !== null) {
                $existentes = Refeicao::where('chave_lancamento', $chave)->get();
                if ($existentes->isNotEmpty()) {
                    if ($existentes->contains(fn (Refeicao $refeicao): bool => $refeicao->hash_lancamento !== $hash)) {
                        throw ValidationException::withMessages(['chave_lancamento' => 'Este formulário já foi enviado com outros dados. Abra um novo lançamento.']);
                    }

                    return $existentes;
                }
            }
            $animais = $this->selecao->paraLancamento($dados, 'data_fornecimento');
            $data = Carbon::parse($dados['data_fornecimento']);
            $duplicada = Refeicao::with('animal')->whereIn('animal_id', $animais->modelKeys())->where('data_fornecimento', $data)->first();
            if ($duplicada) {
                throw ValidationException::withMessages(['data_fornecimento' => 'Já existe uma refeição para o animal '.$duplicada->animal->brinco.' neste horário. Revise antes de lançar o lote.']);
            }

            $itens = $dados['itens'];
            $alimentos = Alimento::disponiveis()->whereIn('id', array_column($itens, 'alimento_id'))->get()->keyBy('id');
            foreach ($itens as $index => $item) {
                if (! $alimentos->has($item['alimento_id'])) {
                    throw ValidationException::withMessages(["itens.{$index}.alimento_id" => 'Selecione um alimento e tipo ativos desta conta.']);
                }
            }

            $registros = new Collection;
            foreach ($animais as $animal) {
                $refeicao = $animal->refeicoes()->create([
                    'data_fornecimento' => $data, 'turno' => $dados['turno'], 'observacao' => $dados['observacao'] ?? null,
                    'chave_lancamento' => $chave, 'hash_lancamento' => $chave ? $hash : null,
                ]);
                foreach ($itens as $item) {
                    $refeicao->itens()->create([
                        'alimento_id' => $item['alimento_id'], 'quantidade' => $item['quantidade'],
                        'unidade' => $alimentos[$item['alimento_id']]->unidade,
                    ]);
                }
                $registros->push($refeicao);
            }

            return $registros;
        });
    }
}
