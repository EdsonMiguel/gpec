@php
    $links = [
        ['sanidade.index', 'sanidade.*', 'Aplicações'],
        ['casos-clinicos.index', 'casos-clinicos.*', 'Prontuário'],
    ];

    if (auth()->user()->can('administrar')) {
        $links[] = ['doencas.index', 'doencas.*', 'Doenças'];
        $links[] = ['medicamentos.index', 'medicamentos.*', 'Medicamentos'];
    }
@endphp
<nav aria-label="Sanidade" class="flex flex-wrap gap-2">
    @foreach($links as [$route, $pattern, $label])
        <a href="{{ route($route) }}" class="btn-secondary {{ request()->routeIs($pattern) ? 'border-emerald-700 bg-emerald-50 text-emerald-900' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
