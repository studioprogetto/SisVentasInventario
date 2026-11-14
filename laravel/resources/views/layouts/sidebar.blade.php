<aside class="sidebar" id="main-nav" aria-label="Navegación principal">
    <a class="brand" href="{{ route('home') }}"><span class="brand-logo"><img src="{{ route('brand.logo') }}" alt="" width="40" height="40"></span><span>{{ config('business.name', 'Studio & Progetto') }}<small>Gestión comercial</small></span></a>
    <p class="nav-group">ESPACIO DE TRABAJO</p>
    <nav class="sidebar-links">
        @can('dashboard_ver')<x-nav-link :href="route('home')" :active="request()->routeIs('home')">Dashboard</x-nav-link>@endcan
        @can('ventas_crear')<x-nav-link :href="route('sales.pos')" :active="request()->routeIs('sales.pos')" icon="cart">Punto de venta</x-nav-link>@endcan
        @can('ventas_ver_listado')<x-nav-link :href="route('sales.history')" :active="request()->routeIs('sales.history','sales.ticket')" icon="clock">Historial de ventas</x-nav-link>@endcan
        @can('productos_ver_lista')<x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')" icon="box">Inventario</x-nav-link>@endcan
        @can('viewAny', App\Models\Categoria::class)<x-nav-link :href="route('categorias.index')" :active="request()->routeIs('categorias.*')" icon="tag">Categorías</x-nav-link>@endcan
        <p class="nav-group">OPERACIONES Y RECURSOS</p>
        @foreach(config('masters') as $module=>$definition)
            @can($definition['all_permission'] ?? $definition['permission'].'_ver_lista')<x-nav-link :href="route('masters.index',$module)" :active="request()->route('maestro')===$module" :icon="in_array($module,['cliente','proveedor']) ? 'users' : 'box'">{{ $definition['title'] }}</x-nav-link>@endcan
        @endforeach
        @can('transferencias_crear')<x-nav-link :href="route('transfers.index')" :active="request()->routeIs('transfers.*')" icon="swap">Transferencias</x-nav-link>@endcan
        @can('compras_ver_lista')<x-nav-link :href="route('purchases.index')" :active="request()->routeIs('purchases.*')" icon="cart">Compras</x-nav-link>@endcan
        @can('caja_gestionar')<x-nav-link :href="route('cash.index')" :active="request()->routeIs('cash.*')" icon="wallet">Caja</x-nav-link>@endcan
        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="chart">Reportes</x-nav-link>
        <p class="nav-group">ADMINISTRACIÓN</p>
        @can('usuarios_ver_lista')<x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users">Usuarios</x-nav-link>@endcan
        @can('tipocambio_ver_lista')<x-nav-link :href="route('rates.index')" :active="request()->routeIs('rates.*')" icon="swap">Tipo de cambio</x-nav-link>@endcan
        @can('backups_gestionar')<x-nav-link :href="route('backups.index')" :active="request()->routeIs('backups.*')" icon="shield">Copias de seguridad</x-nav-link>@endcan
        @can('admin_gestionar_roles')<x-nav-link :href="route('settings.business')" :active="request()->routeIs('settings.*')" icon="settings">Configuración</x-nav-link>@endcan
    </nav>
    <div class="sidebar-footer"><span class="online-dot"></span> Tu negocio, en un solo lugar.</div>
</aside>
<!-- ======================================================================
     BARRA LATERAL (270px) - NAVEGACIÓN COMPLETA
     ====================================================================== -->
