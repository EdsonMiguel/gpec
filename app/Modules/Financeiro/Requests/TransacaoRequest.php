<?php

namespace App\Modules\Financeiro\Requests;

use App\Modules\Financeiro\Models\ItemTransacao;
use App\Modules\Financeiro\Models\Transacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'chave_idempotencia' => ['required', 'string', 'regex:/^[a-zA-Z0-9-]{16,64}$/'],
            'conta_orcamentaria_id' => ['required', 'integer', Rule::exists('contas_orcamentarias', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'tipo' => ['required', Rule::in(['COMPRA', 'VENDA'])],
            'descricao' => ['required', 'string', 'max:255'],
            'pessoa_nome' => ['required', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:100'],
            'data_transacao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'data_vencimento' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data_transacao'],
            'status' => ['required', Rule::in(['PENDENTE', 'EFETIVADA'])],
            'data_efetivacao' => [Rule::requiredIf($this->input('status') === 'EFETIVADA'), Rule::prohibitedIf($this->input('status') !== 'EFETIVADA'), 'nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:data_transacao'],
            'forma_pagamento' => [Rule::requiredIf($this->input('status') === 'EFETIVADA'), 'nullable', Rule::in(array_keys(Transacao::FORMAS))],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'itens' => ['required', 'array', 'min:1', 'max:30'],
            'itens.*' => ['required', 'array:tipo_item,alimento_id,medicamento_id,animal_id,produto_id,descricao,unidade,quantidade,valor_unitario'],
            'itens.*.tipo_item' => ['required', Rule::in(array_keys(ItemTransacao::TIPOS))],
            'itens.*.descricao' => ['nullable', 'string', 'max:255'],
            'itens.*.unidade' => ['nullable', Rule::in(['UN', 'KG', 'G', 'L', 'ML', 'DOSE'])],
            'itens.*.quantidade' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'lt:1000000000'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'decimal:0,2', 'gte:0.01', 'lt:1000000000000'],
            'tenant_id' => ['prohibited'], 'valor_total' => ['prohibited'], 'natureza' => ['prohibited'],
        ];
        foreach (['alimento' => 'alimentos', 'medicamento' => 'medicamentos', 'animal' => 'animais', 'produto' => 'produtos'] as $name => $table) {
            $rules['itens.*.'.$name.'_id'] = ['nullable', 'integer', Rule::exists($table, 'id')->where('tenant_id', $this->user()->tenant_id)];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['itens' => 'itens da transação', 'itens.*.quantidade' => 'quantidade', 'itens.*.valor_unitario' => 'valor unitário'];
    }
}
