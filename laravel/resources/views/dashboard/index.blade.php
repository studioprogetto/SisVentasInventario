@extends('layouts.app')
@section('title', 'Dashboard &mdash; '.config('business.name', 'Studio & Progetto'))
@section('content')
@php
    $user = auth()->user();
    $userName = trim((string) ($user->nombre_completo ?? $user->username ?? 'Usuario'));
    $firstName = explode(' ', $userName)[0] ?? 'Usuario';
    $cards = $summary['cards'] ?? [];
    $chartPoints = collect($summary['sales_series'] ?? [])->values();
    $chartMax = max($chartPoints->pluck('value')->all() ?: [1]);
    $chartWidth = 760;
    $chartHeight = 220;
    $chartPad = 34;
    $chartIntervals = max($chartPoints->count() - 1, 1);
    $chartPath = $chartPoints->map(function ($point, $index) use ($chartWidth, $chartHeight, $chartPad, $chartMax, $chartIntervals) {
        $x = $chartPad + ($index * (($chartWidth - ($chartPad * 2)) / $chartIntervals));
        $y = $chartHeight - $chartPad - (($point['value'] / max($chartMax, 1)) * ($chartHeight - ($chartPad * 2)));

        return [$x, $y];
    })->map(fn ($point) => $point[0].','. $point[1])->implode(' ');
