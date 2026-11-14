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
