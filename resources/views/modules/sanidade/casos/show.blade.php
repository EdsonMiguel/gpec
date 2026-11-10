<x-layout :title="$caso->titulo">
    <x-page-heading :title="$caso->titulo" eyebrow="CASO CLÍNICO">
        <a href="{{ route('casos-clinicos.index', ['animal_id' => $caso->animal_id]) }}" class="btn-secondary">Ver prontuário</a>
    </x-page-heading>

    <section class="card space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('animais.show', $caso->animal) }}" class="font-semibold text-emerald-800 underline">Brinco {{ $caso->animal->brinco }}</a>
            <span @class(['badge', 'bg-amber-50 text-amber-900' => $caso->estaAtivo()])>{{ $caso::STATUS[$caso->status] }}</span>
        </div>
        <div>
            <h2 class="section-title">Sintomas iniciais</h2>
            <p class="mt-1 text-xs text-stone-500">Observados a partir de {{ $caso->data_inicio->format('d/m/Y') }}</p>
            <p class="mt-2 whitespace-pre-line break-words text-sm">{{ $caso->sintomas_iniciais }}</p>
        </div>
        @if($caso->data_encerramento)
            <div class="border-t border-stone-100 pt-4">
                <h2 class="section-title">Desfecho</h2>
                <p class="mt-1 text-sm font-semibold">{{ $caso::DESFECHOS[$caso->desfecho] }} · {{ $caso->data_encerramento->format('d/m/Y') }}</p>
                <p class="mt-2 whitespace-pre-line break-words text-sm">{{ $caso->observacao_encerramento }}</p>
            </div>
        @endif
    </section>

    <section class="card space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="section-title">Avaliações veterinárias</h2>
            @if($caso->estaAtivo())<a href="#avaliacao" class="btn-secondary">Nova avaliação</a>@endif
        </div>
        <div class="divide-y divide-stone-100">
            @forelse($avaliacoes as $avaliacao)
                <article class="space-y-3 py-4 first:pt-0 last:pb-0">
                    <p class="break-words text-sm font-semibold">{{ $avaliacao->data_avaliacao->format('d/m/Y') }} · {{ $avaliacao->veterinario }}</p>
                    @if($avaliacao->doenca)
                        <p class="break-words text-sm"><span class="font-semibold">{{ $avaliacao->doenca->nome }}</span> · {{ $avaliacao::DIAGNOSTICOS[$avaliacao->situacao_diagnostico] }}</p>
                    @endif
                    <dl class="space-y-2 text-sm">
                        @foreach(['Sintomas na avaliação' => $avaliacao->sintomas, 'Parecer' => $avaliacao->parecer, 'Orientações' => $avaliacao->orientacao] as $label => $texto)
                            @if($texto)
                                <div><dt class="text-xs font-semibold text-stone-600">{{ $label }}</dt><dd class="whitespace-pre-line break-words">{{ $texto }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                </article>
            @empty
                <p class="empty">Nenhuma avaliação veterinária registrada.</p>
            @endforelse
        </div>
        {{ $avaliacoes->links() }}
    </section>

    @if($caso->estaAtivo())
        <form id="avaliacao" method="POST" action="{{ route('casos-clinicos.avaliacoes.store', $caso) }}" class="card scroll-mt-20 space-y-5">
            @csrf
            <h2 class="section-title">Registrar avaliação veterinária</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="data_avaliacao" label="Data da avaliação *" type="date" :value="today()->toDateString()" :min="$caso->data_inicio->toDateString()" :max="today()->toDateString()" required />
                <x-field name="veterinario" label="Nome do veterinário *" maxlength="150" required />
                @if($doencas->isNotEmpty())
                    <x-select name="doenca_id" label="Doença citada">
                        <option value="">Sem doença vinculada</option>
                        @foreach($doencas as $doenca)
                            <option value="{{ $doenca->id }}" @selected(old('doenca_id') == $doenca->id)>{{ $doenca->nome }}</option>
                        @endforeach
                    </x-select>
                    <x-select name="situacao_diagnostico" label="Situação do diagnóstico">
                        <option value="">Preencha ao escolher uma doença</option>
                        @foreach(\App\Modules\Sanidade\Models\AvaliacaoClinica::DIAGNOSTICOS as $value => $label)
                            <option value="{{ $value }}" @selected(old('situacao_diagnostico') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                @endif
            </div>
            @if($doencas->isEmpty())
                <p class="text-sm text-stone-600">Nenhuma doença ativa para vincular. A avaliação pode ser registrada sem diagnóstico cadastrado.
                    @can('administrar')<a href="{{ route('doencas.index') }}" class="font-semibold text-emerald-800 underline">Ver cadastro de doenças</a>@endcan
                </p>
            @endif
            <x-textarea name="sintomas" label="Sintomas na avaliação" maxlength="5000" />
            <x-textarea name="parecer" label="O que o veterinário disse *" maxlength="5000" required />
            <x-textarea name="orientacao" label="Orientações do veterinário" maxlength="5000" />
            <button type="submit" class="btn">Salvar avaliação</button>
        </form>

        <details class="card" @if($errors->hasAny(['data_encerramento', 'desfecho', 'observacao_encerramento'])) open @endif>
            <summary class="min-h-11 cursor-pointer text-sm font-semibold">Encerrar caso</summary>
            <form method="POST" action="{{ route('casos-clinicos.encerrar', $caso) }}" class="space-y-5 pt-2">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="data_encerramento" label="Data do encerramento *" type="date" :value="today()->toDateString()" :min="$caso->data_inicio->toDateString()" :max="today()->toDateString()" required />
                    <x-select name="desfecho" label="Desfecho *" required>
                        <option value="">Selecione</option>
                        @foreach($caso::DESFECHOS as $value => $label)
                            <option value="{{ $value }}" @selected(old('desfecho') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-textarea name="observacao_encerramento" label="Motivo do encerramento *" maxlength="5000" required />
                <button type="submit" class="btn-secondary">Confirmar encerramento</button>
            </form>
        </details>
    @endif
</x-layout>
