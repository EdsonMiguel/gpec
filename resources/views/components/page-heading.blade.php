@props(['title', 'eyebrow' => 'NO CAMPO'])
<div class="flex flex-wrap items-center justify-between gap-3">
    <div><p class="mb-1 text-xs font-semibold uppercase tracking-widest text-emerald-800">{{ $eyebrow }}</p><h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $title }}</h1></div>
    {{ $slot }}
</div>
