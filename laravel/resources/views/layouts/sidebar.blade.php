<aside class="sidebar" id="main-nav" aria-label="Navegación principal">
    <a class="brand" href="{{ route('home') }}"><span class="brand-logo"><img src="{{ route('brand.logo') }}" alt="" width="40" height="40"></span><span>{{ config('business.name', 'Studio & Progetto') }}<small>Gestión comercial</small></span></a>
    <p class="nav-group">ESPACIO DE TRABAJO</p>
    <nav class="sidebar-links">
        @can('dashboard_ver')<x-nav-link :href="route('home')" :active="request()->routeIs('home')">Dashboard</x-nav-link>@endcan
        @can('ventas_crear')<x-nav-link :href="route('sales.pos')" :active="request()->routeIs('sales.pos')" icon="cart">Punto de venta</x-nav-link>@endcan
        @can('ventas_ver_listado')<x-nav-link :href="route('sales.history')" :active="request()->routeIs('sales.history','sales.ticket')" icon="clock">Historial de ventas</x-nav-link>@endcan
        @can('ventas_crear')<x-nav-link :href="route('tickets.center')" :active="request()->routeIs('tickets.*')" icon="box">Centro de tickets</x-nav-link>@endcan
        @can('productos_ver_lista')<x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')" icon="box">Inventario</x-nav-link>@endcan
        @can('viewAny', App\Models\Categoria::class)<x-nav-link :href="route('categorias.index')" :active="request()->routeIs('categorias.*')" icon="tag">Categorías</x-nav-link>@endcan
        <p class="nav-group">OPERACIONES Y RECURSOS</p>
        @foreach(config('masters') as $module=>$definition)
        @continue($module === 'tipomoneda')
        @can(app(\App\Services\Masters\MasterData::class)->permission($module,'ver_lista'))<x-nav-link :href="route('masters.index',$module)" :active="request()->route('maestro')===$module" :icon="in_array($module,['cliente','proveedor']) ? 'users' : 'box'">{{ $definition['title'] }}</x-nav-link>@endcan
        @endforeach
        @can('transferencias_crear')<x-nav-link :href="route('transfers.index')" :active="request()->routeIs('transfers.*')" icon="swap">Transferencias</x-nav-link>@endcan
        @can('compras_ver_lista')<x-nav-link :href="route('purchases.index')" :active="request()->routeIs('purchases.*')" icon="cart">Compras</x-nav-link>@endcan
        @can('caja_gestionar')<x-nav-link :href="route('cash.index')" :active="request()->routeIs('cash.*')" icon="wallet">Caja</x-nav-link>@endcan
        @unless(auth()->user()->isMultifunctional())<x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="chart">Reportes</x-nav-link>@endunless
        @unless(auth()->user()->isMultifunctional())<p class="nav-group">ADMINISTRACIÓN</p>@endunless
        @can('usuarios_ver_lista')<x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users">Usuarios</x-nav-link>@endcan
        @can('backups_gestionar')<x-nav-link :href="route('backups.index')" :active="request()->routeIs('backups.*')" icon="shield">Copias de seguridad</x-nav-link>@endcan
        @can('admin_gestionar_roles')<x-nav-link :href="route('settings.business')" :active="request()->routeIs('settings.*')" icon="settings">Configuración</x-nav-link>@endcan
        @canany(['ventas_devolver','solicitar_devolucion'])
            <x-nav-link :href="route('sales.exchanges')" :active="request()->routeIs('sales.exchanges')" icon="swap">Cambio de producto</x-nav-link>
        @endcanany
    </nav>
    <form method="POST" action="{{ route('logout') }}" class="sidebar-exit">@csrf<button type="submit" class="btn-secondary" aria-label="Salir"><x-icon name="logout"/><span class="nav-label">Salir</span></button></form>
</aside>
