<x-layout title="Prontuário">
    <x-page-heading title="Casos clínicos" eyebrow="SANIDADE">
        @if(!$animal || ($animal->status === 'ATIVO' && !$animal->trashed()))
            <a href="{{ route('casos-clinicos.create', $animal ? ['animal_id' => $animal->id] : []) }}" class="btn">Registrar sintomas</a>
        @endif
    </x-page-heading>
    <x-sanidade-nav />

    <form method="GET" action="{{ route('casos-clinicos.index') }}" class="card space-y-4">
        @if($animal)
            <input type="hidden" name="animal_id" value="{{ $animal->id }}">
            <p class="text-sm">Prontuário de <a href="{{ route('animais.show', $animal) }}" class="font-semibold text-emerald-800 underline">{{ $animal->brinco }}</a></p>
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            @unless($animal)
                <x-field name="busca" label="Buscar por brinco" type="search" :value="$busca" maxlength="50" />
            @endunless
            <x-select name="situacao" label="Situação">
                <option value="ATIVOS" @selected($situacao === 'ATIVOS')>Ativos</option>
                <option value="ENCERRADOS" @selected($situacao === 'ENCERRADOS')>Encerrados</option>
                <option value="TODOS" @selected($situacao === 'TODOS')>Todos</option>
            </x-select>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-secondary">Filtrar</button>
            @if($animal || $busca !== '' || $situacao !== 'ATIVOS')
                <a href="{{ route('casos-clinicos.index') }}" class="btn-secondary">Limpar filtros</a>
            @endif
        </div>
    </form>

    <section class="card divide-y divide-stone-100">
        @forelse($casos as $caso)
            @include('sanidade::casos.resumo', ['exibirAnimal' => !$animal])
        @empty
            <p class="empty">Nenhum caso clínico encontrado.</p>
        @endforelse
    </section>
    {{ $casos->links() }}
</x-layout>
