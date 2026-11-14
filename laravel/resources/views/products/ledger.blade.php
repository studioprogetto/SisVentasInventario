@extends('layouts.app')
@section('content')
<section class="neo-card ledger-profile">
<img src="{{ route('products.image', $product->id_producto) }}" alt="{{ $product->nombre }}">
<div><small>Detalle del producto</small><h1>{{ $product->nombre }}</h1><strong>S/ {{ number_format($product->precio_venta, 2) }}</strong>
<p>{{ $product->descripcion ?: 'Sin descripción registrada' }}</p>
<dl>@foreach(['Categoría' => $product->categoria?->nombre, 'Almacén predeterminado' => $product->almacen?->nombre, 'Stock actual' => $stocks->sum('stock'), 'Stock mínimo' => $product->stock_minimo, 'Precio de compra' => isset($product->precio_compra) ? 'S/ '.number_format($product->precio_compra,2) : null, 'Estado' => $product->activo ? 'Activo' : 'Inactivo'] as $label => $value)
<div><dt>{{ $label }}</dt><dd>{{ $value ?? 'Sin registrar' }}</dd></div>@endforeach</dl></div></section>
<h2>Kardex del producto</h2><p>El saldo inicial corresponde al corte documentado al incorporar el registro por almacén. Los movimientos anteriores conservan su historial; no se reconstruyen movimientos inexistentes.</p>
<div class="table-wrap"><table><thead><tr><th>Almacén</th><th>Saldo al corte</th><th>Stock actual</th></tr></thead><tbody>@foreach($stocks as $stock)<tr><td>{{ $stock->nombre }}</td><td>{{ $stock->saldo_inicial }}</td><td>{{ $stock->stock }}</td></tr>@endforeach</tbody></table></div>
@can('inventario_ajustar')<details class="card"><summary>Ajuste de inventario autorizado</summary><form method="POST" action="{{ route('inventory.adjust') }}">@csrf<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}"><input type="hidden" name="id_producto" value="{{ $product->id_producto }}"><label>Almacén<select name="id_almacen">@foreach($stocks as $stock)<option value="{{ $stock->id_almacen }}">{{ $stock->nombre }}</option>@endforeach</select></label><label>Variación (positiva para entrada, negativa para salida)<input name="delta" type="number" required></label><label>Motivo<input name="motivo" minlength="5" maxlength="255" required></label><button type="submit" class="btn btn-success">Registrar ajuste</button></form></details>@endcan
<div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Causa</th><th>Cantidad</th><th>Antes</th><th>Después</th><th>Usuario</th><th>Referencia</th></tr></thead><tbody>@forelse($movements as $movement)<tr><td>{{ $movement->fecha }}</td><td>{{ $movement->tipo_movimiento }}</td><td>{{ $movement->cantidad }}</td><td>{{ $movement->stock_anterior }}</td><td>{{ $movement->stock_nuevo }}</td><td>{{ $movement->id_usuario }}</td><td>{{ $movement->referencia_id }}</td></tr>@empty<tr><td colspan="7">Sin movimientos registrados.</td></tr>@endforelse</tbody></table></div>{{ $movements->links('vendor.pagination.tailwind') }}
@endsection

