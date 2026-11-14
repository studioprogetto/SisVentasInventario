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
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('business.name', 'LA FRENTONA MAS FRENTONA'))</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    screens: {
                        '3xl': '2000px',
                    }
                }
            }
        }
    </script>

    <style>
        /* ==========================================================================
           TOKENS NEOMÓRFICOS DUALES (LIGHT #e6ebf4 / DARK #0a1120)
           ========================================================================== */
        :root, [data-theme="light"] {
            --bg-canvas: #e6ebf4;
            --bg-surface: #e9eef7;
            --bg-surface-alt: #dfe6f2;
            --bg-inset: #dfe5f0;
            --shadow-dark: rgba(163, 177, 198, 0.68);
            --shadow-light: rgba(255, 255, 255, 0.95);
            --inset-dark: rgba(163, 177, 198, 0.74);
            --inset-light: rgba(255, 255, 255, 0.96);
            --border-subtle: rgba(255, 255, 255, 0.72);
            --border-hairline: rgba(148, 163, 184, 0.25);
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --accent-gold: #d97706;
            --accent-blue: #2563eb;
            --sidebar-bg: #111c33;
        }

        [data-theme="dark"] {
            --bg-canvas: #070d19;
            --bg-surface: #0a1120;
            --bg-surface-alt: #0f182d;
            --bg-inset: #050a14;
            --shadow-dark: rgba(2, 5, 12, 0.92);
            --shadow-light: rgba(22, 36, 64, 0.45);
            --inset-dark: rgba(2, 5, 12, 0.95);
            --inset-light: rgba(20, 33, 58, 0.42);
            --border-subtle: rgba(255, 255, 255, 0.05);
            --border-hairline: rgba(255, 255, 255, 0.07);
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --accent-gold: #fbbf24;
            --accent-blue: #60a5fa;
            --sidebar-bg: #050a14;
        }

        body {
            background-color: var(--bg-canvas);
            color: var(--text-primary);
            transition: background-color 0.35s ease, color 0.35s ease;
            overflow-x: hidden;
        }

        .neo-card {
            background: var(--bg-surface);
            box-shadow:
                10px 10px 22px var(--shadow-dark),
                -10px -10px 22px var(--shadow-light),
                inset 1px 1px 1px var(--border-subtle);
            border: 1px solid var(--border-hairline);
            transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.25s ease;
        }

        .neo-card-hover:hover {
            transform: translateY(-3px);
            box-shadow:
                14px 14px 28px var(--shadow-dark),
                -12px -12px 24px var(--shadow-light),
                inset 1px 1px 1px var(--border-subtle);
        }

        .neo-card-sm {
            background: var(--bg-surface);
            box-shadow:
                5px 5px 11px var(--shadow-dark),
                -5px -5px 11px var(--shadow-light),
                inset 1px 1px 1px var(--border-subtle);
            border: 1px solid var(--border-hairline);
        }

        .neo-inset {
            background: var(--bg-inset);
            box-shadow:
                inset 4px 4px 8px var(--inset-dark),
                inset -4px -4px 8px var(--inset-light);
            border: 1px solid var(--border-hairline);
            color: var(--text-primary);
        }

        .neo-btn {
            background: var(--bg-surface);
            box-shadow:
                4px 4px 9px var(--shadow-dark),
                -4px -4px 9px var(--shadow-light),
                inset 1px 1px 1px var(--border-subtle);
            border: 1px solid var(--border-hairline);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .neo-btn:hover {
            transform: translateY(-1px);
            color: var(--accent-blue);
        }

        .neo-btn:active, .neo-btn-active {
            transform: translateY(0px);
            box-shadow:
                inset 3px 3px 7px var(--inset-dark),
                inset -3px -3px 7px var(--inset-light);
            color: var(--accent-blue);
        }

        .neo-btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow:
                6px 6px 14px var(--shadow-dark),
                -4px -4px 10px var(--shadow-light),
                inset 0 1px 1px rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .neo-btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #050a14;
            box-shadow:
                6px 6px 14px var(--shadow-dark),
                -4px -4px 10px var(--shadow-light),
                inset 0 1px 1px rgba(255, 255, 255, 0.4);
        }

        .neo-btn-primary:hover {
            filter: brightness(1.08);
            transform: translateY(-1.5px);
        }

        .custom-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.25);
            border-radius: 999px;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex font-sans antialiased selection:bg-blue-500/20 selection:text-blue-600">

    <!-- Overlay móvil lateral -->
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs hidden lg:hidden"></div>

    @include('layouts.sidebar')

    <!-- CONTENEDOR PRINCIPAL WIDESCREEN -->
    <div class="flex-1 flex flex-col min-w-0">
        @include('layouts.topbar')

        <main class="flex-1 max-w-[1840px] w-full mx-auto px-5 sm:px-8 lg:px-10 py-6 sm:py-8 space-y-6">
            @if(session('status'))
                <div class="neo-card rounded-2xl p-4 text-sm text-emerald-600 dark:text-emerald-400 border-l-4 border-emerald-500">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="neo-card rounded-2xl p-4 text-sm text-rose-600 dark:text-rose-400 border-l-4 border-rose-500">
                    <strong>Revisa los datos:</strong>
                    <ul class="list-disc list-inside mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="max-w-[1840px] w-full mx-auto px-5 sm:px-8 lg:px-10 py-6 text-center text-xs text-[var(--text-muted)]">
            <span>{{ config('business.name', 'Studio & Progetto') }}</span> &bull; <span>Gestión que acompaña tu día.</span>
        </footer>
    </div>

    <!-- NOTIFICACIÓN TOAST -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 hidden neo-card px-4 py-3 rounded-2xl text-xs font-semibold flex items-center gap-2.5 border border-blue-500/30 dark:border-amber-500/30 shadow-2xl">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 shrink-0"></i>
        <span id="toast-message">Acción completada con éxito</span>
    </div>

    <script>
        // Inicializar iconos de Lucide
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                lucide.createIcons();
            }
        });

        // Función global para toast
        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').textContent = msg;
            toast.classList.remove('hidden');
            clearTimeout(window._toastTimer);
            window._toastTimer = setTimeout(() => toast.classList.add('hidden'), 3200);
        }
    </script>
    @stack('scripts')
</body>
</html>
