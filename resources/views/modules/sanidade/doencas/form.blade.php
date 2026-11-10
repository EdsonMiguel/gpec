<x-layout :title="$doenca->exists ? 'Editar doença' : 'Nova doença'">
    <x-page-heading :title="$doenca->exists ? 'Editar doença' : 'Nova doença'" eyebrow="SANIDADE">
        <a href="{{ route('doencas.index') }}" class="btn-secondary">Voltar</a>
    </x-page-heading>
    <form method="POST" action="{{ $doenca->exists ? route('doencas.update', $doenca) : route('doencas.store') }}" class="card space-y-5">
        @csrf
        @if($doenca->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="nome" label="Nome da doença *" :value="$doenca->nome" required maxlength="150" />
            <x-select name="ativo" label="Situação *" required>
                <option value="1" @selected((string) old('ativo', (int) $doenca->ativo) === '1')>Ativa</option>
                <option value="0" @selected((string) old('ativo', (int) $doenca->ativo) === '0')>Inativa</option>
            </x-select>
        </div>
        <x-textarea name="descricao" label="Descrição" :value="$doenca->descricao" maxlength="5000" />
        <p class="text-sm text-stone-600">Doenças inativas ficam no histórico das avaliações e saem da lista de novos diagnósticos.</p>
        <button type="submit" class="btn">Salvar doença</button>
    </form>
</x-layout>
