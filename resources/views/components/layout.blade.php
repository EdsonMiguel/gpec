@props(['title' => 'Visão geral'])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#164e3c">
    <link rel="manifest" href="{{ url('manifest.json') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-title" content="GPec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-stone-50 text-stone-900 antialiased">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-0 focus:z-50 focus:bg-white focus:p-4">Ir para o conteúdo</a>
    <div @class(['min-h-dvh', 'lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]' => auth()->check()])>
        @auth
            <aside aria-label="Barra lateral" class="sticky top-0 hidden h-dvh self-start border-r border-stone-200 lg:block">
                <x-app-sidebar />
            </aside>
        @endauth

        <div class="min-w-0">
            <header class="sticky top-0 z-30 border-b border-stone-200 bg-white/95 backdrop-blur">
                <div class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-3 px-3 sm:px-6">
                    @auth
                        <div class="flex shrink-0 items-center gap-1.5 lg:hidden">
                            <button type="button" data-nav-toggle aria-label="Abrir menu" aria-haspopup="dialog" aria-expanded="false" aria-controls="mobile-navigation" class="flex size-11 items-center justify-center rounded-xl text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-emerald-700">
                                <x-icon name="menu" />
                            </button>
                            <x-app-brand />
                        </div>
                        <p class="hidden min-w-0 truncate text-sm font-semibold text-stone-700 lg:block">{{ $title }}</p>
                        <p class="min-w-0 truncate rounded-lg bg-stone-100 px-2.5 py-1.5 text-right text-xs font-semibold text-stone-700 sm:px-3" title="{{ auth()->user()->tenant->nome }}">{{ auth()->user()->tenant->nome }}</p>
                    @else
                        <x-app-brand />
                    @endauth
                </div>
            </header>

            <main id="conteudo" tabindex="-1" @class([
                'mx-auto space-y-6 px-4 pt-6 sm:px-6 lg:pt-8',
                'max-w-6xl pb-[calc(6rem+env(safe-area-inset-bottom))] lg:pb-12' => auth()->check(),
                'max-w-5xl pb-12' => auth()->guest(),
            ])>
                <div data-location-notice hidden class="rounded-2xl border border-stone-200 bg-white p-3 text-xs text-stone-600" role="status"><span data-location-message></span><button type="button" data-location-retry class="ml-2 min-h-11 font-semibold text-emerald-800">Tentar localização</button></div>
                @if(session('success'))<div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('success') }}</div>@endif
                @if($errors->any())
                    <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" tabindex="-1">
                        <p class="font-bold">Revise os dados informados:</p>
                        <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>

    @auth
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-stone-200 bg-white pt-1 pb-[env(safe-area-inset-bottom)] lg:hidden">
            <div class="mx-auto max-w-lg"><x-app-navigation shortcuts /></div>
        </div>
        <dialog id="mobile-navigation" data-nav-drawer aria-label="Menu de navegação" class="navigation-drawer lg:hidden">
            <x-app-sidebar mobile />
        </dialog>
    @endauth
</body>
</html>

