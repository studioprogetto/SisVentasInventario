<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', config('business.name', 'Studio & Progetto'))</title>
    {{-- Aplica el tema guardado antes de pintar la página. --}}
    <script src="{{ asset('js/theme.js') }}?v=2"></script>
    <script defer src="{{ asset('js/vendor/vanilla-tilt.min.js') }}"></script>
    <script defer src="{{ asset('js/workspace.js') }}"></script>
    <script defer src="{{ asset('js/responsive.js') }}"></script>
    <script defer src="{{ asset('js/vendor/alpine-csp.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/tailwind-utilities.css') }}">
    <link rel="preconnect"  href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=9">
    <link rel="stylesheet" href="{{ asset('css/scrollbars.css') }}?v=1">
    <script src="{{ asset('js/app.js') }}?v=6" defer></script>
    @stack('styles')
    <style>
        /* Tokens compartidos: las páginas heredan el tema del documento. */
        :root, :root[data-theme="light"] {
            color-scheme: light;
            --bg: #e0e5ec; --surface: #e0e5ec; --raised: #e8edf3;
            --text: #1a2a44; --muted: #52627a; --line: #c1cbd9;
            --accent: #3456bb; --accent-soft: #d2ddf2;
            --green: #166b50; --green-soft: #cfe6dc;
            --amber: #875409; --red: #a72d46; --red-soft: #f0d5dc;
            --shadow-dark: #bec3c9; --shadow-light: #ffffff;
            --shadow: 8px 8px 20px var(--shadow-dark), -8px -8px 20px var(--shadow-light);
            --inset: inset 4px 4px 9px var(--shadow-dark), inset -4px -4px 9px var(--shadow-light);
            --sidebar: var(--surface); --inset-bg: var(--surface);
        }
        :root[data-theme="dark"] {
            color-scheme: dark;
            --bg: #1a2a44; --surface: #1a2a44; --raised: #223550;
            --text: #edf3ff; --muted: #b2c1d8; --line: #405574;
            --accent: #b0c5ff; --accent-soft: #2c4264;
            --green: #85dfbb; --green-soft: #24473f;
            --amber: #f0c780; --red: #ffacbb; --red-soft: #503343;
            --shadow-dark: #111c2d; --shadow-light: #23385b;
        }
        [x-cloak] { display: none !important; }
        body.admin-app { background: var(--bg); color: var(--text); }
        .admin-app .sidebar {
            inset: 18px auto 18px 18px; width: 252px; border-radius: 24px;
            background: var(--surface); color: var(--text); border: 1px solid var(--line);
            box-shadow: var(--shadow); scrollbar-color: var(--muted) transparent;
        }
        .admin-app .brand, .admin-app .sidebar .nav-link { color: var(--text); }
        .admin-app .brand small, .admin-app .nav-group,
        .admin-app .sidebar-footer { color: var(--muted); }
        .admin-app .sidebar .nav-link:hover { background: var(--raised); color: var(--accent); }
        .admin-app .sidebar .nav-link.is-active {
            background: var(--surface); color: var(--accent); box-shadow: var(--inset);
        }
        .admin-app .active-dot { background: var(--accent); }
        .admin-app .workspace { margin-left: 288px; padding: 18px 18px 0 0; }
        .admin-app .topbar {
            height: auto; min-height: 80px; padding: 16px 20px; flex-wrap: wrap;
            border: 1px solid var(--line); border-radius: 24px;
            background: var(--surface); box-shadow: var(--shadow);
        }
        .admin-app .topbar-context, .admin-app .topbar-actions { gap: 12px; }
        .admin-app .shell { padding: 26px 0; }
        .neo-card, .admin-app :is(.card, .panel, .stat-card, .executive-card) {
            background: var(--surface); color: var(--text); border: 1px solid var(--line);
            border-radius: 20px; box-shadow: var(--shadow);
        }
        .neo-button, .admin-app :is(.icon-button, .btn-secondary, .button-secondary) {
            background: var(--surface); color: var(--text); border: 1px solid var(--line);
            box-shadow: var(--shadow); border-radius: 14px;
        }
        .neo-button:active, .admin-app .icon-button:active { box-shadow: var(--inset); }
        .admin-app :is(input, select, textarea), .neo-inset {
            background: var(--surface); color: var(--text); border-color: var(--line);
            box-shadow: var(--inset);
        }
        .admin-app input::placeholder { color: var(--muted); opacity: 1; }
        .admin-app :is(input, select, textarea, button, a):focus-visible {
            outline: 3px solid var(--accent); outline-offset: 4px;
        }
        /* Neutraliza el tema oscuro fijo del dashboard existente. */
        .admin-app .studio-dashboard {
            --text: inherit; --muted: inherit; --accent: inherit; --green: inherit;
            --red: inherit; --line: inherit; --surface: inherit; --shadow: inherit;
            --inset-bg: inherit; color-scheme: inherit;
            background: var(--bg); color: var(--text); padding: 0;
        }
        .admin-app .studio-dashboard :is(input, .executive-ranking li, .executive-alerts li) {
            background: var(--surface); box-shadow: var(--inset); color: var(--text);
        }
        .admin-app .studio-dashboard button {
            background: var(--surface); color: var(--accent);
            border-color: var(--line); box-shadow: var(--shadow);
        }
        .admin-app .studio-dashboard .executive-track { background: var(--line); }
        .admin-app .studio-dashboard .executive-track > span {
            background: linear-gradient(90deg, var(--accent), var(--green));
        }
        .admin-app .studio-dashboard .executive-card:hover { border-color: var(--accent); }
        .neo-search { position: relative; flex: 1 1 240px; max-width: 420px; }
        .neo-search label { display: flex; align-items: center; gap: 10px; padding: 0 14px; border-radius: 14px; }
        .neo-search input { min-width: 0; border: 0; box-shadow: none; background: transparent; }
        .neo-search-results {
            position: absolute; inset: calc(100% + 12px) 0 auto; z-index: 50;
            padding: 10px; max-height: 300px; overflow-y: auto;
        }
        .neo-search-results a { display: block; padding: 10px; border-radius: 10px; color: var(--text); text-decoration: none; }
        .neo-search-results a:hover, .neo-search-results a:focus { background: var(--accent-soft); }
        .neo-search-results p { padding: 10px; margin: 0; color: var(--muted); }
        .neo-depth { filter: drop-shadow(2px 3px 2px var(--shadow-dark)); }
        .neo-tilt { transform: perspective(900px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)); transition: transform 180ms ease; }
        @media (min-width: 1024px) {
            .admin-app.sidebar-collapsed .workspace { margin-left: 124px; }
        }
        @media (max-width: 1023px) {
            .admin-app .sidebar { width: min(310px, 82vw); transform: translateX(calc(-100% - 36px)); }
            .admin-app .sidebar.is-open { transform: translateX(0); }
            .admin-app .workspace { margin-left: 0; padding: 14px; }
            .admin-app .breadcrumb { display: none; }
        }
        @media (max-width: 640px) {
            .neo-search { order: 3; flex-basis: 100%; max-width: none; }
            .admin-app .topbar { gap: 16px; padding: 14px; }
            .admin-app .workspace-footer { flex-direction: column; }
        }
        @media (prefers-reduced-motion: reduce) {
            .neo-tilt { transform: none !important; transition: none; }
        }
        .skip-link:not(:focus) { position:absolute; width:1px; height:1px; overflow:hidden; clip-path:inset(50%); }
        @media(min-width:1024px) {
            body.sidebar-collapsed .sidebar .nav-label { display:none; }
            body.sidebar-collapsed .sidebar .nav-link { width:100%; justify-content:center; }
        }
        body.admin-app .shell :is(button,.btn,.button,.inventory-action-link,.inventory-button,summary.btn) {
            --button-bg:#354fb0; --button-text:white;
            background:var(--button-bg); color:var(--button-text); border:1px solid transparent;
            border-radius:12px; padding:.55rem .85rem; min-height:36px; font-weight:600;
            text-decoration:none; box-shadow:2px 3px 6px var(--shadow-dark);
        }
        body.admin-app .shell :is(.btn-secondary,.button-secondary,.btn-ghost,.inventory-action-link,.inventory-button-soft,[data-close-product-modal]) {
            --button-bg:var(--raised); --button-text:var(--text); border-color:var(--line);
        }
        body.admin-app .shell :is(.btn-success,.button-success,.is-success,[data-open-product-modal="new-product-modal"]) { --button-bg:#126945; --button-text:white; }
        body.admin-app .shell :is(.btn-danger,.button-danger,.is-danger) { --button-bg:#ad2940; --button-text:white; }
        body.admin-app .shell button::before { content:none; }
        body.admin-app .shell button:disabled { opacity:.55; }
        .agency-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:18px; }
        .agency-kpis strong { display:block; font-size:1.8rem; }
        .agency-chart { position:relative; height:280px; }
    </style>

    <link rel="stylesheet" href="{{ asset('css/admin-records.css') }}">
<link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}">

