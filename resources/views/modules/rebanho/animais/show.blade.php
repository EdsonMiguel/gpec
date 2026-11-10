<x-layout :title="'Brinco '.$animal->brinco">
    <x-page-heading :title="'Brinco '.$animal->brinco" eyebrow="FICHA DO ANIMAL"><a href="{{ route('animais.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    <x-rebanho-nav />
    <section class="card"><div class="mb-4 flex items-center justify-between"><h2 class="section-title">{{ $animal->nome_ou_numero ?: 'Identificação do animal' }}</h2><span class="badge">{{ $animal->status }}</span></div><dl class="grid grid-cols-2 gap-4 text-sm">@foreach(['Categoria' => $animal->categoria, 'Sexo' => $animal->sexo, 'Raça' => $animal->raca, 'Nascimento' => $animal->data_nascimento?->format('d/m/Y'), 'Lote' => $animal->lote?->nome] as $label => $value)<div><dt class="text-xs text-stone-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $value ?: 'Não informado' }}</dd></div>@endforeach</dl>
        @if($animal->trashed())<p class="mt-4 text-sm font-semibold text-stone-500">Animal arquivado · consulta do histórico</p>@else @can('administrar')<a href="{{ route('animais.edit', $animal) }}" class="btn-secondary mt-5">Editar cadastro</a>@endcan @endif
    </section>
    <section class="card"><h2 class="section-title mb-4">Filiação</h2><dl class="grid grid-cols-2 gap-4 text-sm">
        @foreach(['Pai' => $animal->pai, 'Mãe' => $animal->mae] as $label => $parente)
            <div class="min-w-0"><dt class="text-xs text-stone-500">{{ $label }}</dt><dd class="mt-1 break-words font-semibold">@if($parente)<a href="{{ route('animais.show', $parente) }}" class="text-emerald-800 underline">{{ $parente->brinco }}</a>@if($parente->nome_ou_numero)<p class="text-xs font-normal text-stone-500">{{ $parente->nome_ou_numero }}</p>@endif @if($parente->trashed())<span class="badge mt-1">Arquivado</span>@endif @else Não informado @endif</dd></div>
        @endforeach
    </dl>
        <div class="mt-4 flex flex-wrap gap-2">
            <a class="btn-secondary" href="{{ route('animais.genealogia', $animal) }}">Ver genealogia</a>
            @can('registrar-manejo')<a class="btn-secondary" href="{{ route('gestacoes.index', ['animal_id' => $animal->id]) }}">Histórico reprodutivo</a>@endcan
        </div>
    </section>
    @if($animal->status === 'ATIVO' && ! $animal->trashed())<div class="grid grid-cols-2 gap-3"><a class="btn" href="{{ route('pesagens.index', ['animal_id' => $animal->id]) }}">Registrar peso</a><a class="btn-secondary" href="{{ route('sanidade.index', ['animal_id' => $animal->id]) }}">Registrar aplicação</a></div>@endif
    <section class="card space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="section-title">Prontuário clínico</h2>
                @if($animal->casos_clinicos_ativos_count)
                    <p class="mt-2 text-sm font-semibold text-amber-900">{{ $animal->casos_clinicos_ativos_count }} {{ $animal->casos_clinicos_ativos_count === 1 ? 'caso clínico ativo' : 'casos clínicos ativos' }}</p>
                @endif
            </div>
            @if($animal->status === 'ATIVO' && ! $animal->trashed())
                <a href="{{ route('casos-clinicos.create', ['animal_id' => $animal->id]) }}" class="btn-secondary">Registrar sintomas</a>
            @endif
        </div>
        <div class="divide-y divide-stone-100">
            @forelse($casosClinicos as $caso)
                @include('sanidade::casos.resumo', ['exibirAnimal' => false])
            @empty
                <p class="text-sm text-stone-600">Nenhum caso clínico registrado.</p>
            @endforelse
        </div>
        @if($casosClinicos->isNotEmpty())
            <a href="{{ route('casos-clinicos.index', ['animal_id' => $animal->id]) }}" class="btn-secondary">Ver todos os casos</a>
        @endif
    </section>
    <div class="grid gap-5 md:grid-cols-2">
        @include('rebanho::animais.pesagens')
        <section class="card"><h2 class="section-title mb-3">Histórico sanitário</h2>@forelse($manejos as $manejo)<div class="border-b border-stone-100 py-3 text-sm"><p class="font-semibold">{{ $manejo->medicamento->nome }} · {{ $manejo->dosagem }} {{ $manejo->unidade }}</p><p class="mt-1 text-xs text-stone-500">{{ $manejo->tipo }} · {{ $manejo->data_aplicacao->format('d/m/Y') }}</p>@if($manejo->proxima_aplicacao)<p class="mt-1 text-xs">Previsão registrada: {{ $manejo->proxima_aplicacao->format('d/m/Y') }}</p>@endif</div>@empty<p class="empty">Nenhuma aplicação registrada.</p>@endforelse<div class="mt-4">{{ $manejos->links() }}</div></section>
    </div>
    <section class="card"><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h2 class="section-title">Histórico de alimentação</h2>@if($animal->status === 'ATIVO' && ! $animal->trashed())<a href="{{ route('refeicoes.create', ['animal_id' => $animal->id]) }}" class="btn-secondary">Registrar refeição</a>@endif</div>@forelse($refeicoes as $refeicao)<a href="{{ route('refeicoes.show', $refeicao) }}" class="block space-y-2 border-b border-stone-100 py-4"><p class="text-sm font-semibold">{{ $refeicao->data_fornecimento->format('d/m/Y H:i') }} · {{ \App\Modules\Alimentacao\Models\Refeicao::TURNOS[$refeicao->turno] }}</p>@foreach($refeicao->itens as $item)<p class="break-words text-sm text-stone-600">{{ $item->alimento->nome }} · {{ $item->quantidade }} {{ $item->unidade }}</p>@endforeach</a>@empty<p class="empty">Nenhuma refeição registrada.</p>@endforelse<div class="mt-4">{{ $refeicoes->links() }}</div></section>
    @unless($animal->trashed()) @can('administrar')<details class="card"><summary class="min-h-11 cursor-pointer text-sm font-semibold text-stone-600">Arquivar animal</summary><p class="my-3 text-sm text-stone-500">O animal sairá da listagem e os registros de manejo serão preservados.</p><form method="POST" action="{{ route('animais.destroy', $animal) }}">@csrf @method('DELETE')<button type="submit" class="btn-secondary text-red-800">Confirmar arquivamento</button></form></details>@endcan @endunless
</x-layout>
