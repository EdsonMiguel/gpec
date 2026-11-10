<x-layout title="Reprodução">
    <x-page-heading title="Controle reprodutivo" eyebrow="GESTAÇÕES">
        <a href="{{ route('gestacoes.create', $animal?->sexo === 'FEMEA' ? ['matriz_id' => $animal->id] : []) }}" class="btn">Registrar cobertura</a>
    </x-page-heading>

    <form method="GET" action="{{ route('gestacoes.index') }}" class="card space-y-4">
        @if($animal)
            <input type="hidden" name="animal_id" value="{{ $animal->id }}">
            <p class="text-sm">Histórico de <a class="font-semibold text-emerald-800 underline" href="{{ route('animais.show', $animal) }}">{{ $animal->brinco }}</a></p>
        @endif
        <x-select name="status" label="Situação">
            <option value="">Todas</option>
            @foreach(\App\Modules\Reproducao\Models\Gestacao::STATUS as $codigo => $label)
                <option value="{{ $codigo }}" @selected(request('status') === $codigo)>{{ $label }}</option>
            @endforeach
        </x-select>
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-secondary">Filtrar</button>
            @if($animal || request('status'))
                <a href="{{ route('gestacoes.index') }}" class="btn-secondary">Limpar filtros</a>
            @endif
        </div>
    </form>

    <div class="space-y-3">
        @forelse($gestacoes as $gestacao)
            <a href="{{ route('gestacoes.show', $gestacao) }}" class="card block space-y-2 hover:border-emerald-500">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="break-words font-semibold">{{ $gestacao->matriz->brinco }} · {{ $gestacao->matriz->nome_ou_numero ?: 'Matriz' }}</h2>
                    <span class="badge">{{ $gestacao::STATUS[$gestacao->status] }}</span>
                </div>
                <p class="text-sm text-stone-600">{{ $gestacao::TIPOS[$gestacao->tipo] }} · {{ $gestacao->data_cobertura->format('d/m/Y') }}</p>
                @if($gestacao->status === 'CONFIRMADA' && $gestacao->data_prevista_parto)
                    <p class="text-sm font-semibold text-emerald-800">Previsão de parto {{ $gestacao->data_prevista_parto->format('d/m/Y') }}</p>
                @endif
            </a>
        @empty
            <p class="empty">Nenhum ciclo reprodutivo encontrado.</p>
        @endforelse
    </div>
    {{ $gestacoes->links() }}
</x-layout>
