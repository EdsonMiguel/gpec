<x-layout :title="$conta->exists ? 'Editar conta' : 'Nova conta de orçamento'">
    <x-page-heading :title="$conta->exists ? 'Editar conta' : 'Nova conta de orçamento'" eyebrow="FINANCEIRO"><a href="{{ route('contas-orcamentarias.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    <form method="POST" action="{{ $conta->exists ? route('contas-orcamentarias.update', $conta) : route('contas-orcamentarias.store') }}" class="card space-y-5">@csrf @if($conta->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2"><x-field name="codigo" label="Código *" :value="$conta->codigo" required maxlength="50"/><x-field name="nome" label="Nome *" :value="$conta->nome" required maxlength="150"/>
            <x-select name="natureza" label="Natureza *" required><option value="">Selecione</option>@foreach(['DESPESA' => 'Despesa (compras)', 'RECEITA' => 'Receita (vendas)'] as $value => $label)<option value="{{ $value }}" @selected(old('natureza', $conta->natureza) === $value)>{{ $label }}</option>@endforeach</x-select>
            <x-field name="valor_orcado" label="Valor orçado (R$) *" type="number" :value="$conta->valor_orcado" min="0" max="999999999999.99" step="0.01" inputmode="decimal" required/>
            <x-field name="periodo_inicio" label="Início da vigência *" type="date" :value="$conta->periodo_inicio?->toDateString()" required/><x-field name="periodo_fim" label="Fim da vigência *" type="date" :value="$conta->periodo_fim?->toDateString()" required/>
            <x-select name="ativo" label="Situação *" required><option value="1" @selected((string) old('ativo', (int) $conta->ativo) === '1')>Ativa</option><option value="0" @selected((string) old('ativo', (int) $conta->ativo) === '0')>Inativa</option></x-select>
        </div><x-textarea name="descricao" label="Descrição" :value="$conta->descricao" maxlength="5000"/><p class="text-xs text-stone-500">A conta classifica compras ou vendas dentro da vigência informada. Após o primeiro lançamento, natureza e vigência ficam preservadas; o valor orçado pode ser ajustado.</p><button type="submit" class="btn">Salvar conta</button>
    </form>
</x-layout>
