<x-layout title="Criar conta">
    <div class="mx-auto max-w-sm pt-6 sm:pt-12">
        <div class="mb-6">
            <h1 class="text-3xl font-bold tracking-tight">Comece no GPec</h1>
            <p class="mt-3 text-stone-500">Crie sua conta para administrar sua fazenda. Você poderá adicionar sua equipe depois.</p>
        </div>
        <form method="POST" action="{{ route('register.store') }}" class="card space-y-5">
            @csrf
            <x-field name="nome_fazenda" label="Nome da fazenda ou organização" required maxlength="150" autocomplete="organization" autofocus />
            <x-field name="name" label="Seu nome" required maxlength="255" autocomplete="name" />
            <x-field name="email" type="email" label="E-mail" required maxlength="255" autocomplete="email" />
            <div>
                <x-field name="password" type="password" label="Senha" required minlength="12" autocomplete="new-password" aria-describedby="password-help" />
                <p id="password-help" class="mt-2 text-xs text-stone-500">Use pelo menos 12 caracteres.</p>
            </div>
            <x-field name="password_confirmation" type="password" label="Confirme a senha" required minlength="12" autocomplete="new-password" />
            <button class="btn w-full" type="submit">Criar minha conta</button>
        </form>
        <p class="mt-5 text-center text-sm text-stone-600">Já tem conta? <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold text-emerald-800 underline">Entrar</a></p>
    </div>
</x-layout>
