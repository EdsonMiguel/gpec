@props(['compromisso', 'retorno' => 'agenda.index'])

<article class="space-y-3 py-4 first:pt-0 last:pb-0">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-xs font-semibold text-stone-500">{{ $compromisso::ORIGENS[$compromisso->origem_type] }}</p>
            <h3 class="mt-1 break-words text-sm font-semibold">{{ $compromisso->titulo }}</h3>
        </div>
        <span @class([
            'badge',
            'bg-amber-50 text-amber-900' => $compromisso->status === 'PENDENTE',
            'bg-emerald-50 text-emerald-900' => $compromisso->status === 'CONCLUIDO',
        ])>{{ $compromisso::STATUS[$compromisso->status] }}</span>
    </div>
    <p @class(['text-sm tabular-nums', 'font-semibold text-red-800' => $compromisso->atrasado(), 'text-stone-600' => ! $compromisso->atrasado()])>
        <time datetime="{{ $compromisso->data_prevista->toDateString() }}">{{ $compromisso->data_prevista->format('d/m/Y') }}</time>
        @if($compromisso->atrasado()) · Atrasado @endif
    </p>

    <div class="flex flex-wrap items-start gap-2">
        <a href="{{ $compromisso->urlOrigem() }}" class="btn-secondary">Ver origem</a>
        @if($compromisso->status === 'PENDENTE')
            <form action="{{ route('agenda.atualizar', $compromisso) }}" method="POST" class="flex flex-wrap gap-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="retorno" value="{{ $retorno }}">
                <button type="submit" name="status" value="CONCLUIDO" class="btn-secondary text-emerald-900">Concluir</button>
                <button type="submit" name="status" value="CANCELADO" class="btn-secondary">Cancelar</button>
            </form>
        @endif
        <details>
            <summary class="min-h-12 cursor-pointer rounded-xl px-3 py-3 text-sm font-semibold text-red-800 hover:bg-red-50">Excluir</summary>
            <form action="{{ route('agenda.excluir', $compromisso) }}" method="POST" class="mt-2">
                @csrf
                @method('DELETE')
                <input type="hidden" name="retorno" value="{{ $retorno }}">
                <button type="submit" class="btn-secondary text-red-800">Confirmar exclusão da agenda</button>
            </form>
        </details>
    </div>
</article>
