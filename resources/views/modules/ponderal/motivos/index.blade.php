<x-layout title="Motivos de pesagem">
    <x-page-heading title="Motivos de pesagem" eyebrow="CONTROLE PONDERAL">@can('administrar')<a href="{{ route('motivos-pesagem.create') }}" class="btn">+ Novo motivo</a>@endcan</x-page-heading>
    <a href="{{ route('pesagens.index') }}" class="btn-secondary">Voltar às pesagens</a>
    <div class="grid gap-4 sm:grid-cols-2">@forelse($motivos as $motivo)<a href="{{ route('motivos-pesagem.show', $motivo) }}" class="card min-w-0 space-y-3 hover:border-emerald-500"><div class="flex items-start justify-between gap-3"><h2 class="min-w-0 break-words font-bold">{{ $motivo->nome }}</h2><span class="badge shrink-0">{{ $motivo->ativo ? 'Ativo' : 'Inativo' }}</span></div><p class="break-words text-xs text-stone-500">{{ $motivo->codigo }}</p><p class="text-sm text-emerald-800">{{ $motivo->pesagens_count }} pesagens vinculadas</p></a>@empty<div class="card sm:col-span-2"><p class="empty">Nenhum motivo cadastrado.</p></div>@endforelse</div>
    {{ $motivos->links() }}
</x-layout>
