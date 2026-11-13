@extends('layouts.app')
@section('title', 'Dashboard — '.config('business.name', 'Studio & Progetto'))
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
    $stockTotal = collect($summary['stock_by_warehouse'] ?? [])->sum('stock_total');
@endphp
<div class="studio-dashboard">
    <div class="welcome-row">
        <div class="welcome-copy">
            <span class="welcome-avatar"><x-icon name="users" /></span>
            <div>
                <h2>¡Hola, {{ $firstName }}!</h2>
                <p>Aquí tienes un resumen actualizado de tu negocio.</p>
            </div>
        </div>

        <div class="date-pill" aria-label="Rango de fechas del periodo">
            <span class="mini-calendar"><x-icon name="clock" /></span>
            <span>{{ $summary['period_label'] ?? 'Periodo actual' }}</span>
            <x-icon name="arrow" />
        </div>
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
                <div class="stat-trend {{ $trendDirection }}"><span>{{ $trendValue >= 0 ? '↑' : '↓' }}</span> {{ $trendText }} vs. período previo</div>
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
                <div class="range-tabs" aria-label="Rango del gráfico">
                    <button class="active" type="button">7 días</button>
                    <button type="button">30 días</button>
                    <button type="button">3 meses</button>
                </div>
            </div>

            <div class="chart-summary">
                <div class="chart-pill">{{ $cards[0]['value'] ?? 'S/ 0.00' }}</div>
                <small>{{ now()->format('d/m/Y') }}</small>
            </div>

            <svg class="line-chart" viewBox="0 0 780 250" role="img" aria-label="Resumen de ventas del periodo">
                <defs>
                    <linearGradient id="areaBlue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3c78ff" stop-opacity="0.22"/>
                        <stop offset="100%" stop-color="#3c78ff" stop-opacity="0.01"/>
                    </linearGradient>
                    <linearGradient id="areaPurple" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#a58dff" stop-opacity="0.23"/>
                        <stop offset="100%" stop-color="#a58dff" stop-opacity="0.02"/>
                    </linearGradient>
                </defs>
                <g class="grid-lines">
                    <line x1="40" y1="25" x2="740" y2="25"/>
                    <line x1="40" y1="75" x2="740" y2="75"/>
                    <line x1="40" y1="125" x2="740" y2="125"/>
                    <line x1="40" y1="175" x2="740" y2="175"/>
                    <line x1="40" y1="225" x2="740" y2="225"/>
                </g>

                <polyline fill="url(#areaBlue)" stroke="none" points="40,225 40,175 180,125 320,90 470,110 610,80 740,52 740,225"/>
                <polyline fill="none" stroke="#3c78ff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" points="40,175 180,125 320,90 470,110 610,80 740,52"/>
                <polyline fill="none" stroke="#a58dff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" points="40,190 180,145 320,115 470,130 610,120 740,95" opacity="0.9"/>
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
                    <h3>Productos más vendidos</h3>
                </div>
                <a href="#">Ver todos</a>
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
        <section class="panel stock-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="box" /></span>
                    <h3>Stock por almacén</h3>
                </div>
                <a href="#">Ver inventario</a>
            </div>

            <div class="stock-layout">
                <div class="donut-wrap">
                    <div class="donut-chart">
                        <span>{{ number_format((int) $stockTotal, 0, ',', '.') }}</span>
                        <small>Unidades</small>
                    </div>
                </div>

                <ul class="stock-breakdown">
                    @foreach(($summary['stock_by_warehouse'] ?? []) as $warehouse)
                        @php $warehouseStock = (float) ($warehouse->stock_total ?? 0); $percentage = $stockTotal > 0 ? round(($warehouseStock / $stockTotal) * 100, 0) : 0; @endphp
                        <li><span class="dot {{ ['blue','cyan','green','yellow','lavender'][$loop->index % 5] }}"></span> Almacén {{ $warehouse->almacen ?? $loop->iteration }} <strong>{{ $percentage }}%</strong></li>
                    @endforeach
                </ul>
            </div>
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
                        <small>Última tasa</small>
                    </div>
                @endforeach
            </div>

            <svg class="mini-chart" viewBox="0 0 300 120" role="img" aria-label="Evolución del tipo de cambio">
                <path d="M0 80 C45 72, 58 60, 90 64 S150 58, 180 52 S225 64, 300 42" fill="none" stroke="#3c78ff" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </section>
    </div>

    <div class="content-grid bottom-grid">
        <section class="panel table-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-icon"><x-icon name="file" /></span>
                    <h3>Últimas ventas</h3>
                </div>
                <a href="#">Ver todas</a>
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
            <h3>¡Gracias por confiar en {{ config('app.name', config('business.name', 'Studio & Progetto')) }}!</h3>
            <p>Seguimos creciendo juntos.</p>
            <div class="promo-illustration">
                <div class="pencil"></div>
                <div class="cup"></div>
                <div class="notebook"></div>
            </div>
        </aside>
    </div>
