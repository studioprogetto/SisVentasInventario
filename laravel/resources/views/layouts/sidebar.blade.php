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
