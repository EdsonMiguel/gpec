<x-layout title="Rebanho">
    <x-page-heading title="Meu rebanho" eyebrow="ANIMAIS & LOTES">@can('administrar')<a class="btn" href="{{ route('animais.create') }}">+ Novo animal</a>@endcan</x-page-heading>
    <x-rebanho-nav />
    @if($lote)
        <div class="card flex flex-wrap items-center justify-between gap-3">
            <p class="min-w-0 break-words text-sm font-semibold">Lote {{ $lote->nome }}</p>
            <a href="{{ route('animais.index') }}" class="btn-secondary">Ver todo o rebanho</a>
        </div>
    @endif
    <form action="{{ route('animais.index') }}" method="GET" class="flex items-end gap-2">
        @if($lote)<input type="hidden" name="lote_id" value="{{ $lote->id }}">@endif
        <div class="min-w-0 flex-1"><x-field name="busca" label="Buscar por brinco" :value="$search" placeholder="Digite o número do brinco" maxlength="50" type="search" /></div>
        <button class="btn" type="submit">Buscar</button>
    </form>
    <p class="text-sm text-stone-500">{{ $animais->total() }} animais encontrados</p>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @forelse($animais as $animal)
            <a class="card flex items-center gap-4 hover:border-emerald-500" href="{{ route('animais.show', $animal) }}">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800"><x-icon name="herd" /></span>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate font-bold">{{ $animal->brinco }} <span class="text-sm font-normal text-stone-500">{{ $animal->nome_ou_numero }}</span></h2>
                    <p class="mt-1 text-xs text-stone-500">{{ $animal->categoria }} · {{ $animal->lote?->nome ?? 'Sem lote' }}</p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <span class="badge">{{ $animal->status }}</span>
                        @if($animal->casos_clinicos_ativos_count)<span class="badge bg-amber-50 text-amber-900">Caso clínico ativo</span>@endif
                    </div>
                </div>
                <span aria-hidden="true" class="text-stone-400">→</span>
            </a>
        @empty<div class="card sm:col-span-2"><p class="empty">Nenhum animal encontrado. Ajuste a busca ou cadastre seu primeiro animal.</p></div>@endforelse
    </div>
    {{ $animais->links() }}
</x-layout>
