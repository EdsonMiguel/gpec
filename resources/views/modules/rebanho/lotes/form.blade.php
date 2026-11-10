<x-layout :title="$lote->exists ? 'Editar lote' : 'Novo lote'">
    <x-page-heading :title="$lote->exists ? 'Editar lote' : 'Novo lote'" eyebrow="REBANHO">
        <a href="{{ route('lotes.index') }}" class="btn-secondary">Voltar</a>
    </x-page-heading>
    <x-rebanho-nav />
    <form method="POST" action="{{ $lote->exists ? route('lotes.update', $lote) : route('lotes.store') }}" class="card space-y-5">
        @csrf
        @if($lote->exists) @method('PUT') @endif
        <x-field name="nome" label="Nome do lote *" :value="$lote->nome" required maxlength="100" autofocus />
        <x-textarea name="descricao" label="Descrição" :value="$lote->descricao" maxlength="2000" />
        <p class="text-sm text-stone-600">Selecione este lote no cadastro ou na edição dos animais que fazem parte dele.</p>
        <button type="submit" class="btn">{{ $lote->exists ? 'Salvar alterações' : 'Cadastrar lote' }}</button>
    </form>
</x-layout>
