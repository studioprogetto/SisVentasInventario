<div class="operational-reports">
        @can('productos_ver_lista')
<section class="panel">
            <div class="executive-heading"><h2><x-icon name="shield" /> Salud de inventario</h2><span>{{ $summary['inventory_health']->total ?? 0 }} ítems</span></div>
            <p class="data-note">Existencias actuales de productos activos, no snapshot del periodo.</p>
            <dl class="health-values">
                @foreach(['suficiente'=>'Sobre mínimo','reposicion'=>'Positivo por reponer','agotados'=>'Cero o negativo'] as $key=>$label)
                    <dt>{{ $label }}</dt><dd><meter aria-label="{{ $label }}" min="0" max="{{ max(1,$summary['inventory_health']->total) }}" value="{{ $summary['inventory_health']->$key ?? 0 }}"></meter> {{ $summary['inventory_health']->$key ?? 0 }}</dd>
                @endforeach
            </dl>
            <div class="executive-flow">@can('compras_ver_lista')<a href="{{ route('purchases.index') }}">Compras</a>@endcan @can('transferencias_crear')<a href="{{ route('transfers.index') }}">{{ $summary['operations']['transferencias'] }} movimientos de transferencia</a>@endcan @can('reportes_ver_caja')<a href="{{ route('reports.show', ['type' => 'movimientos-caja'] + $reportDates) }}">Flujo de caja ↗</a>@endcan</div>
        </section>
        @endcan
@can('reportes_ver_stock_bajo')
<section class="panel">
            <div class="executive-heading"><h2><x-icon name="alert" /> Alertas de inventario <span class="alert-count">{{ $summary['low_stock'] }}</span></h2>@can('reportes_ver_stock_bajo')<a href="{{ route('reports.show', ['type' => 'stock-bajo']) }}">Ver todas</a>@endcan</div>
            <ul class="executive-alerts">
                @forelse($summary['inventory_alerts'] as $alert)<li><div><strong>{{ $alert->nombre }}</strong><small>Stock actual: {{ $alert->stock }} · Mínimo: {{ $alert->stock_minimo }}</small></div>@can('productos_ver_detalle')<a href="{{ route('products.ledger', $alert->id_producto) }}">Ver</a>@endcan</li>@empty<li>No hay productos con stock igual o inferior al mínimo.</li>@endforelse
            </ul>
        </section>
        @endcan
@can('reportes_ver_ventas')
<section class="panel report-sales">
            <div class="executive-heading"><h2><x-icon name="file" /> Últimas ventas</h2>@can('ventas_ver_listado')<a href="{{ route('sales.history', $reportDates) }}">Historial</a>@endcan</div>
            <div class="table-wrap"><table><thead><tr><th># / Fecha</th><th>Total original</th><th>Estado</th><th>Doc.</th></tr></thead><tbody>
                @forelse($summary['latest_sales'] as $sale)<tr><td><strong>{{ $sale->id_venta }}</strong><small>{{ \Carbon\Carbon::parse($sale->fecha_venta)->format('d/m H:i') }}</small></td><td>{{ $sale->moneda_codigo ?: 'Moneda no documentada' }} {{ number_format((float) $sale->total_venta, 2, ',', '.') }}</td><td><span class="sale-state {{ $sale->estado === 'completada' ? 'positive' : 'negative' }}">{{ ucfirst($sale->estado) }}</span></td><td>@if(auth()->user()->can('ventas_ver_listado') || (auth()->user()->can('ventas_crear') && (int) $sale->id_usuario === (int) auth()->id()))<a href="{{ route('sales.ticket', $sale->id_venta) }}">Ticket</a>@endif</td></tr>@empty<tr><td colspan="4">Sin ventas en el periodo.</td></tr>@endforelse
            </tbody></table></div>
            {{ $summary['latest_sales']->links('vendor.pagination.tailwind') }}
        </section>
@endcan
</div>
