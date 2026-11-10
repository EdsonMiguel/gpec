<x-layout title="Sanidade">
    <x-page-heading title="Manejo sanitário" eyebrow="CUIDADO DO REBANHO">
        <a href="{{ route('casos-clinicos.create', $selected ? ['animal_id' => $selected->id] : []) }}" class="btn">Registrar sintomas</a>
    </x-page-heading>
    <x-sanidade-nav />
    @if($aplicacao)
        <div class="card flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-semibold">Aplicação de origem do compromisso · {{ $aplicacao->data_aplicacao->format('d/m/Y') }}</p>
            <a href="{{ route('sanidade.index') }}" class="btn-secondary">Ver todas as aplicações</a>
        </div>
    @endif
    @if($medicamentos->isEmpty())<div class="card"><p class="text-sm text-stone-500">Cadastre um medicamento ativo para registrar aplicações. Solicite o cadastro ao administrador da fazenda.</p></div>@else
        <form method="POST" action="{{ route('sanidade.store') }}" class="card space-y-5">@csrf<h2 class="section-title">Nova aplicação realizada</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-destino-lancamento :selected="$selected" :lotes="$lotes" :lote-id="$loteId" />
                <x-select name="medicamento_id" label="Medicamento e unidade" required><option value="">Selecione</option>@foreach($medicamentos as $medicamento)<option value="{{ $medicamento->id }}" @selected((string) old('medicamento_id') === (string) $medicamento->id)>{{ $medicamento->nome }} ({{ $medicamento->unidade }})</option>@endforeach</x-select>
                <x-select name="tipo" label="Tipo de manejo" required>@foreach(['VACINA' => 'Vacina', 'VERMIFUGO' => 'Vermífugo', 'TRATAMENTO' => 'Tratamento', 'OUTRO' => 'Outro'] as $value => $label)<option value="{{ $value }}" @selected(old('tipo') === $value)>{{ $label }}</option>@endforeach</x-select>
                <x-field name="dosagem" label="Quantidade aplicada por animal" type="number" min="0.001" max="9999999.999" step="0.001" inputmode="decimal" required hint="Informe a quantidade para cada animal, na unidade exibida no medicamento."/>
                <x-field name="data_aplicacao" label="Data da aplicação" type="date" :value="today()->format('Y-m-d')" :max="today()->format('Y-m-d')" required/>
                <x-field name="proxima_aplicacao" label="Próxima aplicação (opcional)" type="date"/>
            </div><p class="text-xs text-stone-500">A próxima aplicação é uma previsão. Registre cada aplicação realizada para manter o histórico atualizado.</p><button class="btn w-full sm:w-auto" type="submit">Salvar aplicação</button>
        </form>
    @endif
    <section class="card"><h2 class="section-title mb-3">Histórico de aplicações</h2>@forelse($manejos as $manejo)<article class="border-b border-stone-100 py-4"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-semibold">Brinco {{ $manejo->animal->brinco }} · {{ $manejo->medicamento->nome }}</p><span class="badge">{{ $manejo->tipo }}</span></div><p class="mt-2 text-xs text-stone-500">{{ $manejo->data_aplicacao->format('d/m/Y') }} · {{ $manejo->dosagem }} {{ $manejo->unidade }}</p>@if($manejo->proxima_aplicacao)<p class="mt-2 text-xs text-emerald-800">Previsão registrada: {{ $manejo->proxima_aplicacao->format('d/m/Y') }}</p>@endif</article>@empty<p class="empty">Nenhuma aplicação registrada.</p>@endforelse<div class="mt-4">{{ $manejos->links() }}</div></section>
</x-layout>
