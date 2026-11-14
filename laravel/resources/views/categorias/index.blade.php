@extends('layouts.app')
@section('title', 'Categorías | '.config('business.name', 'Studio & Progetto'))
@section('content')
<section class="categories-page">
    <header class="cat-heading">
        <div><span class="eyebrow">Operaciones y recursos</span><h1>Categorías</h1><p>Organiza los productos publicados en el catálogo y el inventario.</p></div>
        <a class="btn btn-secondary" href="{{ route('categorias.index', ['search' => $search, 'estado' => $estado === 'active' ? null : 'active']) }}"><x-icon name="filter" />{{ $estado === 'active' ? 'Ver todas las categorías' : 'Ver categorías activas' }}</a>
    </header>
    <div class="cat-stats" aria-label="Estadísticas de categorías">
        <a class="cat-surface cat-stat" href="{{ route('categorias.index', ['search' => $search]) }}"><div><span>Total categorías</span><strong>{{ $total }}</strong><small>Registradas en base de datos</small></div><x-icon name="tag" /></a>
        <a class="cat-surface cat-stat cat-positive" href="{{ route('categorias.index', ['search' => $search, 'estado' => 'active']) }}"><div><span>Categorías activas</span><strong>{{ $active }}</strong><small>Estado activo del registro</small></div><x-icon name="shield" /></a>
        <a class="cat-surface cat-stat cat-negative" href="{{ route('categorias.index', ['search' => $search, 'estado' => 'inactive']) }}"><div><span>Categorías inactivas</span><strong>{{ $total - $active }}</strong><small>Estado inactivo del registro</small></div><x-icon name="alert" /></a>
    </div>
    <div class="cat-split cat-list-only">
        @can('create', App\Models\Categoria::class)
        <button type="button" class="btn btn-success" data-open-create="nueva-categoria">Nueva categoría</button><x-create-dialog id="nueva-categoria" title="Nueva categoría">

            <section class="cat-surface cat-padding">
                <div class="cat-section-heading"><x-icon name="tag" /><div><h2>Nueva categoría</h2><p>Registrar categoría para productos.</p></div></div>
                <form method="POST" action="{{ route('categorias.store') }}" class="cat-form" id="cat-create-form">
                    @csrf
                    <label for="cat-name">Nombre de la categoría</label>
                    <input id="cat-name" name="nombre" maxlength="100" value="{{ old('_category_id') ? '' : old('nombre') }}" placeholder="Ej. Papelería y arte" required>
                    <label class="cat-check" for="cat-publish"><input type="hidden" name="publicado" value="0"><input id="cat-publish" type="checkbox" name="publicado" value="1" @checked(!old('_category_id') && old('publicado', false))><span>Publicar categoría<small>Publicación independiente del estado activo.</small></span></label>
                    <button type="submit" class="btn btn-success">Guardar categoría</button>
                </form>
            </section>
            <section class="cat-surface cat-padding cat-preview" aria-label="Vista previa del nombre">
                <span class="eyebrow">Vista previa · sin guardar</span>
                <div class="cat-preview-inset"><x-icon name="tag" /><strong id="cat-preview-name">Nueva categoría</strong><span id="cat-preview-status" class="cat-badge">Sin publicar</span></div>
                <p>El registro se guarda únicamente al enviar el formulario.</p>
            </section>
        
