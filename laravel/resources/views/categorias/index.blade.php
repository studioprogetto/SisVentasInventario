@extends('layouts.app')
@section('title', 'Categorías — '.config('business.name', 'Studio & Progetto'))
@section('content')
<h1>Categorías</h1>
@can('create', App\Models\Categoria::class)
<form method="POST" action="{{ route('categorias.store') }}" class="card">
    @csrf
    <label for="nombre">Nombre de la categoría</label>
    <input id="nombre" name="nombre" maxlength="100" value="{{ old('nombre') }}" required>
    <input type="hidden" name="publicado" value="0"><label><input type="checkbox" name="publicado" value="1"> Publicar categoría</label>
    <button type="submit" class="btn btn-success">Guardar</button>
</form>
@endcan
<div class="table-wrap"><table><caption>Categorías registradas</caption><thead><tr><th>Nombre</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
@forelse($categorias as $categoria)
<tr><td>{{ $categoria->nombre }}</td><td>{{ $categoria->activo ? 'Activa' : 'Inactiva' }}</td><td>
@can('update', $categoria)
<form method="POST" action="{{ route('categorias.update', $categoria) }}">
    @csrf @method('PUT')
    <label for="nombre-{{ $categoria->getKey() }}">Editar nombre</label>
    <input id="nombre-{{ $categoria->getKey() }}" name="nombre" value="{{ $categoria->nombre }}" maxlength="100" required>
    <input type="hidden" name="publicado" value="0"><label><input type="checkbox" name="publicado" value="1" @checked($categoria->publicado)> Publicar categoría</label>
    <button type="submit" class="btn btn-success">Actualizar</button>
</form>
@endcan
@can('categorias_cambiar_estado')<form method="POST" action="{{ route('categorias.state',$categoria) }}">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $categoria->activo ? 0 : 1 }}"><button class="{{ $categoria->activo ? 'btn btn-danger' : 'btn btn-success' }}">{{ $categoria->activo ? 'Desactivar' : 'Activar' }}</button></form>@endcan
</td></tr>
@empty<tr><td colspan="3">No hay categorías registradas.</td></tr>@endforelse
</tbody></table></div>
{{ $categorias->links() }}
@endsection
