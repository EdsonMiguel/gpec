<x-layout title="Equipe">
    <x-page-heading title="Equipe da fazenda" eyebrow="ADMINISTRAÇÃO"><a class="btn-secondary" href="{{ route('dashboard') }}">Voltar</a></x-page-heading>
    <form method="POST" action="{{ route('usuarios.store') }}" class="card space-y-5">@csrf<h2 class="section-title">Novo acesso</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="name" label="Nome" required maxlength="255" autocomplete="name"/>
            <x-field name="email" label="E-mail" type="email" required maxlength="255" autocomplete="off"/>
            <x-select name="role" label="Papel" required>@foreach(['Vaqueiro', 'Admin'] as $role)<option value="{{ $role }}" @selected(old('role', 'Vaqueiro') === $role)>{{ $role }}</option>@endforeach</x-select>
            <div class="hidden sm:block"></div>
            <x-field name="password" label="Senha inicial (mínimo 12 caracteres)" type="password" required minlength="12" autocomplete="new-password"/>
            <x-field name="password_confirmation" label="Confirmar senha" type="password" required minlength="12" autocomplete="new-password"/>
        </div><button type="submit" class="btn">Criar acesso</button>
    </form>
    <section class="card"><h2 class="section-title mb-3">Pessoas com acesso</h2>@foreach($users as $user)<article class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 py-4"><div class="min-w-0"><h3 class="text-sm font-semibold">{{ $user->name }}</h3><p class="break-all text-xs text-stone-500">{{ $user->email }}</p></div><span class="badge">{{ $user->role }}</span></article>@endforeach<div class="mt-4">{{ $users->links() }}</div></section>
</x-layout>
