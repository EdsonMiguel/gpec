<x-layout title="Agenda">
    <x-page-heading title="Agenda geral" eyebrow="COMPROMISSOS">
        <a href="{{ route('dashboard') }}" class="btn-secondary">Visão geral</a>
    </x-page-heading>
    <p class="text-sm text-stone-600">Concluir ou cancelar aqui atualiza a situação do compromisso. Para registrar uma aplicação, um parto ou um pagamento, use Ver origem.</p>

    <form action="{{ route('agenda.index') }}" method="GET" class="card space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-select name="status" label="Situação">
                @foreach([...\App\Modules\Agenda\Models\Compromisso::STATUS, 'TODOS' => 'Todos'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select name="origem" label="Origem">
                <option value="">Todas</option>
                @foreach($origens as $value => $label)
                    <option value="{{ $value }}" @selected($origem === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
        <button type="submit" class="btn-secondary">Filtrar agenda</button>
    </form>

    <section class="card divide-y divide-stone-100" aria-label="Compromissos">
        @forelse($compromissos as $compromisso)
            <x-compromisso-agenda :compromisso="$compromisso" />
        @empty
            <p class="empty">Nenhum compromisso com os filtros selecionados.</p>
        @endforelse
    </section>
    {{ $compromissos->links() }}
</x-layout>
