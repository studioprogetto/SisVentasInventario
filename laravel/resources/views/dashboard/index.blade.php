@extends('layouts.app')
@section('title', 'Dashboard ejecutivo')
@section('content')
@php
    $reportDates = ['desde' => $summary['since'], 'hasta' => $summary['until']];
    $topMax = max(1, $summary['top_products']->max('unidades') ?? 0);
@endphp
<div class="studio-dashboard" data-legacy="{{ json_encode($summary['legacy_series']) }}" data-sales_series="{{ json_encode($summary['sales_series']) }}" data-spark_series="{{ json_encode($summary['spark_series']) }}" data-payments="{{ json_encode($summary['payments']) }}">
    <div class="executive-command">
        <div><h1>¡Hola, {{ explode(' ', auth()->user()->nombre_completo)[0] }}!</h1><p>Resumen ejecutivo · {{ $summary['period_label'] }}</p></div>
        <form method="GET" action="{{ route('home') }}" class="executive-filter">
            <label>Desde<input type="date" name="desde" required value="{{ $summary['since'] }}"></label>
            <label>Hasta<input type="date" name="hasta" max="{{ today()->toDateString() }}" required value="{{ $summary['until'] }}"></label>
            <button type="submit">Actualizar</button>
        </form>
    </div>
    <div class="executive-kpis">
        @foreach($summary['cards'] as $card)
        <article class="executive-card kpi">
            <div><span class="eyebrow">{{ $card['label'] }}</span><strong class="kpi-value">{{ $card['value'] }}</strong>
                @if($loop->index === 0)
                <small>Base PEN documentada · {{ $summary['sales_missing'] }} ventas sin base</small>
                @elseif($loop->index === 1)
                <small>{{ $summary['sales_count'] }} ventas completadas</small>
                @elseif($loop->index === 2)
                <small>Clientes registrados únicos del periodo</small>
                @else
                <small>Solo costo histórico documentado · {{ $summary['profit_missing'] }} líneas sin documentación. No es utilidad neta.</small>
                @endif
                @if($card['trend'] !== null && $loop->index === 0)
                <small class="{{ $card['trend'] < 0 ? 'negative' : 'positive' }}">{{ $card['trend'] > 0 ? '+' : '' }}{{ $card['trend'] }}% vs. base documentada previa</small>
                @endif
            </div>
            @if($loop->index < 3)<div class="spark"><canvas id="executive-spark-{{ $loop->index }}" role="img" aria-label="Tendencia diaria de {{ $card['label'] }}"></canvas></div>@endif
        </article>
        @endforeach
    </div>
    <div class="executive-grid">
        <section class="executive-card sales-panel">
            <div class="executive-heading"><h2><x-icon name="chart" /> Ingresos por día</h2><button type="button" id="income-cumulative" aria-pressed="false">Ver acumulado</button><button type="button" id="executive-chart-mode" aria-pressed="false">Barras / Área</button></div>
            <p class="data-note">Ventas completadas por día. Importes registrados en soles (PEN). Los importes son anteriores a devoluciones.</p>
            <div class="sales-chart"><canvas id="executive-sales" role="img" aria-label="Ventas diarias en soles documentados"></canvas></div>
            <details><summary>Ver importes diarios</summary><div class="executive-scroll"><table><thead><tr><th>Fecha</th><th>Ingreso PEN</th><th>Sin moneda</th></tr></thead><tbody>@foreach($summary['sales_series'] as $point)<tr><td>{{ $point['label'] }}</td><td>S/ {{ number_format($point['value'], 2, ',', '.') }}</td><td>{{ number_format($summary['legacy_series'][$loop->index]['value'], 2, ',', '.') }}</td></tr>@endforeach</tbody></table></div></details>
        </section>
        <section class="executive-card products-panel">
            <div class="executive-heading"><h2><x-icon name="box" /> Productos más vendidos</h2>@can('productos_ver_lista')<a href="{{ route('products.index') }}">Inventario</a>@endcan</div>
            <p class="data-note">Unidades de ventas completadas, sin netear devoluciones.</p>
            <ol class="executive-ranking">
                @forelse($summary['top_products'] as $product)
                <li><div><strong>{{ $loop->iteration }}. {{ $product->nombre }}</strong><span>{{ $product->unidades }} und.</span></div><div class="executive-track"><span style="width: {{ min(100, max(0, $product->unidades / $topMax * 100)) }}%"></span></div></li>
                @empty<li>Sin productos vendidos en el periodo.</li>@endforelse
            </ol>
        </section>
        <section class="executive-card payments-panel">
            <div class="executive-heading"><h2><x-icon name="wallet" /> Métodos de pago</h2>@can('reportes_ver_metodos_pago')<a href="{{ route('reports.show', ['type' => 'metodos-pago'] + $reportDates) }}">Detalle</a>@endcan</div>
            <div class="payment-chart"><canvas id="executive-payments" role="img" aria-label="Operaciones por canal y moneda"></canvas><span>{{ $summary['sales_count'] }}<small>OPERACIONES</small></span></div>
            <p class="data-note">Moneda de venta / cobro base en PEN, no saldo de caja.</p>
            <div class="executive-scroll"><table><thead><tr><th>Canal / moneda</th><th>Ops.</th><th>Base PEN</th></tr></thead><tbody>
                @forelse($summary['payments'] as $payment)<tr><td>{{ $payment->metodo_pago }} / {{ $payment->moneda_codigo ?: 'Sin documentar' }}</td><td>{{ $payment->operaciones }}</td><td>{{ $payment->cobro_base === null ? 'Sin base' : 'S/ '.number_format((float) $payment->cobro_base, 2, ',', '.') }}</td></tr>@empty<tr><td colspan="3">Sin operaciones completadas.</td></tr>@endforelse
            </tbody></table></div>
        </section>

    </div>
