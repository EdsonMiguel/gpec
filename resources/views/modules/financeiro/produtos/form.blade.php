<x-layout :title="$produto->exists ? 'Editar produto' : 'Novo produto'">
    <x-page-heading :title="$produto->exists ? 'Editar produto' : 'Novo produto'" eyebrow="FINANCEIRO"><a href="{{ route('produtos.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    <form method="POST" action="{{ $produto->exists ? route('produtos.update', $produto) : route('produtos.store') }}" class="card space-y-5">@csrf @if($produto->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2"><x-field name="codigo" label="Código *" :value="$produto->codigo" required maxlength="50"/><x-field name="nome" label="Nome *" :value="$produto->nome" required maxlength="150"/>
            <x-select name="unidade" label="Unidade *" required><option value="">Selecione</option>@foreach(\App\Modules\Financeiro\Models\Produto::UNIDADES as $unidade)<option value="{{ $unidade }}" @selected(old('unidade', $produto->unidade) === $unidade)>{{ $unidade }}</option>@endforeach</x-select>
            <x-select name="ativo" label="Situação *" required><option value="1" @selected((string) old('ativo', (int) $produto->ativo) === '1')>Ativo</option><option value="0" @selected((string) old('ativo', (int) $produto->ativo) === '0')>Inativo</option></x-select>
        </div><x-textarea name="descricao" label="Descrição" :value="$produto->descricao" maxlength="5000"/><button type="submit" class="btn">Salvar produto</button>
    </form>
</x-layout>
