@props(['name' => 'grid'])
<svg {{ $attributes->class(['size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('leaf')<path d="M20 4C9 2 3 7 5 14c2 7 15 5 15-10Z"/><path d="M4 21 15 10"/>@break
        @case('herd')<path d="M5 7 2 4v6l3 2m14-5 3-3v6l-3 2M5 7h14v10l-4 4H9l-4-4Z"/><path d="M8 12h.01M16 12h.01M9 17h6"/>@break
        @case('scale')<rect x="3" y="4" width="18" height="17" rx="4"/><path d="M8 8h8l-4 5Z"/>@break
        @case('health')<path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6Z"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4m-8 8h2m4 0h2"/>@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
        @case('close')<path d="m6 6 12 12M18 6 6 18"/>@break
        @case('map')<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Zm6-3v15m6-12v15"/>@break
        @case('reproduction')<circle cx="6" cy="6" r="3"/><circle cx="18" cy="6" r="3"/><circle cx="12" cy="19" r="3"/><path d="M6 9v2a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9m-6 4v3"/>@break
        @case('wallet')<path d="M20 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-2M3 8h17a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-5a4 4 0 0 1 0-8m1 4h.01"/>@break
        @case('team')<circle cx="9" cy="7" r="3"/><path d="M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2m2-16a3 3 0 0 1 0 6m4 10v-2a4 4 0 0 0-3-4"/>@break
        @case('logout')<path d="M10 17l5-5-5-5m5 5H3m9-8h7a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-7"/>@break
        @default<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>
    @endswitch
</svg>
