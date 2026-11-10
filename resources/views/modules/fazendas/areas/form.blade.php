<x-layout :title="$area->exists ? 'Editar área' : 'Nova área'">
    <x-page-heading :title="$area->exists ? 'Editar área' : 'Nova área'" eyebrow="ÁREAS DA FAZENDA"><a href="{{ route('fazendas.show', $fazenda) }}" class="btn-secondary">Voltar à fazenda</a></x-page-heading>
    <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900"><span class="font-semibold">Fazenda:</span> {{ $fazenda->nome }}</div>
    <form method="POST" action="{{ $area->exists ? route('fazendas.areas.update', [$fazenda, $area]) : route('fazendas.areas.store', $fazenda) }}" class="card space-y-5">
        @csrf @if($area->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="nome" label="Nome da área *" :value="$area->nome" required maxlength="100" autofocus/>
            <x-field name="codigo" label="Código interno" :value="$area->codigo" maxlength="50"/>
            <x-select name="tipo" label="Tipo *" required><option value="">Selecione</option>@foreach(\App\Modules\Fazendas\Models\Area::TIPOS as $value => $label)<option value="{{ $value }}" @selected(old('tipo', $area->tipo) === $value)>{{ $label }}</option>@endforeach</x-select>
            <x-field name="area_ha" label="Área (ha)" type="number" :value="$area->area_ha" min="0" max="9999999999.99" step="0.01" inputmode="decimal"/>
            <x-select name="ativo" label="Situação *" required><option value="1" @selected((string) old('ativo', (int) $area->ativo) === '1')>Ativa</option><option value="0" @selected((string) old('ativo', (int) $area->ativo) === '0')>Inativa</option></x-select>
        </div>
        <x-textarea name="descricao" label="Descrição" :value="$area->descricao" maxlength="5000"/>
        <x-geojson-map :value="$area->geojson" :context="$fazenda->geojson" label="Limites da área no mapa"/>
        <p class="text-xs text-stone-500">* Campos obrigatórios. Esta área ficará vinculada à fazenda indicada acima.</p>
        <button type="submit" class="btn w-full sm:w-auto">Salvar área</button>
    </form>
</x-layout>