</div>

<style>
.studio-dashboard {
    display: grid;
    gap: 1.25rem;
    padding: 1.2rem 0 0;
}

.welcome-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-height: 76px;
    padding: 0.4rem 0;
}

.welcome-copy {
    display: flex;
    align-items: center;
    gap: 0.9rem;
}

.welcome-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 14px;
    background: linear-gradient(135deg, #ffd9a3, #ffb1a9);
    color: #1f2937;
    box-shadow: 8px 8px 18px rgba(142, 154, 173, 0.18), -4px -4px 12px rgba(255,255,255,0.8);
}

.welcome-copy h2 {
    margin: 0;
    font-size: clamp(1.2rem, 2vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: #1a2a44;
}

.welcome-copy p {
    margin: 0.15rem 0 0;
    color: #63758d;
    font-size: 0.82rem;
}

.date-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.75rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(145, 159, 181, 0.35);
    background: rgba(255, 255, 255, 0.6);
    color: #344561;
    font-size: 0.78rem;
    box-shadow: 6px 6px 14px rgba(148, 166, 188, 0.12), -6px -6px 14px rgba(255,255,255,0.7);
}

.mini-calendar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 8px;
    background: rgba(60, 120, 255, 0.08);
    color: #2d5de3;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.stat-card {
    background: linear-gradient(180deg, rgba(255,255,255,0.75), rgba(228,236,247,0.82));
    border: 1px solid rgba(168, 180, 197, 0.25);
    border-radius: 20px;
    padding: 1.1rem 1.1rem 1rem;
    box-shadow: 10px 10px 24px rgba(173, 187, 207, 0.22), -8px -8px 18px rgba(255,255,255,0.7);
}

.stat-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.8rem;
}

.stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    color: white;
}

