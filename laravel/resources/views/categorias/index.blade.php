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
    <div class="cat-split @cannot('create', App\Models\Categoria::class) cat-list-only @endcannot">
        @can('create', App\Models\Categoria::class)
        <aside class="cat-create">
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
        </aside>
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
                                <summary class="btn btn-secondary">Editar<span class="cat-sr-only"> {{ $categoria->nombre }}</span></summary>
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
<style>
.categories-page{display:grid;gap:1.6rem;min-width:0;color:var(--text)}
.categories-page h1{margin:.3rem 0;font-size:clamp(1.6rem,3vw,2.1rem);letter-spacing:-.04em}
.categories-page h2{margin:0;font-size:1.05rem}.categories-page p{margin:.35rem 0;color:var(--muted);font-size:.78rem}
.categories-page .cat-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem}
.categories-page .cat-surface{background:var(--surface);border:1px solid var(--line);border-radius:24px;box-shadow:var(--shadow)}
.categories-page .cat-padding{padding:1.5rem}.categories-page .cat-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.3rem}
.categories-page .cat-stat{display:flex;align-items:center;justify-content:space-between;padding:1.4rem;text-decoration:none;color:var(--accent);transition:transform .2s ease}
.categories-page .cat-stat:hover{transform:translateY(-3px)}.categories-page .cat-stat span{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em}
.categories-page .cat-stat strong{display:block;font-size:2rem;line-height:1.5;color:var(--text)}.categories-page .cat-stat small{display:block;color:var(--muted);font-size:.68rem}
.categories-page .cat-stat>.icon{width:46px;height:46px;padding:10px;border-radius:14px;box-shadow:var(--inset)}
.categories-page .cat-positive,.categories-page .cat-positive strong{color:var(--green)}.categories-page .cat-negative,.categories-page .cat-negative strong{color:var(--red)}
.categories-page .cat-split{display:grid;grid-template-columns:minmax(250px,1fr) minmax(0,2fr);align-items:start;gap:1.6rem}.categories-page .cat-list-only{grid-template-columns:1fr}
.categories-page .cat-create{display:grid;gap:1.4rem}.categories-page .cat-section-heading{display:flex;align-items:center;gap:.8rem;border-bottom:1px solid var(--line);padding-bottom:1rem;margin-bottom:1.2rem}
.categories-page .cat-section-heading>.icon{color:var(--green)}.categories-page .cat-form{display:grid;gap:.8rem}
.categories-page label{font-size:.75rem;font-weight:600}.categories-page input:not([type=checkbox]):not([type=hidden]){width:100%;min-width:0;min-height:44px;border:1px solid var(--line);border-radius:12px;background:var(--bg);color:var(--text);box-shadow:var(--inset);padding:.75rem;font-size:.8rem}
.categories-page .cat-check{display:flex;align-items:center;gap:.7rem;padding:1rem;border:1px solid var(--line);border-radius:14px;box-shadow:var(--inset)}
.categories-page input[type=checkbox]{width:18px;height:18px;flex-shrink:0;accent-color:var(--green)}.categories-page .cat-check small{display:block;color:var(--muted);font-weight:400;font-size:.65rem}
.categories-page .cat-preview-inset{display:flex;align-items:center;gap:.6rem;margin:1rem 0;padding:1rem;border-radius:14px;box-shadow:var(--inset)}.categories-page .cat-preview-inset strong{min-width:0;overflow-wrap:anywhere;font-size:.75rem;flex:1}
.categories-page .cat-list{min-width:0;overflow:hidden}.categories-page .cat-filters{display:flex;flex-wrap:wrap;gap:.6rem;margin:1rem 0}
.categories-page .btn{font-size:.72rem;min-height:40px;padding:.65rem .85rem}.categories-page .is-selected{box-shadow:var(--inset);border-color:var(--green);color:var(--green)}
.categories-page .cat-search>div{display:flex;gap:.6rem;margin-top:.4rem}.categories-page .cat-search input{flex:1}.categories-page .cat-table-scroll{overflow-x:auto}
.categories-page .cat-table{width:100%;border-collapse:collapse}.categories-page .cat-table th{padding:1rem;text-align:left;font-size:.65rem;letter-spacing:.07em;text-transform:uppercase;background:var(--sidebar);color:var(--raised)}
[data-theme=dark] .categories-page .cat-table th{color:var(--text)}
.categories-page .cat-table td{padding:1rem;border-bottom:1px solid var(--line);vertical-align:middle;font-size:.78rem;overflow-wrap:anywhere}.categories-page .cat-table tr{animation:cat-row-in .3s ease both;transition:background .2s}
.categories-page .cat-table tbody tr:hover{background:var(--raised)}.categories-page .cat-id{display:block;font-size:.65rem;color:var(--muted);margin-bottom:.3rem}
.categories-page .cat-badge{display:inline-flex;white-space:nowrap;padding:.3rem .6rem;border-radius:999px;font-size:.65rem;font-weight:700;background:var(--raised);color:var(--muted);border:1px solid var(--line)}
.categories-page .cat-badge.is-active{background:var(--green-soft);color:var(--green)}.categories-page .cat-publication{display:block;color:var(--muted);font-size:.65rem;margin-top:.35rem}
.categories-page .cat-actions{display:flex;gap:.5rem;flex-wrap:wrap}.categories-page .cat-actions form{margin:0}.categories-page .cat-edit-content{padding:1rem;background:var(--surface);min-width:210px}
.categories-page .cat-empty{text-align:center;padding:2.5rem!important}.categories-page .cat-empty>.icon{width:36px;height:36px;color:var(--muted)}
.categories-page .cat-summary{padding:1rem 1.5rem;background:var(--bg);color:var(--muted);font-size:.68rem}.categories-page .cat-pagination{padding:0 1rem}.categories-page .cat-pagination:empty{display:none}
.categories-page .cat-sr-only{position:absolute;width:1px;height:1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
.categories-page dialog{width:min(480px,calc(100vw - 2rem));max-height:calc(100dvh - 2rem);padding:1.4rem;border:1px solid var(--line);border-radius:24px;background:var(--surface);color:var(--text);box-shadow:var(--shadow)}
.categories-page dialog::backdrop{background:color-mix(in srgb,var(--sidebar) 72%,transparent)}.categories-page dialog[open]{animation:cat-row-in .2s ease}.categories-page .cat-dialog-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.8rem}.categories-page dialog .cat-edit-content{padding:0;min-width:0}
@keyframes cat-row-in{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
@media(min-width:1280px){.categories-page .cat-create{position:sticky;top:1.5rem}}
@media(max-width:1100px){.categories-page .cat-split{grid-template-columns:1fr}.categories-page .cat-create{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.categories-page .cat-heading{align-items:stretch;flex-direction:column}.categories-page .cat-stats,.categories-page .cat-create{grid-template-columns:1fr}.categories-page .cat-padding{padding:1rem}.categories-page .cat-search>div{flex-wrap:wrap}.categories-page .cat-search input{flex-basis:100%}.categories-page .cat-table thead{display:none}.categories-page .cat-table,.categories-page .cat-table tbody,.categories-page .cat-table tr,.categories-page .cat-table td{display:block;width:100%}.categories-page .cat-table tr{padding:.8rem;border-bottom:1px solid var(--line)}.categories-page .cat-table td{border:0;padding:.5rem}.categories-page .cat-actions{justify-content:flex-start}}
@media(prefers-reduced-motion:reduce){.categories-page .cat-stat,.categories-page .cat-table tr,.categories-page dialog[open]{animation:none;transition:none}.categories-page .cat-stat:hover{transform:none}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const page = document.querySelector('.categories-page');
    if (!page) return;
    const name = page.querySelector('#cat-name');
    const publish = page.querySelector('#cat-publish');
    if (name && publish) {
        const preview = () => {
            page.querySelector('#cat-preview-name').textContent = name.value.trim() || 'Nueva categoría';
            const badge = page.querySelector('#cat-preview-status');
            badge.textContent = publish.checked ? 'Publicada' : 'Sin publicar';
            badge.classList.toggle('is-active', publish.checked);
        };
        name.addEventListener('input', preview);
        publish.addEventListener('change', preview);
        preview();
    }
    const dialog = page.querySelector('#cat-edit-dialog');
    const body = page.querySelector('#cat-dialog-body');
    let source = null;
    let trigger = null;
    if (typeof dialog.showModal === 'function') {
        const open = details => {
            source = details;
            trigger = details.querySelector('summary');
            details.open = false;
            body.append(details.querySelector('.cat-edit-content'));
            dialog.showModal();
            body.querySelector('input[name="nombre"]').focus();
        };
        page.querySelectorAll('.cat-edit').forEach(details => {
            details.querySelector('summary').addEventListener('click', event => {
                event.preventDefault();
                open(details);
            });
        });
        dialog.addEventListener('close', () => {
            if (source) source.append(body.firstElementChild);
            source = null;
            if (trigger) trigger.focus();
        });
        page.querySelector('#cat-close-dialog').addEventListener('click', () => dialog.close());
        const invalidEdit = page.querySelector('.cat-edit[open]');
        if (invalidEdit) open(invalidEdit);
    }
    page.querySelectorAll('.cat-state-form').forEach(form => {
        form.addEventListener('submit', event => {
            const action = form.querySelector('input[name="activo"]').value === '0' ? 'Desactivar' : 'Activar';
            if (!window.confirm(action + ' categoría "' + form.dataset.categoryName + '"? La publicación no se modifica.')) event.preventDefault();
        });
    });
})();
</script>
@endpush