<link rel="stylesheet" href="{{ asset('css/lectura.css') }}?v={{ filemtime(public_path('css/lectura.css')) }}">
</head>
<body class="admin-app">
<a class="skip-link" href="#content">Saltar al contenido</a>
{{-- Conserva los módulos, rutas y permisos de la aplicación. --}}
@include('layouts.sidebar')
<button type="button" class="nav-backdrop" data-close-nav aria-label="Cerrar menú" aria-hidden="true" tabindex="-1"></button>
<div class="workspace">
    <header class="topbar">
        <div class="topbar-context">
            <button type="button" id="nav-toggle" class="icon-button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-nav"><x-icon name="menu" /></button>
            <span class="breadcrumb">Espacio de trabajo <span>/</span> <strong data-page-title>Administración</strong></span>
        </div>
        <div class="neo-search" x-data="moduleSearch">
            <label class="neo-inset"><x-icon name="search"/><input type="search" aria-label="Buscar módulos" placeholder="Buscar módulos…" autocomplete="off" data-module-search></label>
            <div class="neo-search-results neo-card" data-module-results hidden></div>
        </div>
        <div class="topbar-actions">
            @php($unread = app(\App\Services\Notifications\Inbox::class)->unread(auth()->user()))
            <a class="icon-button notification-link" href="{{ route('notifications.index') }}" aria-label="Notificaciones: {{ $unread }}" title="Notificaciones">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 17h14l-2-3V9a5 5 0 0 0-10 0v5zM10 20a2 2 0 0 0 4 0"/></svg>
                @if($unread)<span>{{ $unread }}</span>@endif
            </a>
            <x-theme-toggle />
            <a class="user-profile" href="{{ route('profile.index') }}">
                <x-profile-avatar />
                <span class="user-meta"><strong>{{ auth()->user()->nombre_completo }}</strong><small>Gestionar perfil</small></span>
            </a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button logout-button" type="submit" aria-label="Cerrar sesión"><x-icon name="logout" /></button></form>
        </div>
    </header>
    @if(auth()->user()->fecha_nacimiento && substr(auth()->user()->fecha_nacimiento, 5) === now()->format('m-d'))
        <aside class="birthday-banner"><strong>¡Hoy es tu cumpleaños, {{ auth()->user()->nombres ?: auth()->user()->nombre_completo }}!</strong> Tu equipo te desea un feliz día.</aside>
    @endif
    <main class="shell w-full max-w-full overflow-x-hidden" id="content" tabindex="-1">
        @if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
        @if(session('operation.id_venta'))
            <div class="sale-toast is-visible" data-sale-toast data-ticket-url="{{ route('sales.ticket', session('operation.id_venta')) }}" role="status" aria-live="polite">
                <div class="sale-toast__header">
                    <span class="sale-toast__badge">Venta registrada</span>
                    <button type="button" class="sale-toast__close" data-close-sale-toast aria-label="Cerrar notificación">×</button>
                </div>
                <div class="sale-toast__content"><strong>Ticket #{{ session('operation.id_venta') }}</strong><p>Total: {{ session('operation.total') }}</p></div>
                <div class="sale-toast__actions">
                    <button type="button" data-open-ticket>Ver ticket</button>
                    <button type="button" class="button-secondary" data-close-sale-toast>Cerrar</button>
                </div>
            </div>
        @endif
        @if($errors->any())
            <div class="neo-card p-4 mb-4" role="alert" tabindex="-1"><strong>Revisa los datos</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
        <footer class="workspace-footer"><div><x-icon name="box"/> <strong>{{ config('business.name', 'Studio & Progetto') }}</strong><small>Gestión de ventas e inventario</small></div><a href="{{ route('profile.index') }}">Gestionar mi perfil →</a></footer>
    </main>
</div>
@stack('scripts')
</body>
</html>
