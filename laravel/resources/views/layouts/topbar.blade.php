<header class="topbar">
    <div class="topbar-context"><button type="button" id="nav-toggle" class="icon-button" aria-label="Menú" aria-expanded="false" aria-controls="main-nav"><x-icon name="menu"/></button><span class="breadcrumb">Espacio de trabajo <span>/</span> <strong data-page-title>Administración</strong></span></div>
    <div class="topbar-actions"><x-theme-toggle/><a class="user-profile" href="{{ route('profile.index') }}"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre_completo,0,1)) }}</span><span class="user-meta"><strong>{{ auth()->user()->nombre_completo }}</strong><small>Mi perfil</small></span></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button logout-button" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><x-icon name="logout"/></button></form></div>
</header>

<!-- ======================================================================
     HEADER / TOPBAR
     ====================================================================== -->
<header class="sticky top-0 z-30 backdrop-blur-md bg-[var(--bg-canvas)]/85 border-b border-[var(--border-hairline)]">
    <div class="max-w-[1840px] w-full mx-auto px-5 sm:px-8 lg:px-10 h-16 sm:h-[74px] flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle-btn" class="neo-btn w-10 h-10 rounded-xl flex items-center justify-center cursor-pointer shrink-0">
                <i data-lucide="menu" class="w-4 h-4"></i>
            </button>
            <nav class="flex items-center gap-2 text-xs sm:text-sm">
                <span class="hidden sm:inline text-[var(--text-muted)] font-medium">{{ config('business.name', 'Studio & Progetto') }}</span>
                <span class="hidden sm:inline text-[var(--text-muted)]/50">/</span>
                <span id="breadcrumb-current" class="font-display font-bold text-[var(--text-primary)]">@yield('breadcrumb', 'Dashboard Principal')</span>
            </nav>
        </div>

        <!-- Buscador global -->
        <div class="hidden md:flex flex-1 max-w-md mx-4">
            <div class="neo-inset w-full h-10 px-3.5 rounded-xl flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-[var(--text-muted)]"></i>
                    <input type="text" placeholder="Buscar ventas, productos o clientes..." class="w-full bg-transparent text-xs text-[var(--text-primary)] placeholder-[var(--text-muted)]/70 focus:outline-none">
                </div>
                <kbd class="px-2 py-0.5 text-[10px] font-mono font-bold rounded-md bg-[var(--bg-surface)] text-[var(--text-muted)] border border-[var(--border-hairline)]">Ctrl + K</kbd>
            </div>
        </div>

        <!-- Perfil y Tema -->
        <div class="flex items-center gap-3 sm:gap-4">
            <button id="theme-toggle-btn" title="Cambiar tema" class="neo-btn w-10 h-10 rounded-full flex items-center justify-center cursor-pointer">
                <i id="icon-moon" data-lucide="moon" class="w-4 h-4 text-slate-700"></i>
                <i id="icon-sun" data-lucide="sun" class="w-4 h-4 text-amber-400 hidden"></i>
            </button>
            <div class="h-7 w-px bg-[var(--border-hairline)] hidden sm:block"></div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full neo-card-sm flex items-center justify-center font-display font-extrabold text-sm text-blue-600 dark:text-amber-400 shrink-0">
                    {{ mb_strtoupper(mb_substr(auth()->user()->nombre_completo ?? 'U', 0, 1)) }}
                </div>
                <div class="hidden xl:flex flex-col leading-tight">
                    <span class="text-sm font-display font-bold text-[var(--text-primary)]">{{ auth()->user()->nombre_completo ?? 'Usuario' }}</span>
                    <span class="text-[11px] text-[var(--text-muted)] flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> 
                        {{ auth()->user()->rol ?? 'Administrador' }}
                    </span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" title="Salir" class="neo-btn w-10 h-10 rounded-xl flex items-center justify-center text-[var(--text-muted)] hover:text-rose-500 cursor-pointer">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle
        const themeBtn = document.getElementById('theme-toggle-btn');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const html = document.documentElement;
                const isDark = html.getAttribute('data-theme') === 'dark';
                const nextTheme = isDark ? 'light' : 'dark';
                html.setAttribute('data-theme', nextTheme);
                document.getElementById('icon-moon').classList.toggle('hidden', nextTheme === 'dark');
                document.getElementById('icon-sun').classList.toggle('hidden', nextTheme === 'light');
                
                // Guardar preferencia en localStorage (misma clave que theme.js)
                try { localStorage.setItem('studio-theme', nextTheme); } catch (_) {}
                
                // Disparar evento para actualizar gráficos si existen
                window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: nextTheme } }));
            });
        }

        // Cargar tema guardado (sincronizado con theme.js)
        let savedTheme = 'light';
        try { savedTheme = localStorage.getItem('studio-theme') || savedTheme; } catch (_) {}
        if (savedTheme === 'dark' || savedTheme === 'light') {
            document.documentElement.setAttribute('data-theme', savedTheme);
            if (savedTheme === 'dark') {
                const moon = document.getElementById('icon-moon');
                const sun = document.getElementById('icon-sun');
                if (moon) moon.classList.add('hidden');
                if (sun) sun.classList.remove('hidden');
            }
        }

        // Sidebar toggle responsive
        const sidebar = document.getElementById('app-sidebar');
        const sBackdrop = document.getElementById('sidebar-backdrop');
        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        
        if (toggleBtn && sidebar && sBackdrop) {
            toggleBtn.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    sidebar.classList.toggle('-translate-x-full');
                    sBackdrop.classList.toggle('hidden');
                }
            });
            sBackdrop.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sBackdrop.classList.add('hidden');
            });
        }
    });
</script>
