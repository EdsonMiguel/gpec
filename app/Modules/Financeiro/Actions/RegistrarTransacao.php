<?php

namespace App\Modules\Financeiro\Actions;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Financeiro\Models\ContaOrcamentaria;
use App\Modules\Financeiro\Models\Produto;
use App\Modules\Financeiro\Models\Transacao;
use App\Modules\Financeiro\Support\Money;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Validation\ValidationException;

class RegistrarTransacao
{
    public function __construct(private FinanceiroTransaction $transaction) {}

    public function handle(array $data): Transacao
    {
        return $this->transaction->run(function () use ($data): Transacao {
            $payload = $this->normalize($data);
            $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            $existing = Transacao::where('chave_idempotencia', $data['chave_idempotencia'])->first();
            if ($existing) {
                if (! hash_equals($existing->conteudo_hash, $hash)) {
                    throw ValidationException::withMessages(['chave_idempotencia' => 'Este lançamento já foi enviado com outros dados. Abra um novo lançamento.']);
                }

                return $existing;
            }

            $conta = ContaOrcamentaria::findOrFail($payload['conta_orcamentaria_id']);
            $natureza = $payload['tipo'] === 'COMPRA' ? 'DESPESA' : 'RECEITA';
            if (! $conta->ativo || $conta->natureza !== $natureza) {
                throw ValidationException::withMessages(['conta_orcamentaria_id' => 'Selecione uma conta ativa de despesa para compras ou de receita para vendas.']);
            }
            if ($payload['data_transacao'] < $conta->periodo_inicio->toDateString() || $payload['data_transacao'] > $conta->periodo_fim->toDateString()) {
                throw ValidationException::withMessages(['data_transacao' => 'A transação deve estar dentro da vigência da conta de orçamento.']);
            }
            $items = [];
            $total = '0.00';
            $animals = [];
            foreach ($payload['itens'] as $index => $item) {
                $resolved = $this->resolveItem($item, $index);
                if ($resolved['animal_id'] !== null && in_array($resolved['animal_id'], $animals, true)) {
                    throw ValidationException::withMessages(["itens.{$index}.animal_id" => 'Não repita o mesmo animal nesta transação.']);
                }
                $animals[] = $resolved['animal_id'];
                $items[] = $resolved;
                $total = bcadd($total, $resolved['valor_total'], 2);
            }
            if (bccomp($total, '0', 2) <= 0 || bccomp($total, '1000000000000', 2) >= 0) {
                throw ValidationException::withMessages(['itens' => 'O total deve ser positivo e menor que R$ 1 trilhão.']);
            }
            unset($payload['itens']);
            $record = Transacao::create([
                ...$payload, 'natureza' => $natureza, 'valor_total' => $total, 'moeda' => 'BRL',
                'chave_idempotencia' => $data['chave_idempotencia'], 'conteudo_hash' => $hash,
            ]);
            foreach ($items as $item) {
                $record->itens()->create($item);
            }

            return $record;
        });
    }

    private function normalize(array $data): array
    {
        $payload = ['conta_orcamentaria_id' => (int) $data['conta_orcamentaria_id']];
        foreach (['tipo', 'descricao', 'pessoa_nome', 'documento', 'data_transacao', 'data_vencimento', 'status', 'data_efetivacao', 'forma_pagamento', 'observacao'] as $field) {
            $payload[$field] = $data[$field] ?? null;
        }
        $payload['itens'] = [];
        foreach ($data['itens'] as $item) {
            $normalized = ['tipo_item' => $item['tipo_item']];
            foreach (['alimento_id', 'medicamento_id', 'animal_id', 'produto_id'] as $field) {
                $normalized[$field] = empty($item[$field]) ? null : (int) $item[$field];
            }
            $normalized['descricao'] = $item['descricao'] ?? null;
            $normalized['unidade'] = $item['unidade'] ?? null;
            $normalized['quantidade'] = bcadd((string) $item['quantidade'], '0', 3);
            $normalized['valor_unitario'] = bcadd((string) $item['valor_unitario'], '0', 2);
            $payload['itens'][] = $normalized;
        }

        return $payload;
    }

    private function resolveItem(array $item, int $index): array
    {
        $field = match ($item['tipo_item']) {
            'ALIMENTO' => 'alimento_id', 'MEDICAMENTO' => 'medicamento_id',
            'ANIMAL' => 'animal_id', 'PRODUTO' => 'produto_id', default => null,
        };
        foreach (['alimento_id', 'medicamento_id', 'animal_id', 'produto_id'] as $candidate) {
            if (($candidate === $field && $item[$candidate] === null) || ($candidate !== $field && $item[$candidate] !== null)) {
                throw ValidationException::withMessages(["itens.{$index}.{$candidate}" => 'Informe somente o cadastro correspondente ao tipo deste item.']);
            }
        }
        $source = match ($item['tipo_item']) {
            'ALIMENTO' => Alimento::disponiveis()->find($item['alimento_id']),
            'MEDICAMENTO' => Medicamento::where('ativo', true)->find($item['medicamento_id']),
            'ANIMAL' => Animal::withTrashed()->find($item['animal_id']),
            'PRODUTO' => Produto::where('ativo', true)->find($item['produto_id']),
            default => null,
        };
        if ($field !== null && $source === null) {
            throw ValidationException::withMessages(["itens.{$index}.{$field}" => 'Cadastro indisponível nesta conta. Selecione um registro válido.']);
        }
        if ($item['tipo_item'] === 'ANIMAL' && bccomp($item['quantidade'], '1', 3) !== 0) {
            throw ValidationException::withMessages(["itens.{$index}.quantidade" => 'Para animais, use um item por animal e quantidade 1.']);
        }
        if ($source instanceof Animal) {
            $item['descricao'] = $item['descricao'] ?: 'Brinco '.$source->brinco.($source->nome_ou_numero ? ' · '.$source->nome_ou_numero : '');
            $item['unidade'] = 'UN';
        } elseif ($source !== null) {
            $item['descricao'] = $item['descricao'] ?: $source->nome;
            $item['unidade'] = $source->unidade;
        } else {
            if (! $item['descricao']) {
                throw ValidationException::withMessages(["itens.{$index}.descricao" => 'Descreva o produto ou serviço deste item.']);
            }
            $item['unidade'] = $item['unidade'] ?: 'UN';
        }
        $item['valor_total'] = Money::itemTotal($item['quantidade'], $item['valor_unitario']);
        if (bccomp($item['valor_total'], '0', 2) <= 0 || bccomp($item['valor_total'], '1000000000000', 2) >= 0) {
            throw ValidationException::withMessages(["itens.{$index}.valor_unitario" => 'O total do item deve ser de pelo menos R$ 0,01 e menor que R$ 1 trilhão.']);
        }

        return $item;
    }
}
