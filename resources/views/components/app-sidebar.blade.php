@props(['mobile' => false])

<div class="flex h-full min-h-0 flex-col bg-white">
    <div class="flex min-h-16 shrink-0 items-center justify-between gap-2 border-b border-stone-100 px-4">
        <x-app-brand />
        @if($mobile)
            <form method="dialog">
                <button type="submit" autofocus aria-label="Fechar menu" class="flex size-11 items-center justify-center rounded-xl text-stone-600 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-emerald-700">
                    <x-icon name="close" />
                </button>
            </form>
        @endif
    </div>
    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
        <x-app-navigation />
    </div>
    <div class="shrink-0 border-t border-stone-200 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
        <div class="mb-2 min-w-0 px-1">
            <p class="truncate text-sm font-semibold text-stone-900">{{ auth()->user()->name }}</p>
            <p class="text-xs text-stone-500">{{ auth()->user()->role }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-xl px-2 text-left text-sm font-semibold text-stone-600 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-emerald-700">
                <x-icon name="logout" />Sair
            </button>
        </form>
    </div>
</div>
