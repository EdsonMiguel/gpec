<?php

namespace App\Modules\Agenda\Actions;

use App\Modules\Agenda\Models\Compromisso;
use App\Modules\Financeiro\Models\Transacao;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Models\Gestacao;
use App\Modules\Sanidade\Models\ManejoSanitario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SincronizarAgenda
{
    public function saved(Animal|Gestacao|ManejoSanitario|Transacao $origem): void
    {
        match (true) {
            $origem instanceof ManejoSanitario => $this->aplicacao($origem),
            $origem instanceof Gestacao => $this->gestacao($origem),
            $origem instanceof Transacao => $this->transacao($origem),
            $origem->wasChanged('status') && $origem->status !== 'ATIVO' => $this->cancelarDoAnimal($origem),
            default => null,
        };
    }

    public function trashed(Animal $animal): void
    {
        $this->cancelarDoAnimal($animal);
    }

    private function aplicacao(ManejoSanitario $manejo): void
    {
        $historico = ManejoSanitario::where('animal_id', $manejo->animal_id)->where('medicamento_id', $manejo->medicamento_id);
        $ultimo = (clone $historico)->orderByDesc('data_aplicacao')->orderByDesc('id')->firstOrFail();
        if ($ultimo->id !== $manejo->id) {
            return;
        }

        Compromisso::pendentes()->where('origem_type', Compromisso::APLICACAO)
            ->whereIn('origem_id', $historico->whereKeyNot($manejo->id)->select('id'))
            ->update(['status' => 'CONCLUIDO']);

        $animal = $manejo->animal;
        if ($manejo->proxima_aplicacao && $animal->status === 'ATIVO' && ! $animal->trashed()) {
            $this->agendar($manejo, 'Brinco '.$animal->brinco.' · '.$manejo->medicamento->nome, $manejo->proxima_aplicacao->toDateString());
        } else {
            $this->finalizar($manejo, 'CANCELADO');
        }
    }

    private function gestacao(Gestacao $gestacao): void
    {
        if ($gestacao->status === 'CONFIRMADA' && $gestacao->data_prevista_parto
            && $gestacao->matriz->status === 'ATIVO' && ! $gestacao->matriz->trashed()) {
            $this->agendar($gestacao, 'Parto da matriz '.$gestacao->matriz->brinco, $gestacao->data_prevista_parto->toDateString());
        } else {
            $this->finalizar($gestacao, $gestacao->status === 'PARTO' ? 'CONCLUIDO' : 'CANCELADO');
        }
    }

    private function transacao(Transacao $transacao): void
    {
        if ($transacao->status === 'PENDENTE' && $transacao->data_vencimento) {
            $prefixo = $transacao->tipo === 'COMPRA' ? 'Pagamento · ' : 'Recebimento · ';
            $this->agendar($transacao, $prefixo.$transacao->descricao, $transacao->data_vencimento->toDateString());
        } else {
            $this->finalizar($transacao, $transacao->status === 'EFETIVADA' ? 'CONCLUIDO' : 'CANCELADO');
        }
    }

    private function cancelarDoAnimal(Animal $animal): void
    {
        Compromisso::pendentes()->where('origem_type', Compromisso::APLICACAO)
            ->whereIn('origem_id', $animal->manejos()->select('id'))->update(['status' => 'CANCELADO']);

        Compromisso::pendentes()->where('origem_type', Compromisso::PARTO)
            ->whereIn('origem_id', Gestacao::where('matriz_id', $animal->id)->select('id'))->update(['status' => 'CANCELADO']);
    }

    private function agendar(Model $origem, string $titulo, string $data): void
    {
        $compromisso = Compromisso::withTrashed()->lockForUpdate()->firstOrNew([
            'origem_type' => $origem->getMorphClass(),
            'origem_id' => $origem->getKey(),
        ]);
        if ($compromisso->exists && ($compromisso->trashed() || $compromisso->status !== 'PENDENTE')) {
            return;
        }

        $compromisso->fill(['titulo' => Str::substr($titulo, 0, 255), 'data_prevista' => $data, 'status' => 'PENDENTE'])->save();
    }

    private function finalizar(Model $origem, string $status): void
    {
        Compromisso::pendentes()->whereMorphedTo('origem', $origem)->update(['status' => $status]);
    }
}
