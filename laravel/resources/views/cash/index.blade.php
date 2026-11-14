@extends('layouts.app')
@section('content')
<section class="cash-desk"><header class="neo-card activity-card"><div><small>TESORERÍA</small><h1>Caja</h1><p>{{ auth()->user()->nombre_completo }}</p></div><strong class="cash-state">{{ $active ? 'Turno abierto' : 'Caja cerrada' }}</strong></header>
<a href="{{ route('reports.my-cash') }}">Consultar mis turnos y movimientos →</a>
@if($backupStatus?->accion==='backup_fallido')<p role="alert">La última copia automática de cierre falló. El cierre quedó registrado; solicita al administrador generar y verificar una copia desde Copias de seguridad.</p>@endif
<div class="cash-columns">
<form method="POST" action="{{ route('cash.open') }}" class="neo-card cash-panel">@csrf
<h2>Abrir turno</h2><label>Sede<input value="{{ config('business.name') }}" readonly></label>
<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}">
<label>Monto inicial (S/)<input type="number" step=".01" min="0" name="monto_inicial" required value="0"></label>
<label>Observaciones<textarea name="observacion" maxlength="255"></textarea></label>
<button class="btn btn-success" @disabled($active)>Abrir caja</button></form>
<form method="POST" action="{{ route('cash.close') }}" class="neo-card cash-panel" data-confirm="¿Cerrar el turno con el monto declarado?">@csrf
<h2>Cerrar turno</h2><label>Identificador del turno<input readonly value="{{ $active?->id_turno }}" placeholder="Se completa al abrir la caja"></label>
<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}">
<label>Monto declarado (S/)<input type="number" step=".01" min="0" name="monto_final_real" required @disabled(!$active)></label>
<label>Observaciones<textarea name="observacion" maxlength="255"></textarea></label>
@if($active && $active->ventas->whereNull('id_moneda')->count())<label><input name="confirmar_legacy" type="checkbox" value="1" required> Revisé las ventas históricas</label>@endif
<div><a href="{{ route('reports.my-cash') }}" class="btn btn-secondary">Ver resumen</a>
<button class="btn btn-danger" @disabled(!$active)>Cerrar caja</button></div></form></div>
@if($active)
    <button type="button" class="btn btn-success" data-open-create="nuevo-movimiento">Registrar movimiento</button><x-create-dialog id="nuevo-movimiento" title="Registrar movimiento">
<form method="POST" action="{{ route('cash.movement') }}" class="card">@csrf<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}"><label>Movimiento<select name="tipo_movimiento">
                <option value="ingreso">Ingreso</option>
                <option value="egreso">Egreso</option>
            </select></label><label>Monto<input name="monto" type="number" min="0.01" step="0.01" required></label><label>Motivo<input name="descripcion" maxlength="255" required></label><button type="submit" class="btn btn-success">Registrar movimiento</button></form>
</x-create-dialog>
@endif
</section>
@endsection