.stat-icon.primary { background: linear-gradient(135deg, #3c78ff, #2d5ee2); }
.stat-icon.purple { background: linear-gradient(135deg, #7b81ff, #5d6eff); }
.stat-icon.blue { background: linear-gradient(135deg, #5ec3ff, #3fa4ff); }
.stat-icon.amber { background: linear-gradient(135deg, #fdc45f, #f7b348); }

.stat-label {
    font-size: 0.86rem;
    color: #2c3d59;
    font-weight: 700;
}

.stat-value {
    font-size: clamp(1.4rem, 2vw, 2.1rem);
    font-weight: 800;
    letter-spacing: -0.05em;
    color: #1a2a44;
    margin-bottom: 0.4rem;
}

.stat-trend {
    font-size: 0.72rem;
    color: #2d6a47;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.stat-trend span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: rgba(25, 165, 103, 0.12);
}

.stat-trend.up { color: #2d6a47; }
.stat-trend.down { color: #b13d53; }
.stat-trend.down span { background: rgba(177, 61, 83, 0.12); }

.content-grid {
    display: grid;
    gap: 1.25rem;
}

.top-grid {
    grid-template-columns: 1.8fr 1fr;
}

.secondary-grid {
    grid-template-columns: 1.1fr 1.2fr 1fr;
}

.bottom-grid {
    grid-template-columns: 1.7fr 0.8fr;
}

.panel {
    background: linear-gradient(180deg, rgba(245,248,252,0.92), rgba(233,239,247,0.9));
    border: 1px solid rgba(171,182,198,0.25);
    border-radius: 20px;
    padding: 1rem 1.1rem;
    box-shadow: 10px 10px 24px rgba(172, 185, 206, 0.18), -8px -8px 16px rgba(255,255,255,0.8);
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.85rem;
}

.panel-title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.panel-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    color: #2340a8;
    background: rgba(60, 120, 255, 0.08);
}

.panel-icon.purple {
    color: #5b5ff4;
    background: rgba(120, 105, 255, 0.1);
}

.panel-title h3 {
    margin: 0;
    font-size: 1.05rem;
    color: #1a2a44;
    font-weight: 800;
}

.panel-header a,
.panel-header button {
    font-size: 0.72rem;
    color: #3556ab;
    text-decoration: none;
    font-weight: 700;
    background: transparent;
    border: 0;
    cursor: pointer;
}

.range-tabs {
    display: inline-flex;
    gap: 0.4rem;
    background: rgba(255,255,255,0.4);
    border: 1px solid rgba(151,164,186,0.35);
    border-radius: 999px;
    padding: 0.25rem;
}

.range-tabs button {
    border: 0;
    background: transparent;
    color: #5c7088;
    border-radius: 999px;
    padding: 0.45rem 0.8rem;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
}

.range-tabs .active {
    background: linear-gradient(135deg, #4d79ff, #2d5ee2);
    color: white;
    box-shadow: 0 8px 16px rgba(61, 112, 255, 0.28);
}

.chart-summary {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin: 0.7rem 0 0.2rem;
}

.chart-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.55rem 0.8rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #233d6b, #1b2d4e);
    color: white;
    font-size: 0.9rem;
    font-weight: 800;
    box-shadow: 10px 10px 18px rgba(26,42,68,0.15);
}

.chart-summary small {
    color: #5c7088;
    font-size: 0.72rem;
    font-weight: 700;
    margin-top: 0.7rem;
}

.line-chart {
    width: 100%;
    height: 250px;
    display: block;
    margin-top: 0.5rem;
}

.grid-lines line {
    stroke: rgba(108, 124, 145, 0.18);
    stroke-width: 1;
}

.chart-points circle {
    fill: #3c78ff;
}

.chart-labels {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.3rem;
    margin-top: 0.4rem;
    color: #72839d;
    font-size: 0.72rem;
    text-align: center;
}

.rank-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.8rem;
}

.rank-list li {
    display: grid;
    gap: 0.55rem;
    padding-bottom: 0.4rem;
}

.rank-meta {
    display: grid;
    align-items: center;
    grid-template-columns: auto auto 1fr;
    gap: 0.55rem;
    font-size: 0.8rem;
    color: #2b3a53;
    font-weight: 700;
}

.rank-index {
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(143, 153, 173, 0.15);
    font-size: 0.62rem;
    color: #42536d;
}

.mini-box {
    display: inline-block;
    width: 22px;
    height: 24px;
    border-radius: 6px;
    box-shadow: inset 0 0 0 1px rgba(0,0,0,0.04);
}

.mini-box.blue { background: linear-gradient(180deg, #dfe9ff, #afc5ff); }
.mini-box.purple { background: linear-gradient(180deg, #e2d7ff, #c8bcff); }
.mini-box.mint { background: linear-gradient(180deg, #dff8f0, #bfece0); }
.mini-box.peach { background: linear-gradient(180deg, #ffe3cf, #ffc7b1); }
.mini-box.green { background: linear-gradient(180deg, #d8f7d8, #bde7bb); }

.rank-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rank-stats {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    color: #5d6d84;
    font-size: 0.72rem;
    font-weight: 700;
}

.progress-bar {
    width: 100%;
    height: 8px;
    border-radius: 999px;
    background: rgba(144, 158, 182, 0.18);
    overflow: hidden;
}

.progress-bar span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #4d7dff, #7a9cff);
    border-radius: inherit;
}

.stock-layout {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 0.8rem;
    align-items: center;
}

.donut-chart {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: conic-gradient(#3c78ff 0 24%, #6dd3ff 24% 44%, #7ee0a5 44% 62%, #f6d56a 62% 77%, #d9c5ff 77% 100%);
    position: relative;
    margin: 0 auto;
    display: grid;
    place-items: center;
    box-shadow: inset 0 0 0 10px rgba(255,255,255,0.4);
}

.donut-chart::before {
    content: "";
    position: absolute;
    inset: 18px;
    background: rgba(245,248,252,0.96);
    border-radius: 50%;
}

.donut-chart span,
.donut-chart small {
    position: relative;
    z-index: 1;
    display: block;
    text-align: center;
}

.donut-chart span {
    font-size: 1.05rem;
    font-weight: 800;
    color: #1c2f4d;
}

.donut-chart small {
    color: #6e7f99;
    font-size: 0.68rem;
}

.stock-breakdown {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.6rem;
    font-size: 0.77rem;
    color: #40506f;
}

.stock-breakdown li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 0.45rem;
}

.dot.blue { background: #3c78ff; }
.dot.cyan { background: #6dd3ff; }
.dot.green { background: #7ee0a5; }
.dot.yellow { background: #f6d56a; }
.dot.lavender { background: #d9c5ff; }

.operation-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.7rem;
    margin-top: 0.35rem;
}

.mini-operation {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.8rem 0.7rem;
    border-radius: 16px;
    background: rgba(255,255,255,0.35);
    border: 1px solid rgba(166,181,205,0.2);
}

.mini-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 12px;
    color: white;
}

.purchase .mini-badge { background: linear-gradient(135deg, #6cc9ab, #59b694); }
.return .mini-badge { background: linear-gradient(135deg, #f7a0b8, #ee7aa7); }
.transfer .mini-badge { background: linear-gradient(135deg, #8f92ff, #6e74ff); }

.mini-operation h4 {
    margin: 0;
    color: #495d7a;
    font-size: 0.72rem;
    font-weight: 700;
}

.mini-operation strong {
    display: block;
    margin-top: 0.15rem;
    color: #1e2d47;
    font-size: 0.96rem;
    font-weight: 800;
}

.mini-operation small {
    color: #2a8d5b;
    font-weight: 700;
}

.ghost-btn {
    background: rgba(52, 87, 172, 0.08);
    color: #2346a6;
    border: 1px solid rgba(52,87,172,0.12);
    border-radius: 12px;
    padding: 0.55rem 0.8rem;
    font-size: 0.7rem;
    font-weight: 700;
}

.exchange-values {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.7rem;
    margin-top: 0.5rem;
}

.exchange-box {
    background: rgba(255,255,255,0.34);
    border: 1px solid rgba(172,182,201,0.18);
    border-radius: 14px;
    padding: 0.75rem 0.7rem;
}

.currency-label {
    display: block;
    font-size: 0.68rem;
    color: #5b6f8d;
    font-weight: 700;
    margin-bottom: 0.4rem;
}

.exchange-box strong {
    display: block;
    color: #1d2d4a;
    font-size: 1.15rem;
    font-weight: 800;
}

.exchange-box small {
    color: #2a875e;
    font-weight: 700;
}

.mini-chart {
    width: 100%;
    height: 84px;
    display: block;
    margin-top: 0.6rem;
}

.sales-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}

.sales-table th,
.sales-table td {
    padding: 0.75rem 0.65rem;
    text-align: left;
    border-bottom: 1px solid rgba(153,170,189,0.2);
}

.sales-table th {
    color: #6b7d96;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 800;
}

.sales-table td {
    color: #2d405f;
    font-weight: 600;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 96px;
    padding: 0.38rem 0.65rem;
    border-radius: 999px;
    background: rgba(24, 120, 96, 0.12);
    color: #1c7d5d;
    border: 1px solid rgba(24,120,96,0.18);
    font-weight: 800;
    font-size: 0.67rem;
}

.status-pill.pending {
    background: rgba(194, 140, 45, 0.12);
    color: #b57d24;
    border-color: rgba(194, 140, 45, 0.18);
}

.promo-panel {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 230px;
    background: linear-gradient(135deg, #1d2d57, #2f4b87 52%, #3d64b2);
    color: white;
    overflow: hidden;
}

.promo-panel h3 {
    margin: 0.5rem 0 0.25rem;
    font-size: 1.4rem;
    letter-spacing: -0.04em;
    line-height: 1.25;
}

.promo-panel p {
    margin: 0;
    opacity: 0.82;
    font-size: 0.82rem;
}

.promo-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255,255,255,0.12);
    color: white;
    border: 1px solid rgba(255,255,255,0.12);
}

.promo-illustration {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 170px;
    height: 120px;
}

.notebook,
.cup,
.pencil {
    position: absolute;
}

.notebook {
    right: 30px;
    bottom: 10px;
    width: 100px;
    height: 80px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f9f3d6, #dfe8ff);
    box-shadow: 0 18px 30px rgba(12,25,49,0.18);
    transform: rotate(10deg);
}

.notebook::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 12px;
    background: linear-gradient(90deg, rgba(102,128,200,0.15) 0 10%, transparent 10% 100%);
}

.cup {
    right: 20px;
    bottom: 0;
    width: 56px;
    height: 56px;
    border-radius: 16px 16px 18px 18px;
    background: linear-gradient(180deg, #d8ebff, #9ec5ff);
    box-shadow: 0 14px 20px rgba(11,26,51,0.2);
}

.cup::before {
    content: "";
    position: absolute;
    width: 48px;
    height: 10px;
    border-radius: 999px;
    background: rgba(255,255,255,0.7);
    left: 4px;
    top: 8px;
}

.pencil {
    left: 18px;
    bottom: 18px;
    width: 110px;
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, #ffd25b, #f5ad2f, #ffdb7c);
    transform: rotate(-28deg);
    box-shadow: 0 10px 18px rgba(11,26,51,0.12);
}

.pencil::before {
    content: "";
    position: absolute;
    right: -6px;
    top: -2px;
    width: 14px;
    height: 16px;
    background: linear-gradient(180deg, #4a7dff, #315dcc);
    border-radius: 0 6px 6px 0;
}

@media (max-width: 1180px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .top-grid,
    .secondary-grid,
    .bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stock-layout {
        grid-template-columns: 1fr;
    }

    .sales-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}

.shell {
    animation: dashboard-enter 0.28s ease;
}

@keyframes dashboard-enter {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection
