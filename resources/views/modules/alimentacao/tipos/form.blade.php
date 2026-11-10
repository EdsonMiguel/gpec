<x-layout :title="$tipo->exists ? 'Editar tipo' : 'Novo tipo de alimento'">
    <x-page-heading :title="$tipo->exists ? 'Editar tipo' : 'Novo tipo de alimento'" eyebrow="ALIMENTAÇÃO"><a href="{{ route('tipos-alimento.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    <form method="POST" action="{{ $tipo->exists ? route('tipos-alimento.update', $tipo) : route('tipos-alimento.store') }}" class="card space-y-5">@csrf @if($tipo->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2"><x-field name="codigo" label="Código *" :value="$tipo->codigo" required maxlength="40"/><x-field name="nome" label="Nome *" :value="$tipo->nome" required maxlength="100"/><x-select name="ativo" label="Situação *" required><option value="1" @selected((string) old('ativo', (int) $tipo->ativo) === '1')>Ativo</option><option value="0" @selected((string) old('ativo', (int) $tipo->ativo) === '0')>Inativo</option></x-select></div>
        <x-textarea name="descricao" label="Descrição" :value="$tipo->descricao" maxlength="5000"/>
        <p class="text-xs text-stone-500">Inativar o tipo impede novos lançamentos de refeições com seus alimentos e preserva o histórico.</p><button class="btn" type="submit">Salvar tipo</button>
    </form>
</x-layout>
