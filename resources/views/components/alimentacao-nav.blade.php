<nav aria-label="Alimentação" class="flex flex-wrap gap-2">
    @foreach([['refeicoes.index', 'refeicoes.*', 'Refeições'], ['alimentos.index', 'alimentos.*', 'Alimentos'], ['tipos-alimento.index', 'tipos-alimento.*', 'Tipos de alimento']] as [$route, $pattern, $label])
        <a href="{{ route($route) }}" class="btn-secondary {{ request()->routeIs($pattern) ? 'border-emerald-700 bg-emerald-50 text-emerald-900' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
