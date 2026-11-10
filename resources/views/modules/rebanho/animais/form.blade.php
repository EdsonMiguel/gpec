<x-layout :title="$animal->exists ? 'Editar animal' : 'Novo animal'">
    <x-page-heading :title="$animal->exists ? 'Editar animal' : 'Novo animal'" eyebrow="REBANHO"><a class="btn-secondary" href="{{ $animal->exists ? route('animais.show', $animal) : route('animais.index') }}">Voltar</a></x-page-heading>
    <form class="card space-y-5" method="POST" action="{{ $animal->exists ? route('animais.update', $animal) : route('animais.store') }}">
        @csrf @if($animal->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="brinco" label="Brinco *" :value="$animal->brinco" required maxlength="50" autofocus />
            <x-field name="nome_ou_numero" label="Nome ou número" :value="$animal->nome_ou_numero" maxlength="100" />
            <x-select name="categoria" label="Categoria *" required><option value="">Selecione</option>@foreach(\App\Modules\Rebanho\Models\Animal::CATEGORIAS as $categoria)<option value="{{ $categoria }}" @selected(old('categoria', $animal->categoria) === $categoria)>{{ $categoria }}</option>@endforeach</x-select>
            <x-select name="sexo" label="Sexo *" required><option value="">Selecione</option>@foreach(['MACHO' => 'Macho', 'FEMEA' => 'Fêmea'] as $value => $label)<option value="{{ $value }}" @selected(old('sexo', $animal->sexo) === $value)>{{ $label }}</option>@endforeach</x-select>
            <x-field name="raca" label="Raça" :value="$animal->raca" maxlength="100" />
            <x-field name="data_nascimento" type="date" label="Data de nascimento" :value="$animal->data_nascimento?->format('Y-m-d')" :max="today()->format('Y-m-d')" />
            <x-select name="status" label="Situação *" required>@foreach(\App\Modules\Rebanho\Models\Animal::STATUS as $status)<option value="{{ $status }}" @selected(old('status', $animal->status) === $status)>{{ $status }}</option>@endforeach</x-select>
            <x-select name="lote_id" label="Lote"><option value="">Sem lote</option>@foreach($lotes as $lote)<option value="{{ $lote->id }}" @selected((string) old('lote_id', $animal->lote_id) === (string) $lote->id)>{{ $lote->nome }}</option>@endforeach</x-select>
        </div>
        <fieldset class="min-w-0 space-y-4 border-t border-stone-100 pt-5">
            <legend class="section-title pt-4">Filiação (opcional)</legend>
            <p class="text-xs text-stone-500">Selecione os animais pelo brinco. Pais não cadastrados ou desconhecidos podem ficar sem informação.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-select name="pai_id" label="Pai"><option value="">Não informado</option>@foreach($pais as $pai)<option value="{{ $pai->id }}" @selected((string) old('pai_id', $animal->pai_id) === (string) $pai->id)>{{ $pai->brinco }}{{ $pai->nome_ou_numero ? ' · '.$pai->nome_ou_numero : '' }}{{ $pai->trashed() ? ' (arquivado)' : '' }}</option>@endforeach</x-select>
                <x-select name="mae_id" label="Mãe"><option value="">Não informada</option>@foreach($maes as $mae)<option value="{{ $mae->id }}" @selected((string) old('mae_id', $animal->mae_id) === (string) $mae->id)>{{ $mae->brinco }}{{ $mae->nome_ou_numero ? ' · '.$mae->nome_ou_numero : '' }}{{ $mae->trashed() ? ' (arquivada)' : '' }}</option>@endforeach</x-select>
            </div>
        </fieldset>
        <p class="text-xs text-stone-500">* Campos obrigatórios. Para uma situação diferente de ATIVO, selecione “Sem lote”.</p>
        <button type="submit" class="btn w-full sm:w-auto">Salvar animal</button>
    </form>
</x-layout>
