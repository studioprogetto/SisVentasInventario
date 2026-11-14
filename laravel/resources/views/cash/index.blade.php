@extends('layouts.app')
@section('content')
<h1>Caja</h1>
@if($backupStatus?->accion==='backup_fallido')<p role="alert">La última copia automática de cierre falló. El cierre quedó registrado; solicita al administrador generar y verificar una copia desde Copias de seguridad.</p>@endif
@if(!$active)<form method="POST" action="{{ route('cash.open') }}" class="card">@csrf<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}"><label>Saldo inicial en moneda principal<input name="monto_inicial" type="number" min="0" step="0.01" required></label><button type="submit" class="btn btn-success">Abrir caja</button></form>
@else
<p>Caja #{{ $active->id_turno }} abierta el {{ $active->fecha_apertura }}. Saldo inicial: {{ $active->monto_inicial }}. El cierre compara efectivo físico; pagos electrónicos se reportan por separado.</p>
<div class="cards">
    <form method="POST" action="{{ route('cash.movement') }}" class="card">@csrf<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}"><label>Movimiento<select name="tipo_movimiento">
                <option value="ingreso">Ingreso</option>
                <option value="egreso">Egreso</option>
            </select></label><label>Monto<input name="monto" type="number" min="0.01" step="0.01" required></label><label>Motivo<input name="descripcion" maxlength="255" required></label><button type="submit" class="btn btn-success">Registrar movimiento</button></form>
    <form method="POST" action="{{ route('cash.close') }}" class="card" data-confirm="¿Cerrar caja con el efectivo declarado?">@csrf<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}"><label>Efectivo contado<input name="monto_final_real" type="number" min="0" step="0.01" required></label>@if($active->ventas->whereNull('id_moneda')->count()>0)<label><input type="checkbox" name="confirmar_legacy" value="1" required> Revisé las ventas anteriores a la migración y los importes históricos utilizados para este cierre.</label>@endif<button type="submit" class="btn btn-danger">Cerrar caja</button></form>
</div>
<h2>Movimientos del turno</h2>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>@foreach($active->movimientos as $movement)<tr>
                <td>{{ $movement->fecha }}</td>
                <td>{{ $movement->tipo_movimiento }}</td>
                <td>{{ $movement->monto }}</td>
                <td>{{ $movement->descripcion }}</td>
            </tr>@endforeach</tbody>
    </table>
</div>
@endif
<h2>Mis turnos</h2>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Turno</th>
                <th>Apertura</th>
                <th>Cierre</th>
                <th>Esperado</th>
                <th>Declarado</th>
                <th>Diferencia</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>@foreach($turns as $turn)<tr>
                <td>{{ $turn->id_turno }}</td>
                <td>{{ $turn->fecha_apertura }}</td>
                <td>{{ $turn->fecha_cierre }}</td>
                <td>{{ $turn->monto_final_sistema }}</td>
                <td>{{ $turn->monto_final_real }}</td>
                <td>{{ $turn->diferencia }}</td>
                <td>{{ $turn->estado }}</td>
            </tr>@endforeach</tbody>
    </table>
</div>{{ $turns->links('vendor.pagination.tailwind') }}
@endsection