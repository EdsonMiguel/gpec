<x-layout title="Doenças">
    <x-page-heading title="Cadastro de doenças" eyebrow="SANIDADE">
        <a href="{{ route('doencas.create') }}" class="btn">+ Nova doença</a>
    </x-page-heading>
    <x-sanidade-nav />
    <section class="card divide-y divide-stone-100">
        @forelse($doencas as $doenca)
            <article class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                <div class="min-w-0 flex-1">
                    <h2 class="break-words text-sm font-semibold">{{ $doenca->nome }}</h2>
                    @if($doenca->descricao)<p class="mt-1 whitespace-pre-line break-words text-sm text-stone-600">{{ $doenca->descricao }}</p>@endif
                    <span class="badge mt-2">{{ $doenca->ativo ? 'Ativa' : 'Inativa' }}</span>
                </div>
                <a href="{{ route('doencas.edit', $doenca) }}" class="btn-secondary">Editar</a>
            </article>
        @empty
            <p class="empty">Nenhuma doença cadastrada.</p>
        @endforelse
    </section>
    {{ $doencas->links() }}
</x-layout>
