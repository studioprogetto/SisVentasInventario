@props(['name' => 'grid'])
@php
$paths = [
'grid'=>'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
'box'=>'m3 7 9-4 9 4v10l-9 4-9-4V7Zm0 0 9 4 9-4M12 11v10M7.5 5l9 4',
'cart'=>'M3 3h2l3 12h11l2-8H6M9 20h.01M18 20h.01',
'users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M17 4a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
'chart'=>'M3 3v18h18M7 15l4-5 4 3 6-8',
'wallet'=>'M20 8V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h16v11H5a3 3 0 0 1-3-3V6M16 13h5v4h-5z',
'swap'=>'M4 7h16m-4-4 4 4-4 4M20 17H4m4-4-4 4 4 4',
'file'=>'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8M8 17h5',
'tag'=>'m3 3 8 0 10 10-8 8L3 11V3ZM7 7h.01',
'sun'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1 1M18 18l1 1M5 19l1-1M18 6l1-1',
'moon'=>'M21 12.8A9 9 0 0 1 11.2 3 9 9 0 1 0 21 12.8Z',
'menu'=>'M4 6h16M4 12h16M4 18h16',
'search'=>'M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16ZM21 21l-4.35-4.35',
'filter'=>'M4 5h16M7 12h10m-7 7h4',
'arrow'=>'M5 12h14m-5-5 5 5-5 5',
'shield'=>'m12 3 9 4v6c0 5-9 9-9 9s-9-4-9-9V7l9-4Zm-4 9 3 3 5-6',
'logout'=>'M9 4H4v16h5M10 12h11m-4-4 4 4-4 4',
'clock'=>'M12 8v4l3 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
'alert'=>'m12 3 10 18H2L12 3Zm0 6v5m0 3h.01',
];
@endphp
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['grid'] }}"/></svg>