<aside id="app-sidebar" class="fixed lg:sticky top-0 left-0 z-50 h-screen w-[270px] shrink-0 bg-[var(--sidebar-bg)] text-slate-300 flex flex-col border-r border-white/[0.06] transition-transform duration-300 -translate-x-full lg:translate-x-0 shadow-2xl lg:shadow-none">
    
    <!-- Logo Brand -->
    <div class="p-5 flex items-center gap-3.5 border-b border-white/[0.07]">
        <div class="w-12 h-12 rounded-2xl bg-slate-900/95 border border-white/10 shadow-inner flex items-center justify-center shrink-0">
            <svg class="w-9 h-9" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 21L13.5 10.5H50.5L55 21H9Z" fill="#059669"/>
                <path d="M20.5 10.5H27.5L26.5 21H18.5L20.5 10.5Z" fill="#fefce8"/>
                <path d="M36.5 10.5H43.5L45.5 21H37.5L36.5 10.5Z" fill="#fefce8"/>
                <path d="M9 21H18.5V23.6C18.5 25.1 16.4 26.3 13.75 26.3C11.1 26.3 9 25.1 9 23.6V21Z" fill="#047857"/>
                <path d="M18.5 21H27.5V23.6C27.5 25.1 25.5 26.3 23 26.3C20.5 26.3 18.5 25.1 18.5 23.6V21Z" fill="#e2e8f0"/>
                <path d="M27.5 21H36.5V23.6C36.5 25.1 34.5 26.3 32 26.3C29.5 26.3 27.5 25.1 27.5 23.6V21Z" fill="#047857"/>
                <path d="M36.5 21H45.5V23.6C45.5 25.1 43.5 26.3 41 26.3C38.5 26.3 36.5 25.1 36.5 23.6V21Z" fill="#e2e8f0"/>
                <path d="M45.5 21H55V23.6C55 25.1 52.9 26.3 50.25 26.3C47.6 26.3 45.5 25.1 45.5 23.6V21Z" fill="#047857"/>
                <path d="M14 29.5V52H36.5L14 29.5Z" fill="#f59e0b"/>
                <path d="M18.5 40V47.5H26L18.5 40Z" fill="#0f172a"/>
                <g transform="translate(33, 35) rotate(-32)">
                    <path d="M -4 8 L -4 -9 A 4 4 0 0 1 4 -9 L 4 8 A 3 3 0 0 1 -2 8 L -2 -6 A 1.8 1.8 0 0 1 1.6 -6 L 1.6 5" fill="none" stroke="#ef4444" stroke-width="2.4" stroke-linecap="round"/>
                </g>
                <g transform="rotate(14 46.5 39)">
                    <rect x="43.5" y="28.5" width="5.8" height="16.5" rx="1" fill="#ea580c"/>
                    <rect x="43.5" y="25.5" width="5.8" height="3.2" rx="1" fill="#ef4444"/>
                    <rect x="43.5" y="28.2" width="5.8" height="1.3" fill="#cbd5e1"/>
                    <path d="M43.5 45L46.4 51.5L49.3 45H43.5Z" fill="#fde68a"/>
                </g>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-display font-extrabold text-[12px] tracking-wide text-white leading-tight truncate">
                {{ config('business.name', 'LA FRENTONA MAS FRENTONA') }}
            </p>
            <p class="text-[10px] text-amber-400 font-medium mt-0.5 truncate">
                Studio &amp; Progetto • Enterprise
            </p>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto custom-scroll px-3 py-4 space-y-5 text-xs">
        <!-- 1. Espacio de trabajo -->
        <div>
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">Espacio de trabajo</p>
            <ul class="space-y-1">
                @can('dashboard_ver')
                <li>
                    <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('home') ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold shadow-[0_0_20px_rgba(37,99,235,0.45)]' : 'text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @endcan
                @can('ventas_crear')
                <li><a href="{{ route('sales.pos') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="shopping-cart" class="w-4 h-4 text-slate-400"></i><span>Punto de venta</span></a></li>
                @endcan
                @can('ventas_ver_listado')
                <li><a href="{{ route('sales.history') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="history" class="w-4 h-4 text-slate-400"></i><span>Historial de ventas</span></a></li>
                @endcan
                @can('productos_ver_lista')
                <li><a href="{{ route('products.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="package" class="w-4 h-4 text-slate-400"></i><span>Inventario</span></a></li>
                @endcan
                @can('viewAny', App\Models\Categoria::class)
                <li><a href="{{ route('categorias.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="tags" class="w-4 h-4 text-slate-400"></i><span>Categorías</span></a></li>
                @endcan
            </ul>
        </div>

        <!-- 2. Operaciones y recursos -->
        <div>
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">Operaciones y recursos</p>
            <ul class="space-y-1">
                @foreach(config('masters') as $module => $definition)
                    @can($definition['all_permission'] ?? $definition['permission'].'_ver_lista')
                    <li><a href="{{ route('masters.index', $module) }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="{{ in_array($module, ['cliente', 'proveedor']) ? 'users' : 'box' }}" class="w-4 h-4 text-slate-400"></i><span>{{ $definition['title'] }}</span></a></li>
                    @endcan
                @endforeach
                @can('transferencias_crear')
                <li><a href="{{ route('transfers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="arrow-left-right" class="w-4 h-4 text-slate-400"></i><span>Transferencias</span></a></li>
                @endcan
                @can('compras_ver_lista')
                <li><a href="{{ route('purchases.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="shopping-bag" class="w-4 h-4 text-slate-400"></i><span>Compras</span></a></li>
                @endcan
                @can('caja_gestionar')
                <li><a href="{{ route('cash.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="calculator" class="w-4 h-4 text-slate-400"></i><span>Caja</span></a></li>
                @endcan
                <li>
                    <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-slate-400"></i>
                        <span>Reportes (Inteligencia)</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 3. Administración -->
        <div>
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">Administración</p>
            <ul class="space-y-1">
                @can('usuarios_ver_lista')
                <li><a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="user-cog" class="w-4 h-4 text-slate-400"></i><span>Usuarios</span></a></li>
                @endcan
                @can('tipocambio_ver_lista')
                <li><a href="{{ route('rates.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="refresh-cw" class="w-4 h-4 text-slate-400"></i><span>Tipo de cambio</span></a></li>
                @endcan
                @can('backups_gestionar')
                <li><a href="{{ route('backups.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="shield-check" class="w-4 h-4 text-slate-400"></i><span>Copias de seguridad</span></a></li>
                @endcan
                @can('admin_gestionar_roles')
                <li><a href="{{ route('settings.business') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:bg-white/[0.06] hover:text-white transition-colors"><i data-lucide="settings" class="w-4 h-4 text-slate-400"></i><span>Configuración</span></a></li>
                @endcan
            </ul>
        </div>
    </nav>
    
    <div class="p-4 border-t border-white/[0.07]">
        <div class="flex items-center gap-2 text-[10px] text-slate-500">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Tu negocio, en un solo lugar.</span>
        </div>
    </div>
</aside>
