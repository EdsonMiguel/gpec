@props(['shortcuts' => false])

@php
    $sections = [
        'Início' => [
            ['dashboard', 'Visão geral', 'grid', ['dashboard'], true],
            ['agenda.index', 'Agenda', 'calendar', ['agenda.*'], false],
        ],
        'Operação' => [
            ['animais.index', 'Rebanho', 'herd', ['animais.*', 'lotes.*'], true],
            ['pesagens.index', 'Pesagem', 'scale', ['pesagens.*', 'motivos-pesagem.*'], true],
            ['sanidade.index', 'Sanidade', 'health', ['sanidade.*', 'medicamentos.*', 'casos-clinicos.*', 'doencas.*'], true],
            ['refeicoes.index', 'Refeições', 'leaf', ['refeicoes.*', 'alimentos.*', 'tipos-alimento.*', 'alimentacao.*'], true],
        ],
        'Gestão' => [
            ['fazendas.index', 'Fazendas e áreas', 'map', ['fazendas.*', 'areas.*'], false],
        ],
    ];

    if (auth()->user()->can('registrar-manejo')) {
        $sections['Operação'][] = ['gestacoes.index', 'Reprodução', 'reproduction', ['gestacoes.*'], false];
    }

    if (auth()->user()->can('administrar')) {
        $sections['Gestão'][] = ['transacoes.index', 'Financeiro', 'wallet', ['financeiro.*', 'transacoes.*', 'contas-orcamentarias.*', 'produtos.*'], false];
        $sections['Gestão'][] = ['usuarios.index', 'Equipe', 'team', ['usuarios.*'], false];
    }
@endphp

<nav aria-label="{{ $shortcuts ? 'Atalhos principais' : 'Módulos do GPec' }}" @class([
    'grid grid-cols-5 px-1' => $shortcuts,
    'space-y-5 px-3 py-4' => ! $shortcuts,
])>
    @foreach($sections as $section => $links)
        @unless($shortcuts)
            <div class="space-y-1">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-stone-500">{{ $section }}</p>
        @endunless
        @foreach($links as [$route, $label, $icon, $patterns, $shortcut])
            @continue($shortcuts && ! $shortcut)
            @php($active = request()->routeIs(...$patterns))
            <a href="{{ route($route) }}" @if($active) aria-current="page" @endif @class([
                'flex items-center font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-emerald-700',
                'min-h-16 flex-col justify-center gap-1 rounded-lg px-0.5 text-center text-[11px]' => $shortcuts,
                'min-h-11 gap-3 rounded-xl px-3 py-2 text-left text-sm' => ! $shortcuts,
                'bg-emerald-100 text-emerald-950' => $active,
                'text-stone-600 hover:bg-stone-100 hover:text-stone-950' => ! $active,
            ])><x-icon :name="$icon" />{{ $label }}</a>
        @endforeach
        @unless($shortcuts)
            </div>
        @endunless
    @endforeach
</nav>