@endphp
<div class="studio-dashboard">
    <div class="welcome-row">
        <div class="welcome-copy">
            <span class="welcome-avatar"><x-icon name="users" /></span>
            <div>
                <h2>&iexcl;Hola, {{ $firstName }}!</h2>
                <p>Aqu&iacute; tienes un resumen actualizado de tu negocio.</p>
            </div>
        </div>

        <form class="period-filter" method="GET" action="{{ route('home') }}">
            <label>Desde<input type="date" name="desde" value="{{ $summary['since'] ?? request('desde') }}"></label>
            <label>Hasta<input type="date" name="hasta" value="{{ $summary['until'] ?? request('hasta') }}"></label>
            <button class="btn-primary" type="submit"><x-icon name="chart" /> Actualizar</button>
        </form>
    </div>

    <div class="stats-grid">
        @foreach($cards as $card)
            @php
                $trendValue = (float) ($card['trend'] ?? 0);
                $trendDirection = $trendValue >= 0 ? 'up' : 'down';
                $trendText = ($trendValue >= 0 ? '+' : '').number_format($trendValue, 1, ',', '.').' %';
            @endphp
            <article class="stat-card soft">
                <div class="stat-head">
                    <span class="stat-icon {{ $card['tone'] ?? 'primary' }}"><x-icon name="{{ $card['icon'] ?? 'chart' }}" /></span>
                    <span class="stat-label">{{ $card['label'] }}</span>
                </div>
                <div class="stat-value">{{ $card['value'] }}</div>
                <div class="stat-trend {{ $trendDirection }}"><span>{{ $trendValue >= 0 ? '&#8593;' : '&#8595;' }}</span> {{ $trendText }} vs. per&iacute;odo previo</div>
            </article>
        @endforeach
    </div>

    <div class="content-grid top-grid">
        <section class="panel chart-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="chart" /></span>
                    <h3>Resumen de ventas</h3>
                </div>
                <span class="period-caption">{{ $summary['period_label'] ?? 'Periodo actual' }}</span>
            </div>

            <div class="chart-summary">
                <div class="chart-pill">{{ $cards[0]['value'] ?? 'S/ 0.00' }}</div>
                <small>{{ now()->format('d/m/Y') }}</small>
            </div>

            <svg class="line-chart" viewBox="0 0 780 250" role="img" aria-label="Resumen de ventas del periodo">
                <defs><linearGradient id="areaBlue" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#315ee9" stop-opacity="0.24"/><stop offset="100%" stop-color="#315ee9" stop-opacity="0.01"/></linearGradient></defs>
                <g class="grid-lines">
                    <line x1="40" y1="25" x2="740" y2="25"/>
                    <line x1="40" y1="75" x2="740" y2="75"/>
                    <line x1="40" y1="125" x2="740" y2="125"/>
                    <line x1="40" y1="175" x2="740" y2="175"/>
                    <line x1="40" y1="225" x2="740" y2="225"/>
                </g>

                <polyline fill="url(#areaBlue)" stroke="none" points="40,225 {{ $chartPath ?: '40,225' }} 740,225"/>
                <polyline fill="none" stroke="var(--accent)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $chartPath ?: '40,225' }}"/>
                <g class="chart-points">
                    @foreach($chartPoints->take(6) as $point)
                        @php
                            $index = $loop->index;
                            $x = 40 + ($index * ((740 - 40) / max($chartPoints->count() - 1, 1)));
                            $y = 220 - (($point['value'] / max($chartMax, 1)) * (220 - 30));
                        @endphp
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4.8"/>
                    @endforeach
                </g>
            </svg>

            <div class="chart-labels">
                @foreach($chartPoints as $point)
                    <span>{{ $point['label'] }}</span>
                @endforeach
            </div>
        </section>

        <aside class="panel products-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon purple"><x-icon name="chart" /></span>
                    <h3>Productos m&aacute;s vendidos</h3>
                </div>
                <a href="{{ route('products.index') }}">Ver inventario</a>
            </div>

            <ol class="rank-list">
                @foreach(($summary['top_products'] ?? []) as $product)
                    @php $width = min(100, (($loop->index + 1) * 18) + 35); @endphp
                    <li>
                        <div class="rank-meta">
                            <span class="rank-index">{{ $loop->iteration }}</span>
                            <span class="mini-box {{ ['blue','purple','mint','peach','green'][$loop->index % 5] }}"></span>
                            <span class="rank-name">{{ $product->nombre ?? 'Producto' }}</span>
                        </div>
                        <div class="rank-stats">
                            <span class="rank-qty">{{ $product->unidades ?? 0 }} und.</span>
                            <span class="rank-total">S/ {{ number_format((float) ($product->importe ?? 0), 2, ',', '.') }}</span>
                        </div>
                        <div class="progress-bar"><span style="width: {{ $width }}%"></span></div>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>

    <div class="content-grid secondary-grid">
        <section class="panel alerts-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="alert" /></span>
                    <h3>Alertas de inventario</h3>
                </div>
                <a href="{{ route('products.index') }}">Ver inventario</a>
            </div>
            <div class="alerts-summary"><strong>{{ number_format((int) ($summary['low_stock'] ?? 0)) }}</strong><span>productos requieren revisi&oacute;n</span></div>
            <ul class="inventory-alert-list">
                @forelse(($summary['inventory_alerts'] ?? []) as $alert)
                    <li><span class="alert-dot"></span><span><strong>{{ $alert->nombre }}</strong><small>Stock actual: {{ number_format((int) $alert->stock) }} &middot; M&iacute;nimo: {{ number_format((int) $alert->stock_minimo) }}</small></span><a href="{{ route('products.ledger', $alert->id_producto) }}">Ver</a></li>
                @empty
                    <li class="empty-alert"><x-icon name="shield" /><span><strong>Todo en orden</strong><small>No hay productos por debajo del stock m&iacute;nimo.</small></span></li>
                @endforelse
            </ul>
        </section>

        <section class="panel operations-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="file" /></span>
                    <h3>Operaciones del mes</h3>
                </div>
            </div>

            <div class="operation-grid">
                <article class="mini-operation purchase">
                    <span class="mini-badge"><x-icon name="cart" /></span>
                    <div>
                        <h4>Compras</h4>
                        <strong>S/ {{ number_format((float) ($summary['operations']['compras'] ?? 0), 2, ',', '.') }}</strong>
                        <small>Recibidas</small>
                    </div>
                </article>

                <article class="mini-operation return">
                    <span class="mini-badge"><x-icon name="arrow" /></span>
                    <div>
                        <h4>Devoluciones</h4>
                        <strong>S/ {{ number_format((float) ($summary['operations']['devoluciones'] ?? 0), 2, ',', '.') }}</strong>
                        <small>Registradas</small>
                    </div>
                </article>

                <article class="mini-operation transfer">
                    <span class="mini-badge"><x-icon name="swap" /></span>
                    <div>
                        <h4>Transferencias</h4>
                        <strong>{{ (int) ($summary['operations']['transferencias'] ?? 0) }} movimientos</strong>
                        <small>Activas</small>
                    </div>
                </article>
            </div>
        </section>

        <section class="panel exchange-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="swap" /></span>
                    <h3>Tipo de cambio</h3>
                </div>
                <button class="ghost-btn" type="button">Actualizar</button>
            </div>

            <div class="exchange-values">
                @foreach(($summary['rates'] ?? []) as $rate)
                    <div class="exchange-box">
                        <span class="currency-label">{{ $rate->codigo ?? 'USD' }} ({{ $rate->codigo ?? 'USD' }})</span>
                        <strong>S/ {{ number_format((float) ($rate->valor_soles ?? 0), 4, ',', '.') }}</strong>
                        <small>&Uacute;ltima tasa</small>
                    </div>
                @endforeach
            </div>

            <svg class="mini-chart" viewBox="0 0 300 120" role="img" aria-label="Evoluci&oacute;n del tipo de cambio">
                <path d="M0 80 C45 72, 58 60, 90 64 S150 58, 180 52 S225 64, 300 42" fill="none" stroke="#3c78ff" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </section>
    </div>

    <div class="content-grid bottom-grid">
        <section class="panel table-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="file" /></span>
                    <h3>&Uacute;ltimas ventas</h3>
                </div>
                <a href="{{ route('sales.history') }}">Ver todas</a>
            </div>

            <table class="sales-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($summary['latest_sales'] ?? []) as $sale)
                        <tr>
                            <td>{{ $sale->id_venta }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($sale->fecha_venta)->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->cliente }}</td>
                            <td>S/ {{ number_format((float) ($sale->total_venta ?? 0), 2, ',', '.') }}</td>
                            <td><span class="status-pill {{ $sale->estado === 'completada' ? '' : 'pending' }}">{{ $sale->estado === 'completada' ? 'Completada' : ucfirst($sale->estado) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <aside class="panel promo-panel">
            <div class="promo-badge"><x-icon name="chart" /></div>
            <h3>&iexcl;Gracias por confiar en {{ config('app.name', config('business.name', 'Studio & Progetto')) }}!</h3>
            <p>Seguimos creciendo juntos.</p>
            <div class="promo-illustration">
                <div class="pencil"></div>
                <div class="cup"></div>
                <div class="notebook"></div>
            </div>
        </aside>
    </div>
</div>

@endsection


@extends('layouts.app')
@section('title', 'Dashboard &mdash; ' . config('business.name', 'LA FRENTONA MAS FRENTONA'))
@section('breadcrumb', 'Dashboard Principal')

@section('content')
@php
    $user = auth()->user();
    $userName = trim((string) ($user->nombre_completo ?? $user->username ?? 'Usuario'));
    $firstName = explode(' ', $userName)[0] ?? 'Usuario';
    $cards = $summary['cards'] ?? [];
    $chartPoints = collect($summary['sales_series'] ?? [])->values();
    $chartMax = max($chartPoints->pluck('value')->all() ?: [1]);
    
    // Preparar datos para Chart.js
    $chartLabels = $chartPoints->pluck('label')->toArray();
    $chartValues = $chartPoints->pluck('value')->toArray();
    $topProducts = $summary['top_products'] ?? [];
    $inventoryAlerts = $summary['inventory_alerts'] ?? [];
    $latestSales = $summary['latest_sales'] ?? [];
    $operations = $summary['operations'] ?? [];
@endphp

<!-- ==============================================================
     VIEW 1: DASHBOARD PRINCIPAL
     ============================================================== -->
<div class="space-y-6">
    
    <!-- GREETING & DATE RANGE PICKER -->
    <div class="neo-card rounded-3xl p-5 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-blue-500/10 text-blue-600 dark:bg-amber-500/10 dark:text-amber-400 border border-blue-500/20 dark:border-amber-500/20 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-amber-400 animate-pulse"></span>
                Sistema en tiempo real • Conectado a Base de Datos
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight text-[var(--text-primary)]">
                ¡Hola, {{ $firstName }}!
            </h1>
            <p class="text-xs sm:text-sm text-[var(--text-muted)] mt-0.5">
                Aquí tienes un resumen actualizado de tu negocio.
            </p>
        </div>

        <!-- Date Filter and Update button -->
        <form class="flex flex-wrap items-center gap-3" method="GET" action="{{ route('home') }}">
            <div class="neo-inset px-3.5 py-2 rounded-xl flex items-center gap-3 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold uppercase text-[var(--text-muted)]">Desde:</span>
                    <input type="date" name="desde" value="{{ $summary['since'] ?? request('desde') }}" class="bg-transparent font-mono text-[var(--text-primary)] focus:outline-none cursor-pointer">
                </div>
                <span class="text-[var(--text-muted)]">→</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold uppercase text-[var(--text-muted)]">Hasta:</span>
                    <input type="date" name="hasta" value="{{ $summary['until'] ?? request('hasta') }}" class="bg-transparent font-mono text-[var(--text-primary)] focus:outline-none cursor-pointer">
                </div>
            </div>
            <button type="submit" class="neo-btn-primary h-10 px-5 rounded-xl font-display font-bold text-xs sm:text-sm flex items-center gap-2 cursor-pointer">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                <span>Actualizar</span>
            </button>
        </form>
    </div>

    <!-- 4 KPI CARDS -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        @foreach($cards as $index => $card)
        @php
            $trendValue = (float) ($card['trend'] ?? 0);
            $trendDirection = $trendValue >= 0 ? 'up' : 'down';
            $trendText = ($trendValue >= 0 ? '+' : '') . number_format($trendValue, 1, ',', '.') . '%';
            $iconColors = ['text-blue-600 dark:text-amber-400', 'text-emerald-600', 'text-purple-600', 'text-amber-500'];
            $icons = ['dollar-sign', 'package', 'users', 'trending-up'];
            $currentIcon = $card['icon'] ?? ($icons[$index] ?? 'dollar-sign');
            $currentColor = $iconColors[$index] ?? 'text-blue-600 dark:text-amber-400';
        @endphp
        <div class="neo-card rounded-2xl p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">{{ $card['label'] }}</p>
                    <p class="font-mono text-2xl sm:text-3xl font-extrabold text-[var(--text-primary)] mt-1.5">{{ $card['value'] }}</p>
                </div>
                <div class="neo-card-sm w-11 h-11 rounded-xl flex items-center justify-center {{ $currentColor }}">
                    <i data-lucide="{{ $currentIcon }}" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-[var(--border-hairline)] flex items-center justify-between text-[11px]">
                <span class="text-[var(--text-muted)]">Vs. periodo previo</span>
                <span class="font-mono font-bold {{ $trendValue >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $trendText }}
                </span>
            </div>
        </div>
        @endforeach
    </section>

    <!-- CHART.JS SECTION (3 CHARTS: Sales Trend, Payment Methods, Top Products) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Resumen de ventas (Line Chart) -->
        <div class="neo-card rounded-3xl p-6 lg:col-span-2 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display font-bold text-base text-[var(--text-primary)]">Resumen de ventas</h3>
                    <p class="text-xs text-[var(--text-muted)]">Tendencia diaria de ingresos en soles (S/)</p>
                </div>
                <span class="font-mono text-xs font-bold px-3 py-1 rounded-xl bg-blue-500/10 text-blue-600 dark:text-amber-400 border border-blue-500/20">
                    {{ $summary['period_label'] ?? now()->format('d/m/Y') }}
                </span>
            </div>
            <div class="h-72 w-full">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- Métodos de pago (Doughnut Chart) -->
        <div class="neo-card rounded-3xl p-6 flex flex-col justify-between">
            <div class="mb-4">
                <h3 class="font-display font-bold text-base text-[var(--text-primary)]">Métodos de pago</h3>
                <p class="text-xs text-[var(--text-muted)]">Distribución por canal de cobro</p>
            </div>
            <div class="h-56 w-full flex items-center justify-center">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
            <div class="mt-4 pt-3 border-t border-[var(--border-hairline)] grid grid-cols-3 gap-2 text-center text-[11px]">
                <div>
                    <span class="block text-[var(--text-muted)]">Yape</span>
                    <strong class="font-mono text-emerald-600">55%</strong>
                </div>
                <div>
                    <span class="block text-[var(--text-muted)]">Efectivo</span>
                    <strong class="font-mono text-blue-600">30%</strong>
                </div>
                <div>
                    <span class="block text-[var(--text-muted)]">Tarjeta</span>
                    <strong class="font-mono text-amber-500">15%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- TOP PRODUCTS BAR CHART & INVENTORY ALERTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Productos más vendidos (Bar Chart) -->
        <div class="neo-card rounded-3xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display font-bold text-base text-[var(--text-primary)]">Productos más vendidos</h3>
                    <p class="text-xs text-[var(--text-muted)]">Top 5 por unidades despachadas</p>
                </div>
                <a href="{{ route('products.index') }}" class="text-xs font-bold text-blue-600 dark:text-amber-400 hover:underline">Ver inventario &rarr;</a>
            </div>
            <div class="h-64 w-full">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        <!-- Alertas de Inventario & Operaciones del Mes -->
        <div class="space-y-6">
            <div class="neo-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display font-bold text-base text-[var(--text-primary)] flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500"></i>
                        <span>Alertas de inventario</span>
                    </h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/15 text-rose-600">
                        {{ count($inventoryAlerts) }} ítems
                    </span>
                </div>
                <div class="space-y-2.5 max-h-64 overflow-y-auto custom-scroll">
                    @forelse($inventoryAlerts as $alert)
                    <div class="neo-card-sm p-3 rounded-2xl flex items-center justify-between text-xs">
                        <div>
                            <p class="font-bold text-[var(--text-primary)]">{{ $alert->nombre }}</p>
                            <p class="text-[11px] text-[var(--text-muted)] mt-0.5">Stock actual: {{ number_format((int) $alert->stock) }} • Mínimo: {{ number_format((int) $alert->stock_minimo) }}</p>
                        </div>
                        <a href="{{ route('products.ledger', $alert->id_producto) }}" class="neo-btn px-3 py-1.5 rounded-xl font-bold text-rose-600">Revisar</a>
                    </div>
                    @empty
                    <div class="neo-card-sm p-4 rounded-2xl flex items-center justify-center text-xs text-[var(--text-muted)]">
                        <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-emerald-500"></i>
                        <span>Todo en orden. No hay productos por debajo del stock mínimo.</span>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="neo-card rounded-3xl p-6">
                <h3 class="font-display font-bold text-base text-[var(--text-primary)] mb-3 flex items-center gap-2">
                    <i data-lucide="activity" class="w-5 h-5 text-blue-600 dark:text-amber-400"></i>
                    <span>Operaciones del mes</span>
                </h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="neo-inset p-3 rounded-2xl">
                        <span class="text-[10px] uppercase font-bold text-[var(--text-muted)]">Compras</span>
                        <p class="font-mono font-extrabold text-sm text-[var(--text-primary)] mt-1">S/ {{ number_format((float) ($operations['compras'] ?? 0), 2, ',', '.') }}</p>
                    </div>
                    <div class="neo-inset p-3 rounded-2xl">
                        <span class="text-[10px] uppercase font-bold text-[var(--text-muted)]">Devoluciones</span>
                        <p class="font-mono font-extrabold text-sm text-[var(--text-primary)] mt-1">S/ {{ number_format((float) ($operations['devoluciones'] ?? 0), 2, ',', '.') }}</p>
                    </div>
                    <div class="neo-inset p-3 rounded-2xl">
                        <span class="text-[10px] uppercase font-bold text-[var(--text-muted)]">Transferencias</span>
                        <p class="font-mono font-extrabold text-sm text-[var(--text-primary)] mt-1">{{ (int) ($operations['transferencias'] ?? 0) }} movs.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ULTIMAS VENTAS TABLE -->
    <div class="neo-card rounded-3xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-base text-[var(--text-primary)] flex items-center gap-2">
                <i data-lucide="receipt" class="w-5 h-5 text-blue-600 dark:text-amber-400"></i>
                <span>Últimas ventas registradas en BD</span>
            </h3>
            <a href="{{ route('sales.history') }}" class="text-xs font-bold text-blue-600 dark:text-amber-400 hover:underline">Ver todas &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="border-b border-[var(--border-hairline)] text-[11px] font-display font-bold uppercase tracking-wider text-[var(--text-muted)] bg-[var(--bg-surface-alt)]/60">
                        <th class="py-3.5 px-4">Comprobante #</th>
                        <th class="py-3.5 px-4">Fecha y hora</th>
                        <th class="py-3.5 px-4">Cliente</th>
                        <th class="py-3.5 px-4">Total</th>
                        <th class="py-3.5 px-4">Estado</th>
                        <th class="py-3.5 px-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-medium">
                    @forelse($latestSales as $sale)
                    <tr class="border-b border-[var(--border-hairline)] hover:bg-white/40 dark:hover:bg-white/[0.03] transition-colors">
                        <td class="py-3.5 px-4 font-mono font-bold text-blue-600 dark:text-amber-400">{{ $sale->id_venta }}</td>
                        <td class="py-3.5 px-4 text-[var(--text-muted)]">{{ \Illuminate\Support\Carbon::parse($sale->fecha_venta)->format('d/m/Y H:i') }}</td>
                        <td class="py-3.5 px-4">{{ $sale->cliente ?? 'Venta sin cliente' }}</td>
                        <td class="py-3.5 px-4 font-mono font-bold text-[var(--text-primary)]">S/ {{ number_format((float) ($sale->total_venta ?? 0), 2, ',', '.') }}</td>
                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $sale->estado === 'completada' ? 'bg-emerald-500/15 text-emerald-600' : 'bg-amber-500/15 text-amber-600' }}">
                                {{ $sale->estado === 'completada' ? 'Completada' : ucfirst($sale->estado) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <a href="{{ route('sales.ticket', $sale->id_venta) }}" class="neo-btn px-3 py-1 rounded-xl text-xs font-bold inline-flex items-center gap-1">
                                <i data-lucide="printer" class="w-3 h-3"></i> Imprimir
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[var(--text-muted)] text-xs">
                            No hay ventas registradas en este periodo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Datos inyectados desde Laravel
    const chartLabels = @json($chartLabels);
    const chartValues = @json($chartValues);
    const topProductsNames = @json(collect($topProducts)->take(5)->pluck('nombre')->toArray());
    const topProductsUnits = @json(collect($topProducts)->take(5)->pluck('unidades')->toArray());

    let salesChartInstance = null;
    let paymentChartInstance = null;
    let topProductsChartInstance = null;

    function initCharts() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#cbd5e1' : '#475569';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(148, 163, 184, 0.15)';
        const primaryColor = isDark ? '#fbbf24' : '#2563eb';

        // 1. Sales Trend Line Chart
        const ctxSales = document.getElementById('salesTrendChart').getContext('2d');
        const gradient = ctxSales.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, isDark ? 'rgba(245, 158, 11, 0.35)' : 'rgba(37, 99, 235, 0.35)');
        gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

        if (salesChartInstance) salesChartInstance.destroy();
        salesChartInstance = new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: chartLabels.length > 0 ? chartLabels : ['Sin datos'],
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: chartValues.length > 0 ? chartValues : [0],
                    borderColor: primaryColor,
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: primaryColor,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'JetBrains Mono', size: 11 } } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'JetBrains Mono', size: 11 }, callback: v => 'S/ ' + v } }
                }
            }
        });

        // 2. Payment Methods Doughnut Chart
        const ctxPayment = document.getElementById('paymentMethodsChart').getContext('2d');
        if (paymentChartInstance) paymentChartInstance.destroy();
        paymentChartInstance = new Chart(ctxPayment, {
            type: 'doughnut',
            data: {
                labels: ['Yape', 'Efectivo', 'Tarjeta'],
                datasets: [{
                    data: [55, 30, 15],
                    backgroundColor: ['#10b981', '#2563eb', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '72%'
            }
        });

        // 3. Top Products Bar Chart
        const ctxTop = document.getElementById('topProductsChart').getContext('2d');
        if (topProductsChartInstance) topProductsChartInstance.destroy();
        topProductsChartInstance = new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: topProductsNames.length > 0 ? topProductsNames : ['Sin datos'],
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: topProductsUnits.length > 0 ? topProductsUnits : [0],
                    backgroundColor: isDark ? '#60a5fa' : '#3b82f6',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'JetBrains Mono', size: 11 } } }
                }
            }
        });
    }

    // Inicializar gráficos al cargar
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initCharts, 100);
    });

    // Escuchar cambios de tema para actualizar colores de gráficos
    window.addEventListener('themeChanged', function(e) {
        setTimeout(initCharts, 50);
    });
</script>
@endpush
