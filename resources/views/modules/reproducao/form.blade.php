<x-layout title="Registrar cobertura">
    <x-page-heading title="Registrar cobertura" eyebrow="REPRODUÇÃO">
        <a href="{{ route('gestacoes.index') }}" class="btn-secondary">Voltar</a>
    </x-page-heading>

    @if($matrizes->isEmpty())
        <p class="card text-sm">Cadastre uma fêmea ativa no rebanho para iniciar um ciclo.</p>
    @else
        <form method="POST" action="{{ route('gestacoes.store') }}" class="card space-y-5">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <x-select name="matriz_id" label="Matriz *" required>
                    <option value="">Selecione</option>
                    @foreach($matrizes as $matriz)
                        <option value="{{ $matriz->id }}" @selected(old('matriz_id', $matrizId) == $matriz->id)>{{ $matriz->brinco }} · {{ $matriz->nome_ou_numero }}</option>
                    @endforeach
                </x-select>
                <x-select name="tipo" label="Tipo de cobertura *" required>
                    @foreach(\App\Modules\Reproducao\Models\Gestacao::TIPOS as $codigo => $label)
                        <option value="{{ $codigo }}" @selected(old('tipo') === $codigo)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-field name="data_cobertura" label="Data da cobertura *" type="date" :value="today()->toDateString()" :max="today()->toDateString()" required />
                <x-select name="reprodutor_id" label="Reprodutor (obrigatório na monta)">
                    <option value="">Não informado</option>
                    @foreach($reprodutores as $reprodutor)
                        <option value="{{ $reprodutor->id }}" @selected(old('reprodutor_id') == $reprodutor->id)>{{ $reprodutor->brinco }} · {{ $reprodutor->nome_ou_numero }}{{ $reprodutor->trashed() ? ' (arquivado)' : '' }}</option>
                    @endforeach
                </x-select>
                <x-field name="referencia_semen" label="Lote ou código do sêmen" hint="Preencha somente para inseminação." maxlength="100" />
                <x-field name="responsavel" label="Responsável" maxlength="150" />
            </div>
            <x-textarea name="observacao" label="Observações da cobertura" maxlength="5000" />
            <button type="submit" class="btn">Salvar cobertura</button>
        </form>
    @endif
</x-layout>
