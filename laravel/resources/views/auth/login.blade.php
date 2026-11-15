<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d1629">
    <title>Iniciar sesión — {{ config('business.name', 'Studio & Progetto') }}</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/login-responsive.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/login-overflow.css') }}?v=2">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
    <script src="{{ asset('js/login-scene.js') }}?v=2" defer></script>
    <script src="{{ asset('js/login.js') }}?v=1" defer></script>
<link rel="stylesheet" href="{{ asset('css/scrollbars.css') }}?v={{ filemtime(public_path('css/scrollbars.css')) }}">
</head>
<body class="login-page">
<div class="login-layout">
    <section class="showcase" aria-label="Presentación de Studio & Progetto">
        <div class="showcase-glow showcase-glow-one"></div>
        <div class="showcase-glow showcase-glow-two"></div>

        <header class="showcase-header">
            <div class="environment-badge"><span class="status-dot" aria-hidden="true"></span><span>Gestión comercial inteligente</span></div>
            <div class="scene-hint">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7 2l1 3M5 8l-3-1m12-3-2 2"/></svg>
                <span>Studio, diseño e inventario</span>
            </div>
        </header>

        <div id="three-container" class="three-container" aria-hidden="true"></div>

        <svg class="store-scene" viewBox="0 0 720 580" role="img" aria-label="Ilustración de una tienda y estanterías de inventario">
            <defs>
                <linearGradient id="storeGold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fde68a"/><stop offset=".52" stop-color="#f59e0b"/><stop offset="1" stop-color="#b45309"/></linearGradient>
                <linearGradient id="glassGlow" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#60a5fa" stop-opacity=".32"/><stop offset="1" stop-color="#14213d" stop-opacity=".2"/></linearGradient>
                <filter id="softShadow" x="-.3" y="-.3" width="1.6" height="1.7"><feGaussianBlur in="SourceAlpha" stdDeviation="8"/><feOffset dy="12"/><feComponentTransfer><feFuncA type="linear" slope=".38"/></feComponentTransfer><feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge></filter>
            </defs>
            <g class="scene-grid"><path d="M65 465 355 320l300 150M105 486l290-147m-230 178 290-147m-230 178 290-147M180 406l290 148m-230-173 290 148m-230-173 290 148"/></g>
            <ellipse cx="375" cy="467" rx="260" ry="48" fill="#030914" opacity=".55"/>
            <g filter="url(#softShadow)">
                <path class="scene-floor" d="m155 395 254-136 220 119-257 141z"/>
                <path class="store-side" d="m183 219 153-78v183l-153 83z"/>
                <path class="store-back" d="m336 141 227 91v186l-227-94z"/>
                <path class="store-roof" d="m155 201 179-91 234 95-172 104z"/>
                <path class="window-glass" d="m365 205 72 29v111l-72-30z"/>
                <path class="window-glass" d="m450 239 88 36v111l-88-36z"/>
                <path class="scene-line" d="m365 261 72 29m-72 27 72 30m-72-86v111m85-77 88 36m-88 27 88 36m-88-86v111"/>
                <path class="scene-roof" d="m145 195 196-100 244 98-189 108z"/>
                <path d="m187 174 32-52 321 0 54 68-380 18z" fill="#101b33" stroke="#405476" stroke-width="2"/>
                <path d="m219 122 48 0-19 85-61 1z" fill="#047857"/><path d="m267 122 48 0-7 83-60 2z" fill="#e2e8f0"/>
                <path d="m315 122 48 0 5 81-60 2z" fill="#059669"/><path d="m363 122 48 0 17 79-61 2z" fill="#e2e8f0"/>
                <path d="m411 122 48 0 29 77-61 2z" fill="#047857"/><path d="m459 122 48 0 42 75-61 2z" fill="#e2e8f0"/>
                <path d="m187 207 61-1v15c0 12-15 19-30 19s-31-8-31-20z" fill="#065f46"/><path d="m248 206 60-2v16c0 12-14 19-30 19s-30-8-30-20z" fill="#94a3b8"/>
                <path d="m308 204 60-2v16c0 12-14 19-30 19s-30-8-30-20z" fill="#065f46"/><path d="m368 202 61-2v16c0 12-15 19-30 19s-31-8-31-20z" fill="#94a3b8"/>
                <path d="m429 200 59-1v16c0 12-14 19-29 19s-30-8-30-20z" fill="#065f46"/><path d="m488 199 61-2v15c0 12-15 19-30 19s-31-8-31-20z" fill="#94a3b8"/>
                <path class="shelf" d="m205 254 110-56 2 14-111 58z"/><path class="shelf" d="m205 312 110-56 2 14-111 58z"/><path class="shelf" d="m205 369 110-56 2 14-111 58z"/>
                <path class="product-box product-gold" d="m218 242 24-12v29l-24 13z"/><path class="product-box product-cream" d="m249 226 27-14v31l-27 14z"/><path class="product-box product-green" d="m281 211 22-11v30l-22 12z"/>
                <path class="product-box product-cream" d="m218 300 24-12v28l-24 13z"/><path class="product-box product-green" d="m249 284 27-14v30l-27 14z"/><path class="product-box product-gold" d="m281 268 22-11v30l-22 12z"/>
                <path class="product-box product-green" d="m218 357 24-12v28l-24 13z"/><path class="product-box product-gold" d="m249 341 27-14v30l-27 14z"/><path class="product-box product-cream" d="m281 325 22-11v30l-22 12z"/>
                <path class="counter" d="m376 352 115 47v48l-115-48z"/><path class="counter" d="m491 399 53-27v48l-53 27z"/><path class="counter-top" d="m360 343 126-63 72 29-126 65z"/>
            </g>
            <g class="scene-tool"><path d="m500 115 66 0-66 66z"/><path d="m513 128 25 0-25 25z" fill="#14213d" stroke="none"/></g>
            <g class="scene-pencil" transform="rotate(24 581 182)"><rect x="574" y="139" width="14" height="63" rx="3"/><path d="m574 202 7 19 7-19z" fill="#fde68a"/><rect x="574" y="132" width="14" height="12" rx="3" fill="#f43f5e"/></g>
            <circle class="scene-particle" cx="127" cy="163" r="4"/><circle class="scene-particle" cx="584" cy="293" r="3"/><circle class="scene-particle" cx="407" cy="73" r="3"/><circle class="scene-particle" cx="113" cy="326" r="2.5"/><circle class="scene-particle" cx="616" cy="126" r="3.5"/>
        </svg>

        <div class="showcase-copy">
            <span class="showcase-kicker">PRECISIÓN COMERCIAL Y ARQUITECTÓNICA</span>
            <h2>Control inteligente de <span>ventas e inventario</span>.</h2>
            <p>Gestiona existencias, catálogo y operaciones comerciales desde un solo lugar.</p>
        </div>
    </section>

    <main class="login-panel">
        <section id="login-card" class="login-card" aria-labelledby="login-title">
            <header class="brand-block">
                <div class="brand-emblem"><img src="{{ route('brand.logo') }}" alt="" width="48" height="48"></div>
                <h1 id="login-title">{{ config('business.name', 'Studio & Progetto') }}</h1>
                <p>Sistema de Gestión de Ventas e Inventario</p>
            </header>

            @if ($errors->any())
                <div class="login-alert" role="alert" aria-live="assertive">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form id="login-form" class="login-form" method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="field">
                    <label for="username"><svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>Usuario</label>
                    <div class="input-wrap">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" maxlength="50" placeholder="Nombre de usuario" required autofocus>
                    </div>
                </div>

                <div class="field">
                    <label for="password"><svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>Contraseña</label>
                    <div class="input-wrap">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Ingresa tu contraseña" required>
                        <button type="button" id="toggle-password" class="eye-toggle" aria-label="Mostrar contraseña" aria-pressed="false">
                            <svg id="eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eye-closed" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <div class="login-actions">
                    <button id="submit-btn" class="login-button" type="submit">
                        <svg class="submit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 16l-4-4m0 0 4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <svg class="spinner" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle opacity=".25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path opacity=".75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span id="submit-text">Ingresar al sistema</span>
                    </button>
                    <a class="catalog-link" href="{{ route('catalog.index') }}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0 7-7m-7 7h18"/></svg>
                        <span>Volver al catálogo</span>
                    </a>
                </div>
            </form>
        </section>

        <footer class="login-footer">&copy; {{ date('Y') }} <strong>{{ config('business.name', 'Studio & Progetto') }}</strong>. Todos los derechos reservados.</footer>
    </main>
</div>
</body>
</html>