</div>
@endsection
@push('styles')
<style>
.studio-dashboard{--text:#f8fafc;--muted:#94a3b8;--accent:#f59e0b;--green:#10b981;--red:#fb7185;--line:rgba(255,255,255,.08);--surface:#0f172a;--inset-bg:#070d1a;--shadow:7px 7px 16px rgba(2,5,12,.94),-6px -6px 14px rgba(28,46,82,.36),inset 1px 1px 1px rgba(255,255,255,.06);background:#0a1120;color:var(--text);padding:20px;border-radius:22px;display:grid;gap:16px;font-family:Inter,'Segoe UI',sans-serif;min-width:0;color-scheme:dark}
.studio-dashboard *{box-sizing:border-box}.studio-dashboard h1,.studio-dashboard h2,.studio-dashboard p{margin:0}.studio-dashboard h1{font-size:1.3rem;font-weight:800}.studio-dashboard h2{font-size:.85rem;font-weight:750;display:flex;align-items:center;gap:7px;line-height:1.4}.studio-dashboard a{color:var(--accent);font-size:.7rem;font-weight:650}.studio-dashboard .icon{width:18px;height:18px;color:var(--accent)}.studio-dashboard small{display:block;color:var(--muted);font-size:.65rem;line-height:1.5}.studio-dashboard .positive{color:var(--green)}.studio-dashboard .negative{color:var(--red)}
.studio-dashboard .executive-command{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}.studio-dashboard .executive-command p{color:var(--muted);font-size:.75rem;margin-top:5px}.studio-dashboard .executive-filter{display:flex;align-items:end;gap:8px;flex-wrap:wrap}.studio-dashboard .executive-filter label{color:var(--accent);text-transform:uppercase;font-size:.6rem;letter-spacing:.08em;display:grid;gap:4px}.studio-dashboard input{width:145px;min-height:36px;padding:6px 9px;font-size:.75rem;font-family:monospace;color:var(--text);border:1px solid var(--line);border-radius:10px;background:var(--inset-bg);box-shadow:inset 3px 3px 7px #02050c,inset -2px -2px 6px #1a2a4a}.studio-dashboard button{background:linear-gradient(135deg,#f59e0b,#d97706);color:#0a1120;border:1px solid #fef3c759;box-shadow:4px 4px 10px #02050c;min-height:36px;padding:6px 11px;border-radius:10px;font-size:.7rem}.studio-dashboard #executive-chart-mode{background:var(--surface);color:var(--accent);border-color:var(--line);white-space:nowrap}.studio-dashboard button::before{display:none}
.studio-dashboard .executive-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.studio-dashboard .executive-card{padding:16px;border-radius:17px;border:1px solid var(--line);background:var(--surface);box-shadow:var(--shadow);min-width:0;transition:border-color .25s,transform .25s}.studio-dashboard .executive-card:hover{border-color:#f59e0b42}.studio-dashboard .kpi{display:flex;align-items:center;gap:8px;min-height:128px}.studio-dashboard .kpi>div:first-child{min-width:0;flex:1}.studio-dashboard .eyebrow{font-size:.6rem;font-weight:750;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}.studio-dashboard .eyebrow::before{content:'';display:inline-block;width:6px;height:6px;background:var(--accent);border-radius:50%;margin-right:5px}.studio-dashboard .kpi:nth-child(2) .eyebrow::before{background:var(--green)}.studio-dashboard .kpi:nth-child(3) .eyebrow::before{background:#fef3c7}.studio-dashboard .kpi-value{display:block;font-size:clamp(1.1rem,1.65vw,1.7rem);font-weight:800;margin:6px 0;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}.studio-dashboard .kpi:last-child .kpi-value{color:var(--accent)}.studio-dashboard .spark{width:65px;height:45px;flex-shrink:0;position:relative}
.studio-dashboard .executive-grid{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,4fr) minmax(0,3fr);gap:14px;align-items:start}.studio-dashboard .executive-heading{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}.studio-dashboard .executive-heading>span{font-size:.65rem;color:var(--muted);white-space:nowrap}.studio-dashboard .data-note{font-size:.65rem;color:var(--muted);line-height:1.5;margin:8px 0 12px}.studio-dashboard .sales-chart{height:215px;position:relative}.studio-dashboard .payment-chart{height:145px;position:relative;margin:12px auto;max-width:220px}.studio-dashboard .payment-chart>span{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;font-size:1.2rem;font-weight:800}.studio-dashboard .payment-chart small{font-size:.5rem}.studio-dashboard .health-chart{height:100px;position:relative}.studio-dashboard canvas{display:block;width:100%!important;height:100%!important}.studio-dashboard details{margin-top:10px;font-size:.65rem;color:var(--muted)}
.studio-dashboard .executive-ranking,.studio-dashboard .executive-alerts{list-style:none;padding:0;margin:0;display:grid;gap:9px}.studio-dashboard .executive-ranking li,.studio-dashboard .executive-alerts li{padding:10px;border-radius:11px;background:var(--inset-bg);border:1px solid var(--line);box-shadow:inset 3px 3px 7px #02050c,inset -2px -2px 6px #1a2a4a;font-size:.7rem}.studio-dashboard .executive-ranking li>div:first-child{display:flex;justify-content:space-between;gap:8px}.studio-dashboard .executive-ranking strong{font-size:.65rem;line-height:1.5}.studio-dashboard .executive-ranking span:not(.executive-track span){color:var(--green);white-space:nowrap;font-family:monospace}.studio-dashboard .executive-track{height:5px;border-radius:9px;background:#1e293b;margin-top:7px;overflow:hidden}.studio-dashboard .executive-track>span{display:block;height:100%;background:linear-gradient(90deg,#f59e0b,#10b981);border-radius:9px}.studio-dashboard .executive-alerts li{display:flex;align-items:center;justify-content:space-between;gap:8px}.studio-dashboard .executive-alerts small{color:var(--red);margin-top:4px}.studio-dashboard .alert-count{border:1px solid #fb718540;background:#fb718518;color:var(--red);padding:2px 6px;border-radius:12px;font-size:.65rem}
.studio-dashboard table{width:100%;border-collapse:collapse;font-size:.65rem;color:var(--text)}.studio-dashboard th{color:var(--muted);font-size:.55rem;text-transform:uppercase;text-align:left;padding:6px}.studio-dashboard td{padding:9px 6px;border-top:1px solid var(--line);vertical-align:middle;font-variant-numeric:tabular-nums}.studio-dashboard td:first-child strong{color:var(--accent)}.studio-dashboard .executive-scroll{max-height:230px;overflow:auto;scrollbar-width:thin;scrollbar-color:#334155 transparent}.studio-dashboard .sale-state{font-size:.6rem}.studio-dashboard .health-values{display:grid;grid-template-columns:1fr auto;font-size:.7rem;gap:6px;margin:10px 0}.studio-dashboard .health-values dd{margin:0;color:var(--green);font-family:monospace}.studio-dashboard .health-values dt{color:var(--muted)}.studio-dashboard .executive-flow{display:flex;flex-wrap:wrap;gap:10px;padding-top:12px;border-top:1px solid var(--line)}
@media(min-width:1450px){.studio-dashboard .executive-grid{grid-template-columns:repeat(12,minmax(0,1fr))}.studio-dashboard .sales-panel{grid-column:span 5}.studio-dashboard .products-panel{grid-column:span 4}.studio-dashboard .payments-panel{grid-column:span 3}.studio-dashboard .health-panel,.studio-dashboard .alerts-panel,.studio-dashboard .recent-panel{grid-column:span 4}}
@media(max-width:1250px){.studio-dashboard .executive-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.studio-dashboard .executive-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.studio-dashboard .sales-panel{grid-column:1/-1}.studio-dashboard .recent-panel{grid-column:1/-1}}
@media(max-width:650px){.studio-dashboard{padding:12px;border-radius:16px;gap:12px}.studio-dashboard .executive-kpis,.studio-dashboard .executive-grid{grid-template-columns:minmax(0,1fr)}.studio-dashboard .executive-card{padding:14px}.studio-dashboard .kpi{min-height:105px}.studio-dashboard .executive-filter{width:100%}.studio-dashboard .executive-filter label{flex:1}.studio-dashboard input{width:100%;min-width:0}.studio-dashboard .executive-filter button{width:100%}.studio-dashboard .executive-heading{flex-wrap:wrap}.studio-dashboard .sales-chart{height:200px}.studio-dashboard table{min-width:280px}.studio-dashboard .kpi-value{font-size:1.5rem}}
@media(prefers-reduced-motion:reduce){.studio-dashboard .executive-card{transition:none}}
</style>
@endpush
@push('scripts')
<script defer src="{{ asset('js/vendor/chart.umd.min.js') }}"></script>
<script defer src="{{ asset('js/dashboard-operativo.js') }}?v={{ filemtime(public_path('js/dashboard-operativo.js')) }}"></script>
@endpush
