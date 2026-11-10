<section class="card min-w-0 space-y-4">
    <h2 class="section-title">Histórico de peso</h2>

    @if($evolucaoPeso !== [])
        <div class="rounded-xl bg-emerald-50 p-4">
            <p class="text-xs font-semibold text-emerald-900">Ganho médio diário (GMD)</p>
            @if($ganhoMedioDiario !== null)
                <p @class(['mt-1 text-xl font-bold tabular-nums', 'text-red-800' => $ganhoMedioDiario < 0, 'text-emerald-950' => $ganhoMedioDiario >= 0])>
                    {{ number_format($ganhoMedioDiario, 2, ',', '.') }} <span class="text-xs font-semibold">kg/dia</span>
                </p>
                <p class="mt-1 text-xs text-stone-600">Variação entre a primeira e a última pesagem em {{ $diasAcompanhados }} {{ $diasAcompanhados === 1 ? 'dia' : 'dias' }}.</p>
            @else
                <p class="mt-1 text-sm text-stone-600">Registre pesagens em datas diferentes para calcular o GMD.</p>
            @endif
        </div>

        @if(count($evolucaoPeso) > 1)
            <figure class="space-y-2" data-weight-chart="{{ json_encode($evolucaoPeso, JSON_THROW_ON_ERROR) }}">
                <figcaption class="text-sm font-semibold">Evolução do peso <span class="font-normal text-stone-500">(kg)</span></figcaption>
                <div data-weight-plot tabindex="0" role="group" aria-label="Gráfico de peso. Use as setas para consultar as pesagens." class="h-44 w-full rounded-lg focus-visible:outline-2 focus-visible:outline-emerald-700"></div>
                <p data-weight-selection role="status" class="rounded-lg bg-stone-50 px-3 py-2 text-xs font-semibold tabular-nums text-stone-700"></p>
            </figure>
        @endif
    @endif

    <div class="divide-y divide-stone-100">
        @forelse($pesagens as $pesagem)
            <div class="flex justify-between gap-3 py-3 text-sm">
                <div class="min-w-0">
                    <p>{{ $pesagem->data_pesagem->format('d/m/Y H:i') }}</p>
                    <p class="mt-1 break-words text-xs text-emerald-800">{{ $pesagem->motivo->nome }}</p>
                    @if($pesagem->observacao)<p class="mt-1 break-words text-xs text-stone-500">{{ $pesagem->observacao }}</p>@endif
                </div>
                <strong class="shrink-0 tabular-nums">{{ number_format((float) $pesagem->peso, 2, ',', '.') }} kg</strong>
            </div>
        @empty
            <p class="empty">Nenhuma pesagem registrada.</p>
        @endforelse
    </div>
    {{ $pesagens->links() }}
</section>
