<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', config('business.name'))</title>
    <script src="{{ asset('js/theme.js') }}?v=2"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=9">
    <link rel="stylesheet" href="{{ asset('css/scrollbars.css') }}?v=1">
    <script src="{{ asset('js/app.js') }}?v=6" defer></script>
    @stack('styles')
</head>
<body class="admin-app">
<a class="skip-link" href="#content">Saltar al contenido</a>
@include('layouts.sidebar')
<button type="button" class="nav-backdrop" data-close-nav aria-label="Cerrar menú"></button>
<div class="workspace">
    @include('layouts.topbar')
    <main class="shell" id="content" tabindex="-1">
        @if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
        @if(session('operation.id_venta'))
            <div class="sale-toast is-visible" data-sale-toast data-ticket-url="{{ route('sales.ticket', session('operation.id_venta')) }}" role="status" aria-live="polite">
                <div class="sale-toast__header">
                    <span class="sale-toast__badge">Venta registrada</span>
                    <button type="button" class="sale-toast__close" data-close-sale-toast aria-label="Cerrar notificación">×</button>
                </div>
                <div class="sale-toast__content">
                    <strong>Ticket #{{ session('operation.id_venta') }}</strong>
                    <p>Total: {{ session('operation.total') }}</p>
                </div>
                <div class="sale-toast__actions">
                    <button type="button" data-open-ticket>Ver ticket</button>
                    <button type="button" class="button-secondary" data-close-sale-toast>Cerrar</button>
                </div>
            </div>
        @endif
        @if($errors->any())<div role="alert" tabindex="-1"><strong>Revisa los datos</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
        <footer class="workspace-footer"><span>{{ config('business.name', 'Studio & Progetto') }}</span><span>Gestión que acompaña tu día.</span></footer>
    </main>
</div>
@stack('scripts')
</body>
</html>