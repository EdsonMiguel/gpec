<x-layout title="Registrar refeição">
    <x-page-heading title="Registrar refeição" eyebrow="ALIMENTAÇÃO"><a href="{{ route('refeicoes.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    @if($alimentos->isEmpty())<div class="card"><p class="text-sm">Cadastre ou ative um alimento e seu tipo para registrar refeições. Solicite o cadastro ao administrador.</p>@can('administrar')<a href="{{ route('alimentos.create') }}" class="btn mt-4">Cadastrar alimento</a>@endcan</div>@else
    <form method="POST" action="{{ route('refeicoes.store') }}" class="card space-y-5" data-refeicao-form>@csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-destino-lancamento :selected="$selected" :lotes="$lotes" :lote-id="$loteId" />
            <x-field name="data_fornecimento" label="Data e hora do fornecimento *" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" :max="now()->format('Y-m-d\TH:i')" required />
            <x-select name="turno" label="Turno *" required><option value="">Selecione</option>@foreach(\App\Modules\Alimentacao\Models\Refeicao::TURNOS as $value => $label)<option value="{{ $value }}" @selected(old('turno') === $value)>{{ $label }}</option>@endforeach</x-select>
        </div>
        <h2 class="section-title">Alimentos fornecidos</h2>
        @php($rows = is_array(old('itens')) ? array_slice(old('itens'), 0, 20) : [[]])
        <div class="space-y-3" data-refeicao-items>@forelse($rows as $item)<x-refeicao-item :alimentos="$alimentos" :index="$loop->index" :item="$item"/>@empty<x-refeicao-item :alimentos="$alimentos" :index="0"/>@endforelse</div>
        <button type="button" class="btn-secondary" data-add-refeicao-item>+ Adicionar alimento</button><p class="text-xs text-stone-500" data-refeicao-status aria-live="polite">Inclua até 20 alimentos diferentes na mesma refeição.</p>
        <template data-refeicao-template><x-refeicao-item :alimentos="$alimentos" index="__INDEX__"/></template>
        <x-textarea name="observacao" label="Observações" maxlength="2000"/>
        <button type="submit" class="btn w-full sm:w-auto">Salvar refeição</button>
    </form>@endif
</x-layout>
