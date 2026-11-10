<x-layout :title="$motivo->exists ? 'Editar motivo' : 'Novo motivo'">
    <x-page-heading :title="$motivo->exists ? 'Editar motivo' : 'Novo motivo'" eyebrow="MOTIVOS DE PESAGEM"><a href="{{ $motivo->exists ? route('motivos-pesagem.show', $motivo) : route('motivos-pesagem.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    <form method="POST" action="{{ $motivo->exists ? route('motivos-pesagem.update', $motivo) : route('motivos-pesagem.store') }}" class="card space-y-5">
        @csrf @if($motivo->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2"><x-field name="codigo" label="Código *" :value="$motivo->codigo" required maxlength="40"/><x-field name="nome" label="Nome *" :value="$motivo->nome" required maxlength="100"/><x-select name="ativo" label="Situação *" required><option value="1" @selected((string) old('ativo', (int) $motivo->ativo) === '1')>Ativo</option><option value="0" @selected((string) old('ativo', (int) $motivo->ativo) === '0')>Inativo</option></x-select></div>
        <x-textarea name="descricao" label="Descrição" :value="$motivo->descricao" maxlength="5000"/>
        <p class="text-xs text-stone-500">* Campos obrigatórios. Motivos inativos permanecem no histórico e deixam de aparecer em novos lançamentos.</p>
        <button type="submit" class="btn w-full sm:w-auto">Salvar motivo</button>
    </form>
</x-layout>
