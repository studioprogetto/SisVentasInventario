@extends('layouts.app')
@section('title', 'Dashboard &mdash; '.config('business.name', 'Studio & Progetto'))
@section('content')
@php
    $user = auth()->user();
    $userName = trim((string) ($user->nombre_completo ?? $user->username ?? 'Usuario'));
    $firstName = explode(' ', $userName)[0] ?? 'Usuario';
    $cards = $summary['cards'] ?? [];
    $chartPoints = collect($summary['sales_series'] ?? [])->values();
    $reportDates = ['desde' => $summary['since'], 'hasta' => $summary['until']];
    $topMax = max(1, collect($summary['top_products'] ?? [])->max('unidades') ?? 0);
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
                @if($card['trend'] !== null)
                <div class="stat-trend {{ $trendDirection }}">{{ $trendText }} vs. per&iacute;odo previo</div>
                @else
                <small>@if($card['icon'] === 'wallet')Solo líneas con costo documentado. {{ $summary['profit_missing'] }} líneas sin documentación completa. No es utilidad neta.@elseSin base positiva de comparación en el periodo previo.@endif</small>
                @endif
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
                @can('reportes_ver_rentabilidad')<a href="{{ route('reports.show', ['type' => 'rentabilidad'] + $reportDates) }}">Ver rentabilidad documentada</a>@endcan
            </div>

            <div class="dashboard-chart"><canvas id="dashboard-sales" aria-label="Ventas diarias del periodo" role="img"></canvas></div>
            <details class="daily-data"><summary>Ver importes diarios</summary><table class="sales-table"><tbody>@foreach($chartPoints as $point)<tr><td>{{ $point['label'] }}</td><td>{{ number_format($point['value'], 2, ',', '.') }}</td></tr>@endforeach</tbody></table></details>
            <svg hidden class="line-chart" viewBox="0 0 780 250" role="img" aria-label="Resumen de ventas del periodo">
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
                @can('productos_ver_lista')<a href="{{ route('reports.show', ['type' => 'inventario'] + $reportDates) }}">Ver inventario</a>@endcan
            </div>

            <ol class="rank-list">
                @foreach(($summary['top_products'] ?? []) as $product)
                    @php $width = max(0, min(100, (float) $product->unidades / $topMax * 100)); @endphp
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

    <div class="dashboard-intelligence">
        <section class="panel"><div class="panel-header"><div class="panel-title"><x-icon name="wallet" /><h3>Métodos de pago</h3></div>@can('reportes_ver_metodos_pago')<a href="{{ route('reports.show', ['type' => 'metodos-pago'] + $reportDates) }}">Detalle</a>@endcan</div><p class="data-note">Cobro base de ventas completadas, agrupado por moneda y canal; no es saldo en caja.</p><div class="table-wrap"><table><thead><tr><th>Canal / moneda</th><th>Operaciones</th><th>Cobro base</th></tr></thead><tbody>@forelse($summary['payments'] as $payment)<tr><td>{{ $payment->metodo_pago }} / {{ $payment->moneda_codigo }}</td><td>{{ $payment->operaciones }}</td><td>{{ $payment->cobro_base === null ? 'No documentado' : number_format((float) $payment->cobro_base, 2, ',', '.') }}</td></tr>@empty<tr><td colspan="3">Sin operaciones completadas.</td></tr>@endforelse</tbody></table></div></section>
        <section class="panel"><div class="panel-header"><div class="panel-title"><x-icon name="wallet" /><h3>Flujo de caja y balance</h3></div>@can('reportes_ver_caja')<a href="{{ route('reports.show', ['type' => 'caja'] + $reportDates) }}">Arqueo</a>@endcan</div><p class="data-note">Saldo consolidado no disponible: ventas, compras y saldos de cambios no equivalen a efectivo. Consulta turnos y movimientos documentados.</p>@can('reportes_ver_caja')<a class="btn btn-secondary" href="{{ route('reports.show', ['type' => 'movimientos-caja'] + $reportDates) }}">Movimientos de caja</a>@endcan</section>
        <section class="panel"><div class="panel-header"><div class="panel-title"><x-icon name="box" /><h3>Salud de inventario</h3></div></div><p class="data-note">Existencias actuales de productos activos, no snapshot del periodo.</p><dl class="inventory-health"><dt>Stock cero o negativo</dt><dd>{{ $summary['inventory_health']->agotados ?? 0 }}</dd><dt>Positivo por reponer</dt><dd>{{ $summary['inventory_health']->reposicion ?? 0 }}</dd><dt>Sobre el mínimo</dt><dd>{{ $summary['inventory_health']->suficiente ?? 0 }}</dd></dl></section>
    </div>
    <div class="content-grid secondary-grid">
        <section class="panel alerts-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="alert" /></span>
                    <h3>Alertas de inventario</h3>
                </div>
                @can('productos_ver_lista')<a href="{{ route('reports.show', ['type' => 'inventario'] + $reportDates) }}">Ver inventario</a>@endcan
            </div>
            <div class="alerts-summary"><strong>{{ number_format((int) ($summary['low_stock'] ?? 0)) }}</strong><span>productos requieren revisión · existencias actuales, no snapshot</span></div>
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
                    <h3>Operaciones del periodo</h3>
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
                        <h4>Saldos de cambios</h4>
                        <strong>S/ {{ number_format((float) ($summary['operations']['devoluciones'] ?? 0), 2, ',', '.') }}</strong>
                        <small>Registradas</small>
                    </div>
                </article>

                <article class="mini-operation transfer">
                    <span class="mini-badge"><x-icon name="swap" /></span>
                    <div>
                        <h4>Entradas y salidas de transferencia</h4>
                        <strong>{{ (int) ($summary['operations']['transferencias'] ?? 0) }} movimientos</strong>
                        <small>Activas</small>
                    </div>
                </article>
            </div>
        </section>


    </div>

    <div class="content-grid bottom-grid">
        <section class="panel table-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="file" /></span>
                    <h3>&Uacute;ltimas ventas</h3>
                </div>
                @can('reportes_ver_ventas')<a href="{{ route('reports.show', ['type' => 'ventas'] + $reportDates) }}">Ver reporte de ventas</a>@endcan
            </div>

            <table class="sales-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Estado</th><th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($summary['latest_sales'] ?? []) as $sale)
                        <tr>
                            <td>{{ $sale->id_venta }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($sale->fecha_venta)->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->cliente }}</td>
                            <td>S/ {{ number_format((float) ($sale->total_venta ?? 0), 2, ',', '.') }}</td>
                            <td><span class="status-pill {{ $sale->estado === 'completada' ? '' : 'pending' }}">{{ $sale->estado === 'completada' ? 'Completada' : ucfirst($sale->estado) }}</span></td><td><a href="{{ route('sales.ticket', $sale->id_venta) }}">Ticket</a></td>
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
@push('styles')
<style>
.studio-dashboard{display:grid;gap:24px}.studio-dashboard .welcome-row,.studio-dashboard .panel,.studio-dashboard .stat-card{background:var(--surface);border:1px solid var(--line);box-shadow:var(--shadow);border-radius:24px;backdrop-filter:none;transition:transform .25s,box-shadow .25s}.studio-dashboard .welcome-row{padding:24px;display:flex;justify-content:space-between;gap:24px}.studio-dashboard .welcome-copy{display:flex;align-items:center;gap:16px}.studio-dashboard .welcome-copy h2{margin:0;font-size:1.6rem}.studio-dashboard .welcome-copy p{font-size:.8rem}.studio-dashboard .welcome-avatar{padding:16px;border-radius:18px;box-shadow:var(--shadow);color:var(--accent)}.studio-dashboard .stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}.studio-dashboard .stat-card{padding:22px}.studio-dashboard .stat-card:hover{transform:translateY(-3px)}.studio-dashboard .stat-head{display:flex;gap:12px;align-items:center}.studio-dashboard .stat-value{font:800 clamp(1.3rem,2vw,1.9rem) monospace;margin:16px 0}.studio-dashboard .stat-label,.studio-dashboard small{font-size:.7rem;color:var(--muted)}.studio-dashboard .stat-trend{display:inline-block;padding:6px 10px;border-radius:20px;background:var(--green-soft);color:var(--green);font-size:.65rem}.studio-dashboard .content-grid{display:grid;gap:24px}.studio-dashboard .top-grid{grid-template-columns:minmax(0,2fr) minmax(0,1fr)}.studio-dashboard .panel{padding:22px;min-width:0}.studio-dashboard .panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px}.studio-dashboard .panel-title{display:flex;align-items:center;gap:10px}.studio-dashboard h3{font-size:.95rem;margin:0}.studio-dashboard .panel-header a{font-size:.7rem}.studio-dashboard .panel-icon{color:var(--accent)}.studio-dashboard .dashboard-chart{height:300px;position:relative}.studio-dashboard .line-chart,.studio-dashboard .chart-labels,.studio-dashboard .promo-panel{display:none}.studio-dashboard .chart-summary{display:flex;justify-content:space-between;color:var(--muted);font-size:.8rem}.studio-dashboard .rank-list{display:grid;gap:18px;padding:0;list-style:none}.studio-dashboard .rank-meta,.studio-dashboard .rank-stats{display:flex;gap:8px;justify-content:space-between;font-size:.7rem}.studio-dashboard .rank-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.studio-dashboard .progress-bar{height:8px;border-radius:20px;box-shadow:var(--inset);margin-top:8px;overflow:hidden}.studio-dashboard .progress-bar span{display:block;height:100%;background:var(--accent);transition:width .7s}.studio-dashboard .dashboard-intelligence{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}.studio-dashboard .data-note{font-size:.75rem;color:var(--muted)}.studio-dashboard .table-wrap table{min-width:360px}.studio-dashboard .table-wrap thead th{background:var(--raised);color:var(--muted)}.studio-dashboard .inventory-health{display:grid;grid-template-columns:1fr auto;gap:16px;font-size:.8rem}.studio-dashboard .inventory-health dd{margin:0;font-weight:800}.studio-dashboard .secondary-grid{grid-template-columns:minmax(0,1fr) minmax(0,1.4fr)}.studio-dashboard .bottom-grid{grid-template-columns:1fr}.studio-dashboard .operation-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.studio-dashboard .mini-operation{padding:16px;border-radius:16px;background:var(--surface);box-shadow:var(--inset)}.studio-dashboard .mini-operation h4{font-size:.75rem}.studio-dashboard .mini-operation strong{font-family:monospace}.studio-dashboard .inventory-alert-list{padding:0}.studio-dashboard .inventory-alert-list li{box-shadow:var(--inset);background:var(--surface)}.studio-dashboard .daily-data{margin-top:16px;font-size:.75rem}.studio-dashboard .period-filter{box-shadow:none;flex-wrap:wrap}.studio-dashboard .period-filter input{background:var(--surface);box-shadow:var(--inset);color:var(--text)}@media(max-width:1200px){.studio-dashboard .stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.studio-dashboard .welcome-row{flex-direction:column}.studio-dashboard .dashboard-intelligence{grid-template-columns:1fr 1fr}}@media(max-width:850px){.studio-dashboard .top-grid,.studio-dashboard .secondary-grid,.studio-dashboard .dashboard-intelligence{grid-template-columns:1fr}}@media(max-width:540px){.studio-dashboard .stats-grid,.studio-dashboard .operation-grid{grid-template-columns:1fr}.studio-dashboard .panel,.studio-dashboard .welcome-row{padding:18px}.studio-dashboard .panel-header{flex-wrap:wrap}.studio-dashboard .period-filter{width:100%}.studio-dashboard .sales-table{min-width:580px}}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    if (!window.Chart) return;
    const points = @json($summary['sales_series']);
    const root = document.querySelector('.studio-dashboard');
    let chart;
    const draw = () => {
        if (chart) chart.destroy();
        const css = getComputedStyle(root);
        chart = new Chart(root.querySelector('#dashboard-sales'), {
            type: 'line',
            data: {labels: points.map(p => p.label), datasets: [{label: 'Total de ventas completadas (moneda registrada)', data: points.map(p => p.value), borderColor: css.getPropertyValue('--accent').trim(), backgroundColor: css.getPropertyValue('--accent-soft').trim(), fill: true, tension: .35, pointRadius: 2}]},
            options: {responsive: true, maintainAspectRatio: false, animation: {duration: matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 700}, plugins: {legend: {labels: {color: css.getPropertyValue('--text').trim()}}}, scales: {x: {ticks: {color: css.getPropertyValue('--muted').trim()}}, y: {ticks: {color: css.getPropertyValue('--muted').trim()}, beginAtZero: true}}}
        });
    };
    draw();
    new MutationObserver(draw).observe(document.documentElement, {attributes: true, attributeFilter: ['data-theme']});
})();
</script>
@endpush
