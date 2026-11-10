<x-layout :title="$fazenda->nome">
    <x-page-heading title="Ficha da fazenda" eyebrow="PROPRIEDADES"><a href="{{ route('fazendas.index') }}" class="btn-secondary">Todas as fazendas</a></x-page-heading>
    <section class="card space-y-5">
        <div class="flex items-start justify-between gap-3"><h2 class="min-w-0 break-words text-xl font-bold">{{ $fazenda->nome }}</h2><span class="badge shrink-0">{{ $fazenda->ativo ? 'Ativa' : 'Inativa' }}</span></div>
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div class="min-w-0"><dt class="text-xs text-stone-500">Código interno</dt><dd class="mt-1 break-words font-semibold">{{ $fazenda->codigo ?: 'Não informado' }}</dd></div>
            <div class="min-w-0"><dt class="text-xs text-stone-500">Localização</dt><dd class="mt-1 break-words font-semibold">{{ collect([$fazenda->municipio, $fazenda->uf])->filter()->join(' / ') ?: 'Não informada' }}</dd></div>
            <div><dt class="text-xs text-stone-500">Área total</dt><dd class="mt-1 font-semibold">{{ $fazenda->area_total_ha !== null ? number_format((float) $fazenda->area_total_ha, 2, ',', '.').' ha' : 'Não informada' }}</dd></div>
            <div><dt class="text-xs text-stone-500">Áreas cadastradas</dt><dd class="mt-1 font-semibold">{{ $fazenda->areas_count }}</dd></div>
        </dl>
        @if($fazenda->observacao)<p class="whitespace-pre-line break-words text-sm text-stone-600">{{ $fazenda->observacao }}</p>@endif
        @can('administrar')<a href="{{ route('fazendas.edit', $fazenda) }}" class="btn-secondary">Editar fazenda</a>@endcan
    </section>
    @if($fazenda->geojson)<div class="card"><x-geojson-map :value="$fazenda->geojson" :readonly="true" label="Limites da fazenda"/></div>@endif
    <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="section-title">Áreas desta fazenda</h2>@can('administrar') @if($fazenda->ativo)<a href="{{ route('fazendas.areas.create', $fazenda) }}" class="btn">+ Nova área</a>@endif @endcan</div>
    @unless($fazenda->ativo)<p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Fazenda inativa. Os cadastros existentes continuam disponíveis. Reative a fazenda para adicionar novas áreas.</p>@endunless
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse($areas as $area)
            <article class="card min-w-0 space-y-3"><div class="flex items-start justify-between gap-3"><h3 class="min-w-0 break-words font-bold">{{ $area->nome }}</h3><span class="badge shrink-0">{{ $area->ativo ? 'Ativa' : 'Inativa' }}</span></div><p class="text-sm text-emerald-800">{{ \App\Modules\Fazendas\Models\Area::TIPOS[$area->tipo] }} · {{ $area->area_ha !== null ? number_format((float) $area->area_ha, 2, ',', '.').' ha' : 'Área não informada' }}</p>@if($area->codigo)<p class="break-words text-xs text-stone-500">Código: {{ $area->codigo }}</p>@endif @if($area->descricao)<p class="whitespace-pre-line break-words text-sm text-stone-600">{{ $area->descricao }}</p>@endif @can('administrar')<a href="{{ route('fazendas.areas.edit', [$fazenda, $area]) }}" class="btn-secondary">Editar área</a>@endcan</article>
        @empty<div class="card sm:col-span-2"><p class="empty">Esta fazenda ainda não tem áreas cadastradas.</p></div>@endforelse
    </div>
    {{ $areas->links() }}
</x-layout>
