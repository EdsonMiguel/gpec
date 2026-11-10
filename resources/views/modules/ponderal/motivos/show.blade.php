<x-layout title="Motivo de pesagem">
    <x-page-heading title="Motivo de pesagem" eyebrow="CONTROLE PONDERAL"><a href="{{ route('motivos-pesagem.index') }}" class="btn-secondary">Voltar</a></x-page-heading>
    <section class="card space-y-4"><div class="flex items-start justify-between gap-3"><h2 class="min-w-0 break-words text-lg font-bold">{{ $motivo->nome }}</h2><span class="badge shrink-0">{{ $motivo->ativo ? 'Ativo' : 'Inativo' }}</span></div><p class="break-words text-sm text-stone-500">Código: {{ $motivo->codigo }}</p>@if($motivo->descricao)<p class="whitespace-pre-line break-words text-sm">{{ $motivo->descricao }}</p>@endif<p class="text-sm text-emerald-800">{{ $motivo->pesagens_count }} pesagens vinculadas</p>@can('administrar')<a href="{{ route('motivos-pesagem.edit', $motivo) }}" class="btn-secondary">Editar motivo</a>@endcan</section>
    @can('administrar')
        @if($motivo->pesagens_count > 0)<p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Este motivo já foi utilizado e não pode ser excluído. Para impedir novos lançamentos, edite-o e selecione a situação “Inativo”.</p>
        @else<details class="card"><summary class="min-h-11 cursor-pointer text-sm font-semibold">Excluir motivo</summary><p class="my-3 text-sm text-stone-500">Este motivo não possui pesagens vinculadas.</p><form method="POST" action="{{ route('motivos-pesagem.destroy', $motivo) }}">@csrf @method('DELETE')<button type="submit" class="btn-secondary text-red-800">Confirmar exclusão</button></form></details>@endif
    @endcan
</x-layout>
