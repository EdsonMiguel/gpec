<x-layout :title="$fazenda->exists ? 'Editar fazenda' : 'Nova fazenda'">
    <x-page-heading :title="$fazenda->exists ? 'Editar fazenda' : 'Nova fazenda'" eyebrow="PROPRIEDADES"><a href="{{ $fazenda->exists ? route('fazendas.show', $fazenda) : route('fazendas.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    <form method="POST" action="{{ $fazenda->exists ? route('fazendas.update', $fazenda) : route('fazendas.store') }}" class="card space-y-5">
        @csrf @if($fazenda->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="nome" label="Nome da fazenda *" :value="$fazenda->nome" required maxlength="150" autofocus/>
            <x-field name="codigo" label="Código interno" :value="$fazenda->codigo" maxlength="50"/>
            <x-field name="municipio" label="Município" :value="$fazenda->municipio" maxlength="150"/>
            <x-select name="uf" label="UF"><option value="">Não informada</option>@foreach(\App\Modules\Fazendas\Models\Fazenda::UFS as $uf)<option value="{{ $uf }}" @selected(old('uf', $fazenda->uf) === $uf)>{{ $uf }}</option>@endforeach</x-select>
            <x-field name="area_total_ha" label="Área total (ha)" type="number" :value="$fazenda->area_total_ha" min="0" max="9999999999.99" step="0.01" inputmode="decimal"/>
            <x-select name="ativo" label="Situação *" required><option value="1" @selected((string) old('ativo', (int) $fazenda->ativo) === '1')>Ativa</option><option value="0" @selected((string) old('ativo', (int) $fazenda->ativo) === '0')>Inativa</option></x-select>
        </div>
        <x-textarea name="observacao" label="Observações" :value="$fazenda->observacao" maxlength="5000"/>
        <x-geojson-map :value="$fazenda->geojson" label="Limites da fazenda no mapa"/>
        <p class="text-xs text-stone-500">* Campos obrigatórios. As áreas são cadastradas na ficha da fazenda após salvar.</p>
        <button type="submit" class="btn w-full sm:w-auto">Salvar fazenda</button>
    </form>
</x-layout>
