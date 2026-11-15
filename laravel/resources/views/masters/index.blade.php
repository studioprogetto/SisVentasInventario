@extends('layouts.app')
@section('title', $definition['title'].' — '.config('business.name', 'Studio & Progetto'))
@push('styles')
<link rel="stylesheet" href="{{ asset('css/category-modules.css') }}">
@endpush
@section('content')
<section class="categories-page module-shell master-page">
    <header class="cat-heading module-header"><div><span class="eyebrow">Operaciones y recursos</span><h1>{{ $definition['title'] }}</h1><p>Administra los registros maestros utilizados por ventas, compras e inventario.</p></div><div class="module-actions"><a class="btn btn-secondary" href="{{ route('masters.index', ['maestro' => $module, 'estado' => 'activos']) }}">Activos</a><a class="btn btn-secondary" href="{{ route('masters.index', ['maestro' => $module, 'estado' => 'inactivos']) }}">Inactivos</a></div></header>
    <form method="GET" class="filters"><label>Buscar<input name="q" value="{{ request('q') }}" placeholder="Buscar registro..."></label><label>Estado<select name="estado"><option value="activos">Activos</option><option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option></select></label><button class="btn btn-secondary" type="submit">Filtrar</button></form>
    @if($canEdit)<button type="button" class="btn btn-success" data-open-create="nuevo-registro">Nuevo registro</button><x-create-dialog id="nuevo-registro" title="Nuevo registro">
<form method="POST" action="{{ route('masters.store', $module) }}" class="form-grid">@csrf @foreach($definition['fields'] as $name => $field)<label>{{ $field['label'] }}<input name="{{ $name }}" value="{{ old($name) }}" @required(str_contains($field['rules'], 'required'))></label>@endforeach<button class="btn btn-success" type="submit">Guardar registro</button></form>
</x-create-dialog>@endif
    <div data-search-results>
<div class="cat-surface panel"><div class="panel-header"><div><h2>{{ $definition['title'] }} registrados</h2><p class="muted">Listado paginado con acciones y estado actual.</p></div></div><div class="cat-surface table-wrap"><table class="cat-table"><thead><tr>@foreach($definition['fields'] as $field)<th>{{ $field['label'] }}</th>@endforeach @if($module === 'cliente')<th>Saldo a favor</th><th>Deuda pendiente</th>@endif<th>Acciones</th></tr></thead><tbody>@forelse($rows as $row)<tr>@foreach($definition['fields'] as $name => $field)<td>{{ $row->getAttribute($name) }}</td>@endforeach
@if($module === 'cliente')
    @foreach(['favor','deuda'] as $balanceType)
        <td>@forelse($row->balances as $balance)<div>{{ $balance->codigo }} {{ number_format($balance->$balanceType, 2) }}</div>@empty 0.00 @endforelse</td>
    @endforeach
@endif
<td class="table-actions">@if($module === 'cliente' && $row->activo)
@can('admin_gestionar_roles')
<button type="button" class="btn btn-secondary" data-open-create="saldo-{{ $row->getKey() }}">Registrar saldo</button>
<x-create-dialog id="saldo-{{ $row->getKey() }}" title="Registrar saldo">
<form action="{{ route('clients.balance',$row->getKey()) }}" method="POST">@csrf
<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}">
<p>{{ $row->nombre_cliente }}</p><label>Tipo<select name="tipo"><option value="favor">La tienda debe al cliente</option><option value="deuda">El cliente debe a la tienda</option></select></label>
<label>Monto en soles<input name="monto" type="number" min=".01" step=".01" required></label>
<label>Motivo<input name="motivo" maxlength="255" required></label><button class="btn btn-success">Guardar saldo</button></form></x-create-dialog>
@endcan @endif
@if($canEdit)<details><summary class="btn btn-success">Editar</summary><form method="POST" action="{{ route('masters.update', [$module, $row->getKey()]) }}" class="form-grid inline-detail">@csrf @method('PUT') @foreach($definition['fields'] as $name => $field)<label>{{ $field['label'] }}<input name="{{ $name }}" value="{{ $row->getAttribute($name) }}" @required(str_contains($field['rules'], 'required'))></label>@endforeach<button class="btn btn-success" type="submit">Guardar cambios</button></form></details>@endif @if($canState)<form method="POST" action="{{ route('masters.state', [$module, $row->getKey()]) }}" data-confirm="¿Cambiar el estado de este registro?">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $row->activo ? 0 : 1 }}"><button class="btn {{ $row->activo ? 'btn-danger' : 'btn-success' }}" type="submit">{{ $row->activo ? 'Desactivar' : 'Activar' }}</button></form>@endif</td></tr>@empty<tr><td colspan="{{ count($definition['fields']) + ($module === 'cliente' ? 3 : 1) }}">No hay registros con estos filtros.</td></tr>@endforelse</tbody></table></div></div>
    {{ $rows->links('vendor.pagination.tailwind') }}
</div>
</section>
@endsection
