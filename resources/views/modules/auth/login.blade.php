<x-layout title="Entrar">
    <div class="mx-auto max-w-sm pt-8 sm:pt-16">
        <div class="mb-8"><p class="mb-3 text-xs font-bold uppercase tracking-widest text-emerald-800">Sua fazenda, na palma da mão</p><h1 class="text-3xl font-bold tracking-tight">Bem-vindo ao campo.</h1><p class="mt-3 text-stone-500">Entre para acompanhar o rebanho e registrar o manejo do dia.</p></div>
        <form method="POST" action="{{ route('login.store') }}" class="card space-y-5">
            @csrf
            <x-field name="email" type="email" label="E-mail" required autocomplete="username" autofocus maxlength="255" />
            <x-field name="password" type="password" label="Senha" required autocomplete="current-password" />
            <label class="flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-5 accent-emerald-800">Manter conectado</label>
            <button class="btn w-full" type="submit">Entrar na fazenda</button>
        </form>
        <p class="mt-5 text-center text-sm text-stone-600">Ainda não tem conta? <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center font-semibold text-emerald-800 underline">Criar minha conta</a></p>
    </div>
</x-layout>
