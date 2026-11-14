@extends('layouts.app')
@section('content')
<h1>Reporte personal de caja</h1>
@if($active)
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
        <tbody>@foreach($movements as $movement)<tr>
                <td>{{ $movement->fecha }}</td>
                <td>{{ $movement->tipo_movimiento }}</td>
                <td>{{ $movement->monto }}</td>
                <td>{{ $movement->descripcion }}</td>
            </tr>@endforeach</tbody>
    </table>
</div>{{ $movements->links('vendor.pagination.tailwind') }}
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