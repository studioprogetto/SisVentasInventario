@extends('layouts.app')
@section('title', $definition['title'].' — '.config('business.name', 'Studio & Progetto'))
@section('content')
<h1>{{ $definition['title'] }}</h1>
<form method="GET" class="filters"><label>Buscar<input name="q" value="{{ request('q') }}"></label><label>Estado<select name="estado"><option value="activos">Activos</option><option value="inactivos" @selected(request('estado')==='inactivos')>Inactivos</option></select></label><button>Filtrar</button></form>
@if($canEdit)
<details class="card"><summary>Nuevo registro</summary>
<form method="POST" action="{{ route('masters.store', $module) }}" class="form-grid">@csrf
@foreach($definition['fields'] as $name=>$field)<label>{{ $field['label'] }}<input name="{{ $name }}" value="{{ old($name) }}" @required(str_contains($field['rules'],'required'))></label>@endforeach
<button>Guardar</button></form></details>
@endif
<div class="table-wrap"><table><caption>{{ $definition['title'] }} registrados</caption><thead><tr>@foreach($definition['fields'] as $field)<th>{{ $field['label'] }}</th>@endforeach<th>Acciones</th></tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($definition['fields'] as $name=>$field)<td>{{ $row->getAttribute($name) }}</td>@endforeach<td>
@if($canEdit)<details><summary>Editar</summary><form method="POST" action="{{ route('masters.update', [$module,$row->getKey()]) }}" class="form-grid">@csrf @method('PUT')
@foreach($definition['fields'] as $name=>$field)<label>{{ $field['label'] }}<input name="{{ $name }}" value="{{ $row->getAttribute($name) }}" @required(str_contains($field['rules'],'required'))></label>@endforeach<button>Guardar cambios</button></form></details>@endif
@if($canState)<form method="POST" action="{{ route('masters.state', [$module,$row->getKey()]) }}" data-confirm="¿Cambiar el estado de este registro?">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $row->activo ? 0 : 1 }}"><button>{{ $row->activo ? 'Desactivar' : 'Activar' }}</button></form>@endif
</td></tr>@empty<tr><td colspan="{{ count($definition['fields'])+1 }}">No hay registros con estos filtros.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}
@endsection
