@extends('layouts.app')
@section('content')
<h1>Historial de ventas</h1>
<form method="GET" class="filters"><label>Operación<input name="id" type="number" min="1" value="{{ request('id') }}"></label><label>Desde<input name="desde" type="date" value="{{ request('desde') }}"></label><label>Hasta<input name="hasta" type="date" value="{{ request('hasta') }}"></label>
<label>Nombre del cliente<input name="cliente" value="{{ request('cliente') }}" maxlength="150"></label><label>Nombre del vendedor<input name="usuario" value="{{ request('usuario') }}" maxlength="100"></label>
<label>Método<select name="metodo_pago"><option value="">Todos</option>@foreach(config('business.payment_methods') as $method)<option @selected(request('metodo_pago')===$method)>{{ $method }}</option>@endforeach</select></label>
<label>Estado<select name="estado"><option value="">Todos</option>@foreach(['completada','parcialmente_devuelta','anulada'] as $state)<option @selected(request('estado')===$state)>{{ $state }}</option>@endforeach</select></label><button type="submit" class="btn btn-secondary">Filtrar</button></form>
<div class="table-wrap"><table><caption>Operaciones de venta</caption><thead><tr><th>Número</th><th>Fecha</th><th>Cliente</th><th>Usuario</th><th>Importe</th><th>Pago</th><th>Estado</th><th>Detalle</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td>{{ $sale->id_venta }}</td><td>{{ $sale->fecha_venta }}</td><td>{{ $sale->cliente?->nombre_cliente ?? 'Venta sin cliente' }}</td><td>{{ $sale->usuario?->nombre_completo }}</td><td>{{ $sale->moneda_codigo ?? $sale->usuario?->moneda ?? config('business.display_currency', 'S/') }} {{ $sale->total_venta }}</td><td><span class="payment-badge" data-payment="{{ strtolower(trim((string) $sale->metodo_pago)) }}">{{ ucfirst($sale->metodo_pago ?? 'Sin documentar') }}</span></td><td>{{ $sale->estado }}</td><td><a class="btn btn-success" href="{{ route('sales.ticket',$sale->id_venta) }}">Ver / imprimir</a></td></tr>@empty<tr><td colspan="8">No hay ventas con estos filtros.</td></tr>@endforelse
</tbody></table></div>{{ $sales->links('vendor.pagination.tailwind') }}
@endsection

