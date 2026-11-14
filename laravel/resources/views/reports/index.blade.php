@extends('layouts.app')
@section('title', 'Reportes — '.config('business.name'))
@section('content')
<section class="module-shell reports-page">
    <header class="module-header">
        <div><span class="eyebrow">Centro de inteligencia</span><h1>Módulo de Reportes</h1><p>Selecciona un reporte para visualizar los datos consolidados de tu negocio.</p></div>
    </header>
    <div class="report-catalog">
        @foreach($types as $type => $definition)
            @can($definition[1])
                <article class="report-card card"><div class="report-card__icon">{{ $loop->iteration }}</div><div><h2>{{ $definition[0] }}</h2><p>{{ match($type) { 'ventas' => 'Ingresos, número de ventas, clientes y detalle de operaciones.', 'inventario' => 'Stock actual, valoración de activos y necesidades de reposición.', 'caja' => 'Flujo de efectivo, diferencias y cierre por turno.', 'top-productos' => 'Productos más vendidos por unidades y facturación.', 'rentabilidad' => 'Márgenes, costos y ganancia documentada.', 'metodos-pago' => 'Distribución de ventas por método de pago.', default => 'Consulta información operativa y financiera de la base de datos.' } }}</p></div><a class="btn btn-secondary" href="{{ route('reports.show', $type) }}">Ver reporte</a></article>
            @endcan
        @endforeach
    </div>
</section>
@endsection
@extends('layouts.app')
@section('title', 'Centro de Inteligencia & Reportes — ' . config('business.name', 'Studio & Progetto'))
@section('breadcrumb', 'Centro de Inteligencia & Reportes')

@section('content')
@php
    // Mapeo de descripciones e iconos para los reportes
    $reportDetails = [
        'ventas' => ['desc' => 'Ingresos, número de ventas, clientes y detalle de operaciones.', 'icon' => 'dollar-sign'],
        'compras' => ['desc' => 'Consulta información operativa y financiera de adquisiciones.', 'icon' => 'shopping-bag'],
        'inventario' => ['desc' => 'Stock actual, valoración de activos y necesidades de reposición.', 'icon' => 'package'],
        'movimientos-inventario' => ['desc' => 'Entradas, salidas y transferencias entre almacenes físicos.', 'icon' => 'arrow-left-right'],
        'top-productos' => ['desc' => 'Ranking de productos por unidades despachadas y facturación.', 'icon' => 'trending-up'],
        'stock-bajo' => ['desc' => 'Alertas críticas de reposición para evitar quiebres de stock.', 'icon' => 'alert-triangle'],
        'rentabilidad' => ['desc' => 'Márgenes, costos de adquisición y ganancia neta documentada.', 'icon' => 'percent'],
        'metodos-pago' => ['desc' => 'Distribución y arqueo de ventas por canal (Yape, Efectivo, Tarjeta).', 'icon' => 'credit-card'],
        'clientes' => ['desc' => 'Cartera consolidada y comportamiento de compra frecuente.', 'icon' => 'users'],
        'proveedores' => ['desc' => 'Cuentas corrientes, historial de pedidos y deudas vigentes.', 'icon' => 'truck'],
        'caja' => ['desc' => 'Flujo de efectivo, diferencias y cierre de caja por turno.', 'icon' => 'calculator'],
        'saldos-clientes' => ['desc' => 'Cuentas por cobrar y conciliación de estados financieros.', 'icon' => 'file-check'],
        'movimientos-caja' => ['desc' => 'Ingresos y egresos detallados en efectivo y cuentas bancarias.', 'icon' => 'coins'],
        'cambios-devoluciones' => ['desc' => 'Auditoría de notas de crédito, reembolsos y devoluciones de stock.', 'icon' => 'rotate-ccw'],
    ];
@endphp

<!-- ==============================================================
     CENTRO DE INTELIGENCIA / MÓDULO DE REPORTES
     ============================================================== -->
<div class="space-y-6">
    <div class="neo-card rounded-3xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-2">
            <div class="neo-card-sm w-12 h-12 rounded-2xl flex items-center justify-center text-blue-600 dark:text-amber-400">
                <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-[var(--text-primary)]">Centro de inteligencia & Módulo de Reportes</h2>
                <p class="text-xs sm:text-sm text-[var(--text-muted)]">Selecciona un reporte para visualizar los datos consolidados en tiempo real de tu negocio.</p>
            </div>
        </div>
    </div>

    <!-- Grid de Reportes -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @foreach($types as $type => $definition)
            @can($definition[1])
            @php
                $details = $reportDetails[$type] ?? ['desc' => 'Consulta información operativa y financiera de la base de datos.', 'icon' => 'file-text'];
            @endphp
            <div class="neo-card neo-card-hover rounded-3xl p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="neo-card-sm w-11 h-11 rounded-2xl flex items-center justify-center text-blue-600 dark:text-amber-400">
                            <i data-lucide="{{ $details['icon'] }}" class="w-5 h-5"></i>
                        </div>
                        <span class="font-mono text-xs font-bold text-[var(--text-muted)]">#{{ $loop->iteration }}</span>
                    </div>
                    <h3 class="font-display font-bold text-base text-[var(--text-primary)] mb-1">{{ $definition[0] }}</h3>
                    <p class="text-xs text-[var(--text-muted)] leading-relaxed">{{ $details['desc'] }}</p>
                </div>
                <div class="mt-5 pt-3 border-t border-[var(--border-hairline)] flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">BD Sincronizada</span>
                    <a href="{{ route('reports.show', $type) }}" class="neo-btn px-4 py-2 rounded-xl text-xs font-bold text-blue-600 dark:text-amber-400 flex items-center gap-1.5 cursor-pointer transition-colors">
                        <span>Ver reporte</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
            @endcan
        @endforeach
    </div>
</div>
@endsection
