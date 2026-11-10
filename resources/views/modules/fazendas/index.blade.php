<x-layout title="Fazendas e áreas">
    <x-page-heading title="Fazendas e áreas" eyebrow="PROPRIEDADES">@can('administrar')<a href="{{ route('fazendas.create') }}" class="btn">+ Nova fazenda</a>@endcan</x-page-heading>
    <p class="text-sm text-stone-500">Cadastre as propriedades e organize os pastos, piquetes e demais áreas de cada fazenda.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse($fazendas as $fazenda)
            <a href="{{ route('fazendas.show', $fazenda) }}" class="card min-w-0 space-y-4 hover:border-emerald-500">
                <div class="flex items-start justify-between gap-3"><h2 class="min-w-0 break-words text-lg font-bold">{{ $fazenda->nome }}</h2><span class="badge shrink-0">{{ $fazenda->ativo ? 'Ativa' : 'Inativa' }}</span></div>
                <p class="text-sm text-stone-500">{{ collect([$fazenda->municipio, $fazenda->uf])->filter()->join(' / ') ?: 'Localização não informada' }}</p>
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm"><span class="font-semibold text-emerald-800">{{ $fazenda->areas_count }} áreas</span><span>{{ $fazenda->area_total_ha !== null ? number_format((float) $fazenda->area_total_ha, 2, ',', '.').' ha' : 'Área total não informada' }}</span></div>
                <p class="text-sm font-semibold text-emerald-800">Consultar fazenda e áreas →</p>
            </a>
        @empty
            <div class="card sm:col-span-2"><p class="empty">Nenhuma fazenda cadastrada. @can('administrar')Use “Nova fazenda” para começar.@else Solicite o cadastro ao administrador.@endcan</p></div>
        @endforelse
    </div>
    {{ $fazendas->links() }}
</x-layout>
