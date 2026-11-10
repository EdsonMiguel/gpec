<x-layout title="Nova transação">
    <x-page-heading title="Nova transação" eyebrow="FINANCEIRO"><a href="{{ route('transacoes.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    @if($contas->isEmpty())<div class="card"><p class="text-sm">Cadastre uma conta de orçamento ativa para registrar compras ou vendas.</p><a href="{{ route('contas-orcamentarias.create') }}" class="btn mt-4">Cadastrar conta</a></div>@else
    <form method="POST" action="{{ route('transacoes.store') }}" class="card space-y-5" data-fin-form>@csrf
        <input type="hidden" name="chave_idempotencia" value="{{ old('chave_idempotencia', \Illuminate\Support\Str::uuid()->toString()) }}">
        <div class="grid gap-5 sm:grid-cols-2"><x-select name="tipo" label="Operação *" required><option value="COMPRA" @selected(old('tipo', 'COMPRA') === 'COMPRA')>Compra / despesa</option><option value="VENDA" @selected(old('tipo') === 'VENDA')>Venda / receita</option></x-select>
            <x-select name="conta_orcamentaria_id" label="Conta de orçamento *" required><option value="">Selecione</option>@foreach($contas as $conta)<option value="{{ $conta->id }}" data-natureza="{{ $conta->natureza }}" @selected((string) old('conta_orcamentaria_id') === (string) $conta->id)>{{ $conta->nome }} · {{ $conta->natureza }} ({{ $conta->periodo_inicio->format('d/m/Y') }} a {{ $conta->periodo_fim->format('d/m/Y') }})</option>@endforeach</x-select>
            <x-field name="descricao" label="Descrição da transação *" required maxlength="255"/><x-field name="pessoa_nome" label="Fornecedor / cliente *" required maxlength="150"/>
            <x-field name="documento" label="Documento / nota / referência" maxlength="100"/><x-field name="data_transacao" label="Data da transação *" type="date" :value="today()->toDateString()" :max="today()->toDateString()" required/>
            <x-field name="data_vencimento" label="Vencimento (opcional)" type="date"/>
            <x-select name="status" label="Situação *" required><option value="PENDENTE" @selected(old('status', 'PENDENTE') === 'PENDENTE')>Pendente de pagamento / recebimento</option><option value="EFETIVADA" @selected(old('status') === 'EFETIVADA')>Já paga / recebida</option></x-select>
        </div>
        <div class="grid gap-5 sm:grid-cols-2" data-fin-payment><x-field name="data_efetivacao" label="Data do pagamento / recebimento *" type="date" :max="today()->toDateString()"/><x-select name="forma_pagamento" label="Forma de pagamento *"><option value="">Selecione</option>@foreach(\App\Modules\Financeiro\Models\Transacao::FORMAS as $value => $label)<option value="{{ $value }}" @selected(old('forma_pagamento') === $value)>{{ $label }}</option>@endforeach</x-select></div>
        <h2 class="section-title">Itens da transação</h2><p class="text-xs text-stone-500">Informe as quantidades e os preços negociados. O total é calculado ao salvar, com duas casas decimais.</p>
        @php($rows = is_array(old('itens')) ? array_slice(old('itens'), 0, 30) : [[]])
        <div class="space-y-4" data-fin-items>@forelse($rows as $item)<x-transacao-item :index="$loop->index" :item="$item" :alimentos="$alimentos" :medicamentos="$medicamentos" :animais="$animais" :produtos="$produtos"/>@empty<x-transacao-item :index="0" :alimentos="$alimentos" :medicamentos="$medicamentos" :animais="$animais" :produtos="$produtos"/>@endforelse</div>
        <button type="button" class="btn-secondary" data-add-fin-item>+ Adicionar item</button><p class="text-xs text-stone-500" data-fin-status aria-live="polite">Até 30 itens por transação.</p>
        <template data-fin-template><x-transacao-item index="__INDEX__" :alimentos="$alimentos" :medicamentos="$medicamentos" :animais="$animais" :produtos="$produtos"/></template>
        <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs text-emerald-800">Total dos itens</p><output data-fin-total class="mt-1 block break-words font-bold text-emerald-900" aria-live="polite">Preencha as quantidades e os preços.</output></div>
        <x-textarea name="observacao" label="Observações" maxlength="5000"/><button type="submit" class="btn w-full sm:w-auto">Salvar transação</button>
    </form>@endif
</x-layout>
