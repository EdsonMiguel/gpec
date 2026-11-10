<x-layout :title="'Genealogia · '.$animal->brinco">
    <x-page-heading :title="'Genealogia de '.$animal->brinco" eyebrow="REBANHO">
        <a href="{{ route('animais.show', $animal) }}" class="btn-secondary">Ficha do animal</a>
    </x-page-heading>

    @if($animal->gestacaoNascimento)
        <div class="card space-y-2 text-sm">
            <a href="{{ route('gestacoes.show', $animal->gestacaoNascimento) }}" class="font-semibold text-emerald-800 underline">Ver gestação de origem</a>
            <p class="text-stone-600">O vínculo com o parto registra a origem no rebanho. A filiação usa os dados cadastrados da cobertura e do animal.</p>
        </div>
    @endif

    <section class="card space-y-4">
        <h2 class="section-title">Ascendentes</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach(['Paterna' => ['Pai', $animal->pai], 'Materna' => ['Mãe', $animal->mae]] as $linha => [$label, $parente])
                <div class="rounded-xl bg-stone-50 p-4">
                    <h3 class="mb-3 font-semibold">Linha {{ strtolower($linha) }}</h3>
                    <dl class="space-y-3 text-sm">
                        @foreach([$label => $parente, 'Avô' => $parente?->pai, 'Avó' => $parente?->mae] as $parentesco => $ascendente)
                            <div>
                                <dt class="text-xs text-stone-500">{{ $parentesco }}</dt>
                                <dd class="mt-1 break-words">
                                    @if($ascendente)
                                        <a href="{{ route('animais.genealogia', $ascendente) }}" class="font-semibold text-emerald-800 underline">{{ $ascendente->brinco }} · {{ $ascendente->nome_ou_numero }}</a>
                                        @if($ascendente->trashed())<span class="badge">Arquivado</span>@endif
                                    @else
                                        Não informado
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card space-y-4">
        <h2 class="section-title">Descendentes diretos</h2>
        @forelse($descendentes as $cria)
            <p class="break-words text-sm">
                <a href="{{ route('animais.genealogia', $cria) }}" class="font-semibold text-emerald-800 underline">{{ $cria->brinco }} · {{ $cria->nome_ou_numero }}</a>
                @if($cria->trashed())<span class="badge">Arquivado</span>@endif
            </p>
        @empty
            <p class="empty">Nenhum descendente vinculado.</p>
        @endforelse
        {{ $descendentes->links() }}
    </section>
</x-layout>
