@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/products-index.css') }}?v={{ filemtime(public_path('css/products-index.css')) }}">
@endpush

@section('title', 'Inventario — '.config('business.name'))

@section('content')
@php
    $warehouseFilter = request('id_almacen');
    $activeState = request('estado', 'activos');
    $money = fn ($amount) => 'S/ '.number_format((float) $amount, 2, '.', ',');
@endphp
<section class="inventory-page" aria-labelledby="inventory-title">
    <header class="inventory-heading">
        <div>
            <span class="inventory-eyebrow"><span></span> Control de existencias</span>
            <h1 id="inventory-title">Inventario</h1>
            <p>Consulta productos, disponibilidad y alertas de reposición.</p>
        </div>
        <div class="inventory-heading-actions">
            <a class="inventory-button inventory-button-soft" href="{{ route('reports.show', 'inventario') }}?formato=xlsx">Excel</a>
            <a class="inventory-button inventory-button-soft" href="{{ route('reports.show', 'inventario') }}?formato=pdf">PDF</a>
            <a class="inventory-button inventory-button-soft" href="{{ route('products.index', ['estado' => $activeState === 'activos' ? 'inactivos' : 'activos']) }}">
                <x-icon name="box"/>
                {{ $activeState === 'activos' ? 'Ver inactivos' : 'Ver activos' }}
            </a>
            @can('productos_crear')
                <button class="inventory-button inventory-button-primary" type="button" data-open-product-modal="new-product-modal"><span aria-hidden="true">＋</span> Nuevo producto</button>
            @endcan
        </div>
    </header>

    <section class="inventory-kpis" aria-label="Resumen del inventario">
        <article class="inventory-kpi">
            <div class="inventory-kpi-top"><span class="inventory-kpi-icon inventory-icon-blue"><x-icon name="box"/></span><span class="inventory-kpi-label">Productos activos</span></div>
            <strong>{{ number_format((int) $stats['total']) }}</strong>
            <small>En el catálogo de inventario</small>
        </article>
        <article class="inventory-kpi">
            <div class="inventory-kpi-top"><span class="inventory-kpi-icon inventory-icon-gold"><x-icon name="wallet"/></span><span class="inventory-kpi-label">Valor a precio de venta</span></div>
            <strong class="inventory-kpi-money">{{ $money($stats['valuation']) }}</strong>
            <small>Calculado con el stock de la selección</small>
        </article>
        <article class="inventory-kpi">
            <div class="inventory-kpi-top"><span class="inventory-kpi-icon inventory-icon-green"><x-icon name="swap"/></span><span class="inventory-kpi-label">Unidades disponibles</span></div>
            <strong>{{ number_format((int) $stats['units']) }}</strong>
            <small>{{ $warehouseFilter ? 'En el almacén seleccionado' : 'En todos los almacenes' }}</small>
        </article>
        <article class="inventory-kpi inventory-kpi-alert">
            <div class="inventory-kpi-top"><span class="inventory-kpi-icon inventory-icon-red"><x-icon name="alert"/></span><span class="inventory-kpi-label">Alertas de stock bajo</span></div>
            <strong>{{ number_format((int) $stats['low_stock']) }}</strong>
            <small>{{ number_format((int) $stats['out_of_stock']) }} sin existencias</small>
        </article>
    </section>

    <section class="inventory-filter-panel" aria-label="Filtros de inventario">
        <form method="GET" action="{{ route('products.index') }}" class="inventory-filter-form">
            <label class="inventory-filter inventory-search" for="inventory-search">
                <span>Buscar producto</span>
                <span class="inventory-control"><x-icon name="search"/><input id="inventory-search" type="search" name="q" value="{{ request('q') }}" placeholder="Nombre, descripción o código" maxlength="100"></span>
            </label>
            <label class="inventory-filter" for="inventory-category">
                <span>Categoría</span>
                <select id="inventory-category" name="id_categoria" class="inventory-control"><option value="">Todas</option>@foreach($categories as $category)<option value="{{ $category->id_categoria }}" @selected((string) request('id_categoria') === (string) $category->id_categoria)>{{ $category->nombre }}</option>@endforeach</select>
            </label>
            <label class="inventory-filter" for="inventory-warehouse">
                <span>Almacén</span>
                <select id="inventory-warehouse" name="id_almacen" class="inventory-control"><option value="">Todos</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string) $warehouseFilter === (string) $warehouse->id)>{{ $warehouse->nombre }}</option>@endforeach</select>
            </label>
            <label class="inventory-filter" for="inventory-sort">
                <span>Ordenar por</span>
                <select id="inventory-sort" name="orden" class="inventory-control">
                    <option value="nombre" @selected(request('orden', 'nombre') === 'nombre')>Nombre A–Z</option>
                    <option value="precio_asc" @selected(request('orden') === 'precio_asc')>Menor precio</option>
                    <option value="precio_desc" @selected(request('orden') === 'precio_desc')>Mayor precio</option>
                    <option value="stock_asc" @selected(request('orden') === 'stock_asc')>Menor stock</option>
                    <option value="stock_desc" @selected(request('orden') === 'stock_desc')>Mayor stock</option>
                </select>
            </label>
            <label class="inventory-status-filter"><input type="checkbox" name="bajo" value="1" @checked(request()->boolean('bajo'))><span class="inventory-switch" aria-hidden="true"></span><span>Solo stock bajo</span></label>
            <input type="hidden" name="estado" value="{{ $activeState }}">
            <div class="inventory-filter-actions"><button class="inventory-button inventory-button-primary" type="submit"><x-icon name="search"/> Aplicar</button><a class="inventory-button inventory-button-soft" href="{{ route('products.index', ['estado' => $activeState]) }}">Limpiar</a></div>
        </form>
    </section>

    <div class="inventory-results-bar">
        <p>Mostrando <strong>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</strong> de <strong>{{ $products->total() }}</strong> productos</p>
        <div class="inventory-view-controls" role="group" aria-label="Tipo de vista">
            <button type="button" class="is-active" data-inventory-view="grid" aria-pressed="true"><span aria-hidden="true">▦</span> Tarjetas</button>
            <button type="button" data-inventory-view="table" aria-pressed="false"><span aria-hidden="true">▤</span> Tabla</button>
        </div>
    </div>

    <div class="inventory-product-grid" data-products-grid>
        @forelse($products as $product)
            @php
                $stock = $warehouseFilter ? (int) ($product->stock_scope_sum ?? 0) : (int) ($product->stock_total ?? 0);
                $isLow = $stock <= (int) $product->stock_minimo;
                $stockPercent = $product->stock_minimo > 0 ? min(100, (int) round($stock / ($product->stock_minimo * 2) * 100)) : ($stock > 0 ? 100 : 0);
                $imageUrl = route('products.image', $product->id_producto);
            @endphp
            <article class="inventory-product-card">
                <div class="inventory-product-art">
                    <img src="{{ $imageUrl }}" alt="Imagen de {{ $product->nombre }}" loading="lazy">
                    <span class="inventory-category-chip">{{ $product->categoria?->nombre ?? 'Sin categoría' }}</span>
                    <span class="inventory-status-chip {{ $product->activo ? ($isLow ? 'is-low' : 'is-active') : 'is-inactive' }}">
                        {{ !$product->activo ? 'Inactivo' : ($isLow ? 'Stock bajo' : 'Activo') }}
                    </span>
                </div>
                <div class="inventory-product-body">
                    <div class="inventory-product-meta"><span>Código</span><strong>{{ $product->codigo_barra ?: 'Sin código' }}</strong></div>
                    <h2 title="{{ $product->nombre }}">{{ $product->nombre }}</h2>
                    <p class="inventory-product-description">{{ $product->descripcion ?: 'Sin descripción registrada.' }}</p>
                    <div class="inventory-product-metrics">
                        <div><span>Precio de venta</span><strong>{{ $money($product->precio_venta) }}</strong></div>
                        <div class="inventory-stock-value"><span>{{ $warehouseFilter ? 'Stock en almacén' : 'Stock total' }}</span><strong class="{{ $isLow ? 'is-low' : '' }}">{{ number_format($stock) }} <small>/ mín. {{ number_format((int) $product->stock_minimo) }}</small></strong></div>
                    </div>
                    <div class="inventory-stock-track" role="meter" aria-label="Nivel de stock de {{ $product->nombre }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $stockPercent }}"><span class="{{ $isLow ? 'is-low' : '' }}" style="width: {{ $stockPercent }}%"></span></div>
                    <p class="inventory-default-warehouse">Almacén predeterminado: <strong>{{ $product->almacen?->nombre ?? 'No asignado' }}</strong></p>
                </div>
                <footer class="inventory-card-actions">
                    @can('productos_ver_detalle')<a href="{{ route('products.ledger', $product->id_producto) }}" class="inventory-action-link">Kardex / almacenes</a>@endcan
                    <a href="{{ route('products.labels', ['ids' => [$product->id_producto]]) }}" class="inventory-action-link" target="_blank" rel="noopener">Etiqueta</a>
                    @can('productos_editar')<button type="button" class="inventory-action-link btn-success" data-open-product-modal="product-modal-{{ $product->id_producto }}">Editar</button>@endcan
                    @can('productos_cambiar_estado')<form method="POST" action="{{ route('products.state', $product->id_producto) }}" data-confirm="¿{{ $product->activo ? 'Desactivar' : 'Activar' }} este producto?">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $product->activo ? 0 : 1 }}"><button class="inventory-action-link {{ $product->activo ? 'is-danger' : 'is-success' }}" type="submit">{{ $product->activo ? 'Desactivar' : 'Activar' }}</button></form>@endcan
                </footer>
            </article>
        @empty
            <div class="inventory-empty"><span aria-hidden="true">⌕</span><h2>No hay productos que coincidan</h2><p>Prueba cambiando los filtros o busca por otro nombre o código.</p></div>
        @endforelse
    </div>

    <div class="inventory-table-wrap" data-products-table hidden>
        <table class="inventory-table">
            <thead><tr><th>Producto</th><th>Categoría</th><th>Almacén</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($products as $product)
                @php $stock = $warehouseFilter ? (int) ($product->stock_scope_sum ?? 0) : (int) ($product->stock_total ?? 0); $isLow = $stock <= (int) $product->stock_minimo; @endphp
                <tr>
                    <td><div class="inventory-table-product"><img src="{{ route('products.image', $product->id_producto) }}" alt="" loading="lazy"><span><strong>{{ $product->nombre }}</strong><small>{{ $product->codigo_barra ?: 'Sin código' }}</small></span></div></td>
                    <td>{{ $product->categoria?->nombre ?? 'Sin categoría' }}</td><td>{{ $product->almacen?->nombre ?? 'No asignado' }}</td><td>{{ $money($product->precio_venta) }}</td>
                    <td class="{{ $isLow ? 'is-low' : '' }}">{{ number_format($stock) }} / {{ number_format((int) $product->stock_minimo) }}</td>
                    <td><span class="inventory-status-chip {{ $product->activo ? ($isLow ? 'is-low' : 'is-active') : 'is-inactive' }}">{{ !$product->activo ? 'Inactivo' : ($isLow ? 'Stock bajo' : 'Activo') }}</span></td>
                    <td class="inventory-table-actions">
                        @can('productos_ver_detalle')<a href="{{ route('products.ledger', $product->id_producto) }}">Kardex</a>@endcan
                        <a href="{{ route('products.labels', ['ids' => [$product->id_producto]]) }}" target="_blank" rel="noopener">Etiqueta</a>
                        @can('productos_editar')<button type="button" class="inventory-action-link btn-success" data-open-product-modal="product-modal-{{ $product->id_producto }}">Editar</button>@endcan
                        @can('productos_cambiar_estado')<form method="POST" action="{{ route('products.state', $product->id_producto) }}" data-confirm="¿Cambiar el estado del producto?">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $product->activo ? 0 : 1 }}"><button class="btn {{ $product->activo ? 'btn-danger' : 'btn-success' }}" type="submit">{{ $product->activo ? 'Desactivar' : 'Activar' }}</button></form>@endcan
                    </td>
                </tr>
            @empty<tr><td colspan="7">No hay productos con estos filtros.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <nav class="inventory-pagination" aria-label="Paginación del inventario">{{ $products->links('vendor.pagination.tailwind') }}</nav>

    @can('productos_crear')
        <dialog class="product-modal" id="new-product-modal" aria-labelledby="new-product-title">
            <div class="product-modal__surface"><button type="button" class="product-modal__close" data-close-product-modal aria-label="Cerrar formulario">×</button><div class="product-modal__heading"><span class="product-modal__eyebrow">Nuevo registro</span><h2 id="new-product-title">Crear producto</h2><p>Completa la información comercial, de inventario e imagen.</p></div><form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="product-modal__form">@csrf @include('products.form', ['product' => null])</form></div>
        </dialog>
    @endcan
    @foreach($products as $product)
        @can('productos_editar')
            <dialog class="product-modal" id="product-modal-{{ $product->id_producto }}" aria-labelledby="product-title-{{ $product->id_producto }}">
                <div class="product-modal__surface"><button type="button" class="product-modal__close" data-close-product-modal aria-label="Cerrar formulario">×</button><div class="product-modal__heading"><span class="product-modal__eyebrow">Edición de inventario</span><h2 id="product-title-{{ $product->id_producto }}">Editar producto</h2><p>Actualiza los datos del producto y guarda los cambios.</p></div><form method="POST" action="{{ route('products.update', $product->id_producto) }}" enctype="multipart/form-data" class="product-modal__form">@csrf @method('PUT') @include('products.form', ['product' => $product])</form></div>
            </dialog>
        @endcan
    @endforeach
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/products-index.js') }}?v={{ filemtime(public_path('js/products-index.js')) }}" defer></script>
@endpush
