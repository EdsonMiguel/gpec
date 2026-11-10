<x-layout title="Lotes">
    <x-page-heading title="Lotes de manejo" eyebrow="REBANHO">
        @can('administrar')<a href="{{ route('lotes.create') }}" class="btn">+ Novo lote</a>@endcan
    </x-page-heading>
    <x-rebanho-nav />
    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @forelse($lotes as $lote)
            <article class="card min-w-0 space-y-3">
                <h2 class="break-words font-bold">{{ $lote->nome }}</h2>
                @if($lote->descricao)<p class="whitespace-pre-line break-words text-sm text-stone-600">{{ $lote->descricao }}</p>@endif
                <p class="text-sm font-semibold text-emerald-800">{{ $lote->animais_count }} {{ $lote->animais_count === 1 ? 'animal' : 'animais' }}</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('animais.index', ['lote_id' => $lote->id]) }}" class="btn-secondary">Ver animais</a>
                    @can('administrar')<a href="{{ route('lotes.edit', $lote) }}" class="btn-secondary">Editar lote</a>@endcan
                    @can('registrar-manejo')
                        <a href="{{ route('refeicoes.create', ['lote_id' => $lote->id]) }}" class="btn-secondary">Registrar refeição</a>
                        <a href="{{ route('sanidade.index', ['lote_id' => $lote->id]) }}" class="btn-secondary">Registrar aplicação</a>
                    @endcan
                </div>
            </article>
        @empty
            <p class="empty">Nenhum lote cadastrado.</p>
        @endforelse
    </section>
    {{ $lotes->links() }}
</x-layout>
