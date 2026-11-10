<a href="{{ route('casos-clinicos.show', $caso) }}" class="block space-y-1 rounded-lg px-2 py-3 hover:bg-stone-50">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="min-w-0 break-words text-sm font-semibold">
            @if($exibirAnimal){{ $caso->animal->brinco }} · @endif{{ $caso->titulo }}
        </h3>
        <span @class(['badge', 'bg-amber-50 text-amber-900' => $caso->estaAtivo()])>{{ $caso::STATUS[$caso->status] }}</span>
    </div>
    <p class="text-xs text-stone-500">Início dos sintomas em {{ $caso->data_inicio->format('d/m/Y') }}</p>
    <p class="break-words text-sm text-stone-600">{{ \Illuminate\Support\Str::limit($caso->sintomas_iniciais, 140) }}</p>
</a>
