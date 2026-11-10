<nav aria-label="Rebanho" class="flex flex-wrap gap-2">
    @foreach([['animais.index', 'animais.*', 'Animais'], ['lotes.index', 'lotes.*', 'Lotes']] as [$route, $pattern, $label])
        <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif class="btn-secondary {{ request()->routeIs($pattern) ? 'border-emerald-700 bg-emerald-50 text-emerald-900' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
