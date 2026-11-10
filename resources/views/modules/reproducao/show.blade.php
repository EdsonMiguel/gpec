<x-layout :title="'Gestação · '.$gestacao->matriz->brinco">
    <x-page-heading :title="'Matriz '.$gestacao->matriz->brinco" eyebrow="REPRODUÇÃO">
        <a href="{{ route('gestacoes.index') }}" class="btn-secondary">Voltar</a>
    </x-page-heading>

    <section class="card space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('animais.show', $gestacao->matriz) }}" class="font-semibold text-emerald-800 underline">Ficha da matriz</a>
            <span class="badge">{{ $gestacao::STATUS[$gestacao->status] }}</span>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs text-stone-500">Confirmação</p>
                <p class="font-semibold">{{ $gestacao->data_confirmacao?->format('d/m/Y') ?? 'A confirmar' }}</p>
            </div>
            <div>
                <p class="text-xs text-stone-500">Previsão de parto</p>
                <p class="font-semibold">{{ $gestacao->data_prevista_parto?->format('d/m/Y') ?? 'Não informada' }}</p>
            </div>
        </div>
    </section>

    <section class="card space-y-4">
        <h2 class="section-title">Histórico do ciclo</h2>
        <ol class="divide-y divide-stone-100">
            <li class="space-y-1 py-3 text-sm">
                <p class="font-semibold">{{ $gestacao->data_cobertura->format('d/m/Y') }} · {{ $gestacao::TIPOS[$gestacao->tipo] }}</p>
                <p>Reprodutor
                    @if($gestacao->reprodutor)
                        <a href="{{ route('animais.show', $gestacao->reprodutor) }}" class="text-emerald-800 underline">{{ $gestacao->reprodutor->brinco }}</a>
                    @else
                        não informado
                    @endif
                </p>
                @if($gestacao->referencia_semen)<p class="break-words">Sêmen {{ $gestacao->referencia_semen }}</p>@endif
                @if($gestacao->responsavel)<p class="break-words text-stone-500">{{ $gestacao->responsavel }}</p>@endif
                @if($gestacao->observacao)<p class="whitespace-pre-line break-words text-stone-600">{{ $gestacao->observacao }}</p>@endif
            </li>
            @foreach($gestacao->exames as $exame)
                <li class="space-y-1 py-3 text-sm">
                    <p class="break-words font-semibold">{{ $exame->data_exame->format('d/m/Y') }} · {{ $exame->metodo }}</p>
                    <span class="badge">{{ $exame::RESULTADOS[$exame->resultado] }}</span>
                    @if($exame->responsavel)<p class="break-words text-stone-500">{{ $exame->responsavel }}</p>@endif
                    @if($exame->observacao)<p class="whitespace-pre-line break-words text-stone-600">{{ $exame->observacao }}</p>@endif
                </li>
            @endforeach
            @if($gestacao->data_encerramento)
                <li class="space-y-1 py-3 text-sm">
                    <p class="font-semibold">{{ $gestacao->data_encerramento->format('d/m/Y') }} · {{ $gestacao::STATUS[$gestacao->status] }}</p>
                    @if($gestacao->observacao_encerramento)<p class="whitespace-pre-line break-words text-stone-600">{{ $gestacao->observacao_encerramento }}</p>@endif
                </li>
            @endif
        </ol>
    </section>

    @if($gestacao->estaAberta())
        @php($dataMinima = ($gestacao->exames->last()?->data_exame ?? $gestacao->data_cobertura)->toDateString())
        <form method="POST" action="{{ route('gestacoes.exames.store', $gestacao) }}" class="card space-y-5">
            @csrf
            <h2 class="section-title">Registrar exame</h2>
            <p class="text-sm text-stone-600">O primeiro resultado positivo confirma a gestação. Um resultado negativo encerra um ciclo ainda sem confirmação.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="data_exame" label="Data do exame *" type="date" :value="today()->toDateString()" :min="$dataMinima" :max="today()->toDateString()" required />
                <x-field name="metodo" label="Método *" placeholder="Ex.: ultrassonografia" maxlength="100" required />
                <x-select name="resultado" label="Resultado *" required>
                    <option value="">Selecione</option>
                    @foreach(\App\Modules\Reproducao\Models\ExameGestacao::RESULTADOS as $codigo => $label)
                        <option value="{{ $codigo }}" @selected(old('resultado') === $codigo)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-field name="data_prevista_parto" label="Previsão de parto" type="date" hint="Opcional em exames positivos." />
                <x-field name="responsavel" label="Responsável pelo exame" maxlength="150" />
            </div>
            <x-textarea name="observacao" label="Observações do exame" maxlength="5000" />
            <button type="submit" class="btn">Salvar exame</button>
        </form>

        <section class="card space-y-4">
            <h2 class="section-title">{{ $gestacao->status === 'CONFIRMADA' ? 'Registrar desfecho' : 'Encerrar ciclo sem confirmação' }}</h2>
            <form method="POST" action="{{ route('gestacoes.encerrar', $gestacao) }}" class="space-y-4">
                @csrf
                @if($gestacao->status === 'CONFIRMADA')
                    <x-select name="status" label="Desfecho *" required>
                        <option value="">Selecione</option>
                        <option value="PARTO" @selected(old('status') === 'PARTO')>Parto</option>
                        <option value="PERDA" @selected(old('status') === 'PERDA')>Perda gestacional</option>
                    </x-select>
                @else
                    <input type="hidden" name="status" value="ENCERRADA">
                @endif
                <x-field name="data_encerramento" label="Data do desfecho *" type="date" :value="today()->toDateString()" :min="$dataMinima" :max="today()->toDateString()" required />
                <x-textarea name="observacao_encerramento" label="Observação ou motivo do encerramento" maxlength="5000" :required="$gestacao->status !== 'CONFIRMADA'" />
                <button type="submit" class="btn-secondary">Confirmar desfecho</button>
            </form>
        </section>
    @elseif($gestacao->status === 'PARTO')
        <section class="card space-y-4">
            <h2 class="section-title">Crias do parto</h2>
            @forelse($gestacao->crias as $cria)
                <a href="{{ route('animais.genealogia', $cria) }}" class="block break-words text-sm font-semibold text-emerald-800 underline">{{ $cria->brinco }} · {{ $cria->nome_ou_numero }}</a>
            @empty
                <p class="text-sm text-stone-600">Nenhuma cria vinculada.</p>
            @endforelse
            @can('administrar')
                <p class="text-sm text-stone-600">Cadastre a cria com a data do parto e vincule-a aqui. A filiação é preenchida com a matriz e o reprodutor informado no ciclo.</p>
                <a href="{{ route('animais.create') }}" class="btn-secondary">Cadastrar animal</a>
                @if($criasDisponiveis->isNotEmpty())
                    <form method="POST" action="{{ route('gestacoes.crias.store', $gestacao) }}" class="space-y-4">
                        @csrf
                        <x-select name="cria_id" label="Animal nascido em {{ $gestacao->data_encerramento->format('d/m/Y') }}" required>
                            <option value="">Selecione</option>
                            @foreach($criasDisponiveis as $cria)
                                <option value="{{ $cria->id }}" @selected(old('cria_id') == $cria->id)>{{ $cria->brinco }} · {{ $cria->nome_ou_numero }}</option>
                            @endforeach
                        </x-select>
                        <button type="submit" class="btn">Vincular cria ao parto</button>
                    </form>
                @endif
            @endcan
        </section>
    @endif
</x-layout>