</x-create-dialog>
        @endcan
        <section class="cat-surface cat-list">
            <div class="cat-padding">
                <h2>Categorías registradas</h2><p>Consulta, edita o cambia el estado de cada registro.</p>
                <nav class="cat-filters" aria-label="Filtrar categorías por estado">
                    <a class="btn btn-secondary {{ $estado === '' ? 'is-selected' : '' }}" @if($estado === '') aria-current="page" @endif href="{{ route('categorias.index', ['search' => $search]) }}">Todas ({{ $total }})</a>
                    <a class="btn btn-secondary {{ $estado === 'active' ? 'is-selected' : '' }}" @if($estado === 'active') aria-current="page" @endif href="{{ route('categorias.index', ['search' => $search, 'estado' => 'active']) }}">Activas ({{ $active }})</a>
                    <a class="btn btn-secondary {{ $estado === 'inactive' ? 'is-selected' : '' }}" @if($estado === 'inactive') aria-current="page" @endif href="{{ route('categorias.index', ['search' => $search, 'estado' => 'inactive']) }}">Inactivas ({{ $total - $active }})</a>
                </nav>
                <form method="GET" action="{{ route('categorias.index') }}" class="cat-search">
                    @if($estado !== '')<input type="hidden" name="estado" value="{{ $estado }}">@endif
                    <label for="cat-search">Buscar por nombre</label>
                    <div><input id="cat-search" name="search" value="{{ $search }}" maxlength="100" placeholder="Buscar categoría…"><button type="submit" class="btn btn-secondary"><x-icon name="search" />Buscar</button><a class="btn btn-secondary" href="{{ route('categorias.index', ['estado' => $estado ?: null]) }}">Limpiar</a></div>
                </form>
            </div>
            <div class="cat-table-scroll">
                <table class="cat-table"><thead><tr><th>Nombre</th><th>Estado / publicación</th><th>Acciones</th></tr></thead><tbody>
                @forelse($categorias as $categoria)
                    <tr>
                        <td><small class="cat-id">#{{ $categoria->getKey() }}</small><strong>{{ $categoria->nombre }}</strong></td>
                        <td><span class="cat-badge {{ $categoria->activo ? 'is-active' : '' }}">{{ $categoria->activo ? 'Activa' : 'Inactiva' }}</span><small class="cat-publication">{{ $categoria->publicado ? 'Publicada' : 'Sin publicar' }}</small></td>
                        <td><div class="cat-actions">
                            @can('update', $categoria)
                            <details class="cat-edit" data-category-id="{{ $categoria->getKey() }}" @if($errors->any() && (string) old('_category_id') === (string) $categoria->getKey()) open @endif>
                                <summary class="btn btn-success">Editar<span class="cat-sr-only"> {{ $categoria->nombre }}</span></summary>
                                <div class="cat-edit-content">
                                    <h2>Editar categoría</h2><p>Registro #{{ $categoria->getKey() }}</p>
                                    <form method="POST" action="{{ route('categorias.update', $categoria) }}" class="cat-form">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="_category_id" value="{{ $categoria->getKey() }}">
                                        <label for="cat-edit-name-{{ $categoria->getKey() }}">Nombre de la categoría</label>
                                        <input id="cat-edit-name-{{ $categoria->getKey() }}" name="nombre" maxlength="100" value="{{ (string) old('_category_id') === (string) $categoria->getKey() ? old('nombre', $categoria->nombre) : $categoria->nombre }}" required>
                                        <label class="cat-check"><input type="hidden" name="publicado" value="0"><input type="checkbox" name="publicado" value="1" @checked((string) old('_category_id') === (string) $categoria->getKey() ? old('publicado', $categoria->publicado) : $categoria->publicado)> Publicar categoría</label>
                                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                                    </form>
                                </div>
                            </details>
                            @endcan
                            @can('categorias_cambiar_estado')
                            <form method="POST" action="{{ route('categorias.state', $categoria) }}" class="cat-state-form" data-category-name="{{ $categoria->nombre }}">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $categoria->activo ? 0 : 1 }}"><button type="submit" class="btn {{ $categoria->activo ? 'btn-danger' : 'btn-success' }}">{{ $categoria->activo ? 'Desactivar' : 'Activar' }}<span class="cat-sr-only"> {{ $categoria->nombre }}</span></button></form>
                            @endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="cat-empty"><x-icon name="search" /><h3>No se encontraron categorías</h3><p>Prueba con otro nombre o cambia el filtro de estado.</p></td></tr>
                @endforelse
                </tbody></table>
            </div>
            <div class="cat-summary">Mostrando {{ $categorias->count() }} de {{ $categorias->total() }} resultados · {{ $total }} categorías registradas</div>
            <div class="cat-pagination">{{ $categorias->links('vendor.pagination.tailwind') }}</div>
        </section>
    </div>
    <dialog id="cat-edit-dialog" aria-labelledby="cat-dialog-title"><div class="cat-dialog-heading"><strong id="cat-dialog-title">Editar categoría</strong><button type="button" class="btn btn-secondary" id="cat-close-dialog">Cerrar</button></div><div id="cat-dialog-body"></div></dialog>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/category-modules.css') }}">
@endpush

@push('scripts')
<script defer src="{{ asset('js/categories.js') }}"></script>
@endpush
