<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" {{ $attributes->class(['flex w-fit items-center gap-2.5 rounded-xl font-bold tracking-tight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700']) }}>
    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-900 text-white"><x-icon name="leaf" /></span>
    <span class="leading-tight">
        {{ config('app.name') }}
        <span class="block text-[10px] font-medium uppercase tracking-widest text-stone-500">Gestão pecuária</span>
    </span>
</a>
