@extends('layouts.app')

@section('title', 'Usuarios y permisos')

@push('styles')
<style>
    .users-page{display:grid;gap:1.2rem}
    .users-table-wrap caption.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    .users-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1.25rem 1.35rem;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:var(--shadow)}
    .users-header h1{margin:.3rem 0 .45rem}.users-header p{margin:0;color:var(--muted);max-width:680px;font-size:.82rem}.users-header__actions{display:flex;gap:.6rem;flex-wrap:wrap}
    .users-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.users-kpi{display:flex;align-items:center;gap:.8rem;padding:1rem;border:1px solid var(--line);border-radius:18px;background:var(--surface);box-shadow:var(--shadow)}
    .users-kpi__icon{width:40px;height:40px;display:grid;place-items:center;border-radius:13px;background:var(--accent-soft);color:var(--accent);font-size:1rem;font-weight:800}.users-kpi__icon.green{background:var(--green-soft);color:var(--green)}.users-kpi__icon.amber{background:var(--accent-soft);color:var(--amber)}
    .users-kpi span{display:block;color:var(--muted);font-size:.7rem;font-weight:600}.users-kpi strong{display:block;margin-top:.1rem;font-size:1.35rem;line-height:1.1}
    .users-panel{padding:0;overflow:hidden}.users-panel__header{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.15rem;border-bottom:1px solid var(--line)}.users-panel__header h2{margin:0}.users-panel__header p{margin:.25rem 0 0;color:var(--muted);font-size:.74rem}
    .users-form{padding:1.15rem}.users-form .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.users-form .form-grid label:nth-child(3),.users-form .form-grid label:nth-child(4),.users-form .form-grid label:nth-child(5){grid-column:1/-1}.users-form__actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line)}
    .users-table-wrap{border:0;border-radius:0;box-shadow:none;background:transparent}.users-table-wrap table{min-width:850px}.user-identity{display:flex;align-items:center;gap:.7rem}.user-avatar{width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;border-radius:12px;background:var(--accent-soft);color:var(--accent);font-weight:800;font-size:.75rem}.user-identity strong{display:block;color:var(--text);font-size:.8rem}.user-identity small{display:block;color:var(--muted);font-size:.68rem}.user-role{display:inline-flex;padding:.35rem .6rem;border-radius:999px;background:var(--accent-soft);color:var(--accent);font-size:.68rem;font-weight:700}.user-status{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .6rem;border-radius:999px;font-size:.68rem;font-weight:700}.user-status::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}.user-status.active{background:var(--green-soft);color:var(--green)}.user-status.inactive{background:var(--red-soft);color:var(--red)}.user-actions{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap}.user-actions .btn{min-height:36px;padding:.55rem .75rem;font-size:.7rem}.user-edit{min-width:300px;margin-top:.7rem;padding:.9rem;border:1px solid var(--line);border-radius:14px;background:var(--surface)}.user-edit .form-grid{display:grid;gap:.75rem}.user-edit__actions{display:flex;gap:.5rem;justify-content:flex-end;margin-top:.75rem}
    .roles-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;padding:1.15rem}.role-card{border:1px solid var(--line);border-radius:16px;background:var(--surface);overflow:hidden}.role-card summary{display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.9rem 1rem;cursor:pointer;font-size:.82rem;font-weight:700;color:var(--text)}.role-card summary::after{content:'Gestionar';color:var(--accent);font-size:.68rem;font-weight:600}.role-permissions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem;padding:0 1rem 1rem}.permission-item{display:flex;flex-direction:row;align-items:center;gap:.45rem;padding:.55rem .6rem;border:1px solid var(--line);border-radius:10px;background:var(--raised);font-size:.68rem;color:var(--text)}.permission-item input{width:17px;min-height:17px;box-shadow:none}.role-card__actions{display:flex;justify-content:flex-end;padding:0 1rem 1rem}.role-card__actions .btn{min-height:38px;font-size:.72rem}
    @media(max-width:760px){.users-header{flex-direction:column;padding:1rem}.users-header__actions,.users-header__actions .btn{width:100%}.users-header__actions .btn{flex:1}.users-kpis{grid-template-columns:1fr}.users-form .form-grid{grid-template-columns:1fr}.users-form .form-grid label:nth-child(3),.users-form .form-grid label:nth-child(4),.users-form .form-grid label:nth-child(5){grid-column:auto}.users-form__actions{flex-direction:column}.users-form__actions .btn{width:100%}.role-permissions{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="users-page">
    <header class="users-header">
        <div>
            <span class="eyebrow">ADMINISTRACIÓN · ACCESOS</span>
            <h1>Usuarios y permisos</h1>
            <p>Administra las cuentas del equipo, sus roles y el nivel de acceso a cada módulo del sistema.</p>
        </div>
        @can('usuarios_crear_editar')
            <div class="users-header__actions">
                <button type="button" class="btn btn-success" data-open-create="nuevo-usuario">Nuevo usuario</button>
            </div>
        @endcan
    </header>

    @php
        $totalUsers = $users->total();
        $activeUsers = $users->getCollection()->where('activo', true)->count();
        $roleCount = $roles->count();
    @endphp
    <section class="users-kpis" aria-label="Resumen de usuarios">
        <article class="users-kpi"><div class="users-kpi__icon">U</div><div><span>Usuarios registrados</span><strong>{{ $totalUsers }}</strong></div></article>
        <article class="users-kpi"><div class="users-kpi__icon green">✓</div><div><span>Usuarios activos en esta página</span><strong>{{ $activeUsers }}</strong></div></article>
        <article class="users-kpi"><div class="users-kpi__icon amber">R</div><div><span>Roles configurados</span><strong>{{ $roleCount }}</strong></div></article>
    </section>

    @can('usuarios_crear_editar')
        <x-create-dialog id="nuevo-usuario" title="Nuevo usuario">
<form method="POST" action="{{ route('users.store') }}" class="users-form">
                @csrf
                <div class="form-grid">@include('users.form', ['user' => null])</div>
                <div class="users-form__actions"><button type="reset" class="btn btn-secondary">Limpiar</button><button type="submit" class="btn btn-success">Crear usuario</button></div>
            </form>
</x-create-dialog>
    @endcan

    <section class="panel users-panel">
        <div class="users-panel__header"><div><h2>Directorio del equipo</h2><p>Consulta el estado, rol y acciones disponibles para cada usuario.</p></div><span class="status-chip">{{ $totalUsers }} registros</span></div>
        <div class="table-wrap users-table-wrap">
            <table>
                <caption class="sr-only">Listado de usuarios del sistema</caption>
                <thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    @php $initials = collect(preg_split('/\s+/', trim($user->nombre_completo)))->filter()->take(2)->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode(''); @endphp
                    <tr>
                        <td><div class="user-identity"><span class="user-avatar">{{ $initials ?: 'U' }}</span><div><strong>{{ $user->nombre_completo }}</strong><small>{{ '@'.$user->username }}</small></div></div></td>
                        <td><span class="user-role">{{ $user->rol?->nombre_rol ?? 'Sin rol' }}</span></td>
                        <td><span class="user-status {{ $user->activo ? 'active' : 'inactive' }}">{{ $user->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td><div class="user-actions">
                            @can('usuarios_crear_editar')
                                <details><summary class="btn btn-success">Editar</summary><div class="user-edit"><form method="POST" action="{{ route('users.update', $user->getKey()) }}">@csrf @method('PUT')<div class="form-grid">@include('users.form')</div><div class="user-edit__actions"><button type="submit" class="btn btn-success">Guardar cambios</button></div></form></div></details>
                            @endcan
                            @can('usuarios_cambiar_estado')
                                <form method="POST" action="{{ route('users.state', $user->getKey()) }}" data-confirm="¿Confirmas cambiar el estado de este usuario?">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $user->activo ? 0 : 1 }}"><button type="submit" class="btn {{ $user->activo ? 'btn-danger' : 'btn-success' }}">{{ $user->activo ? 'Desactivar' : 'Activar' }}</button></form>
                            @endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><strong>No hay usuarios registrados</strong><span>Crea el primer usuario para comenzar a gestionar accesos.</span></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem 1.15rem">{{ $users->links('vendor.pagination.tailwind') }}</div>
    </section>

    @can('admin_gestionar_roles')
        <section class="panel users-panel">
            <div class="users-panel__header"><div><h2>Permisos por rol</h2><p>Define qué módulos puede utilizar cada perfil del sistema.</p></div><span class="status-chip">{{ $permissions->count() }} permisos disponibles</span></div>
            <div class="roles-grid">
                @forelse($roles as $role)
                    <details class="role-card"><summary>{{ $role->nombre_rol }}<span>{{ $role->permisos->count() }} asignados</span></summary><form method="POST" action="{{ route('roles.permissions', $role->id_rol) }}">@csrf @method('PUT')<div class="role-permissions">@foreach($permissions as $permission)<label class="permission-item"><input type="checkbox" name="permisos[]" value="{{ $permission->id_permiso }}" @checked($role->permisos->contains('id_permiso', $permission->id_permiso))>{{ $permission->nombre_permiso }}</label>@endforeach</div><div class="role-card__actions"><button type="submit" class="btn btn-success">Guardar permisos</button></div></form></details>
                @empty
                    <p class="muted">No hay roles configurados.</p>
                @endforelse
            </div>
        </section>
    @endcan
</div>
@endsection
