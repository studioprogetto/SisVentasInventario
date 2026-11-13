@extends('layouts.app')
@section('content')
<h1>Tipos de cambio</h1><p>Valor de una unidad de la moneda seleccionada expresado en la moneda principal. Cada venta o compra nueva conserva la tasa aplicada.</p>
@can('tipocambio_gestionar')<form method="POST" action="{{ route('rates.store') }}" class="form-grid card">@csrf<label>Fecha de vigencia<input type="date" name="fecha" value="{{ today()->toDateString() }}" required></label><label>Moneda<select name="id_moneda" required>@foreach($currencies as $currency)<option value="{{ $currency->id }}">{{ $currency->codigo }} — {{ $currency->descripcion }}</option>@endforeach</select></label><label>Valor en moneda principal<input name="valor_soles" type="number" step="0.0001" min="0.0001" required></label><button type="submit" class="btn btn-success">Guardar tasa</button></form>@endcan
<div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Moneda</th><th>Valor</th></tr></thead><tbody>@foreach($rates as $rate)<tr><td>{{ $rate->fecha->toDateString() }}</td><td>{{ $rate->moneda?->descripcion }}</td><td>{{ $rate->valor_soles }}</td></tr>@endforeach</tbody></table></div>{{ $rates->links() }}
@endsection
