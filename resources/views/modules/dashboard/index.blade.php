<x-layout title="Visão geral">
    <x-page-heading title="O campo de hoje" :eyebrow="now()->translatedFormat('d / m / Y')"><span class="rounded-full bg-white px-3 py-2 text-xs text-stone-500">{{ auth()->user()->name }}</span></x-page-heading>
    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3" aria-label="Indicadores">
        <a href="{{ route('animais.index') }}" class="rounded-2xl bg-emerald-900 p-5 text-white"><x-icon name="herd" class="mb-5 size-6 opacity-70"/><p class="text-4xl font-bold">{{ number_format($total, 0, ',', '.') }}</p><p class="mt-1 text-sm text-emerald-100">Animais ativos</p></a>
        <a href="#agenda" class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><x-icon name="calendar" class="mb-5 size-6 text-amber-700"/><p class="text-4xl font-bold text-amber-900">{{ $compromissosTotal }}</p><p class="mt-1 text-sm text-amber-900">Pendências até 7 dias</p></a>
        <a href="{{ route('lotes.index') }}" class="card col-span-2 flex items-center justify-between sm:col-span-1 sm:block"><p class="text-sm text-stone-500">Lotes de manejo</p><p class="text-3xl font-bold sm:mt-7">{{ $lotes }}</p></a>
    </section>
    <section aria-label="Ações rápidas" class="grid grid-cols-2 gap-3"><a href="{{ route('pesagens.index') }}" class="btn"><x-icon name="scale"/>Lançar peso</a><a href="{{ route('sanidade.index') }}" class="btn-secondary"><x-icon name="health"/>Aplicação</a></section>
    <section id="agenda" class="card scroll-mt-24 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="section-title">Agenda geral</h2>
                <p class="mt-1 text-xs text-stone-500">Compromissos atrasados e dos próximos 7 dias</p>
            </div>
            <a href="{{ route('agenda.index') }}" class="btn-secondary">Ver agenda completa</a>
        </div>
        <div class="divide-y divide-stone-100">
            @forelse($compromissos as $compromisso)
                <x-compromisso-agenda :compromisso="$compromisso" retorno="dashboard" />
            @empty
                <p class="empty">Nenhum compromisso pendente nesse período.</p>
            @endforelse
        </div>
        @if($compromissosTotal > 8)<p class="text-xs text-stone-500">Exibindo os 8 primeiros de {{ $compromissosTotal }} compromissos.</p>@endif
    </section>
    <div class="grid gap-5 md:grid-cols-2">
        <section class="card"><h2 class="section-title">Rebanho por categoria</h2><p class="mb-5 text-xs text-stone-500">Distribuição dos animais ativos</p>
            <div class="space-y-4">@forelse($categorias as $categoria)<div><div class="mb-1.5 flex justify-between text-sm"><span>{{ ucfirst(strtolower($categoria->categoria)) }}</span><strong>{{ $categoria->total }}</strong></div><div class="h-2 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full bg-emerald-700" style="width: {{ $total ? round($categoria->total / $total * 100) : 0 }}%"></div></div></div>@empty<p class="empty">O rebanho começa com o primeiro cadastro.</p>@endforelse</div>
        </section>
        <section class="card"><div class="mb-3 flex items-center justify-between gap-2"><h2 class="section-title">Últimas pesagens</h2><a class="text-sm font-semibold text-emerald-800" href="{{ route('pesagens.index') }}">Ver todas →</a></div>
            @forelse($pesagens as $pesagem)<a href="{{ route('animais.show', $pesagem->animal_id) }}" class="flex min-h-17 items-center justify-between gap-2 border-b border-stone-100 py-3 last:border-0"><div><p class="break-words text-sm font-semibold">Brinco {{ $pesagem->animal->brinco }}</p><p class="text-xs text-stone-500">{{ $pesagem->data_pesagem->format('d/m/Y · H:i') }}</p></div><p class="font-bold">{{ number_format((float) $pesagem->peso, 2, ',', '.') }} <span class="text-xs font-normal text-stone-500">kg</span></p></a>@empty<p class="empty">As pesagens aparecerão aqui após o primeiro lançamento.</p>@endforelse
        </section>
    </div>
</x-layout>
