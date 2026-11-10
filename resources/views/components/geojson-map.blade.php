@props(['value' => null, 'context' => null, 'readonly' => false, 'label' => 'Limites no mapa'])
@php
    $geometry = $readonly ? $value : old('geojson', $value);
    $serialized = is_string($geometry) ? $geometry : ($geometry === null ? '' : json_encode($geometry));
@endphp
<section class="min-w-0 space-y-3" data-geojson-map data-readonly="{{ $readonly ? 'true' : 'false' }}" data-context="{{ $context ? json_encode($context) : '' }}">
    <h2 class="section-title">{{ $label }}</h2>
    @unless($readonly)<p class="text-sm text-stone-500">Desenhe o contorno com a ferramenta de polígono ou retângulo. Para finalizar um polígono, toque novamente no primeiro ponto. Use o lápis para editar os vértices e salve o cadastro para gravar a marcação.</p>@endunless
    @if($context)<p class="text-xs text-amber-800">O contorno da fazenda aparece em amarelo tracejado como referência. O contorno desta área é desenhado em verde.</p>@endif
    <input type="hidden" @unless($readonly) name="geojson" @endunless value="{{ $serialized }}" data-geojson-value>
    <div class="flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" data-map-locate>Minha localização</button>
        <button type="button" class="btn-secondary" data-map-fit>Ver contorno</button>
        @unless($readonly)<button type="button" class="btn-secondary" data-map-clear>Limpar marcação</button>@endunless
    </div>
    <div class="geojson-canvas" data-map-canvas role="region" aria-label="Mapa de satélite para {{ strtolower($label) }}"></div>
    <p class="text-xs text-stone-500" role="status" aria-live="polite" data-map-status>Carregando mapa…</p>
    @unless($readonly)<p class="text-xs text-stone-500">A marcação é opcional. As medidas em hectares do cadastro continuam sendo informadas separadamente.</p>@endunless
    @error('geojson')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
    <noscript><p class="text-sm text-amber-900">Ative o JavaScript para visualizar e marcar os limites no mapa.</p></noscript>
</section>
