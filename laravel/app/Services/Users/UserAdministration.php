<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Models\Usuario;
use App\Services\Operations\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserAdministration
{
    public function __construct(private AuditTrail $audit) {}

    public function save(Usuario $actor, array $data, ?int $id): void
    {
        DB::transaction(function () use ($actor, $data, $id): void {
            DB::table('usuarios')->orderBy('id_usuario')->lockForUpdate()->get();
            $old = $id ? DB::table('usuarios')->where('id_usuario', $id)->firstOrFail() : null;
            if (! $actor->hasPermission('admin_gestionar_roles')) {
                $own = DB::table('rol_permiso')->where('id_rol', $actor->id_rol)->pluck('id_permiso');
                if (DB::table('rol_permiso')->whereIn('id_rol', array_filter([$old?->id_rol, $data['id_rol']]))->whereNotIn('id_permiso', $own)->exists()) {
                    throw ValidationException::withMessages(['id_rol' => 'No puede administrar un usuario o rol con permisos superiores.']);
                }
            }
            $fields = array_intersect_key($data, array_flip(['nombre_completo', 'username', 'id_rol']));
            if (! empty($data['password'])) {
                $fields['password'] = Hash::make($data['password']);
            }
            if ($id) {
                DB::table('usuarios')->where('id_usuario', $id)->update($fields);
            } else {
                $id = DB::table('usuarios')->insertGetId($fields + ['activo' => 1], 'id_usuario');
            }
            $this->assertAdministrator();
            $this->audit->record((int) $actor->getKey(), 'guardar', 'usuarios', $id, $fields);
        });
    }

    public function state(Usuario $actor, int $id, bool $active): void
    {
        DB::transaction(function () use ($actor, $id, $active): void {
            DB::table('usuarios')->orderBy('id_usuario')->lockForUpdate()->get();
            if ($id === (int) $actor->getKey() && ! $active) {
                throw ValidationException::withMessages(['activo' => 'No puede desactivar su propia cuenta.']);
            }
            $target = Usuario::query()->findOrFail($id);
            if ($target->hasPermission('admin_gestionar_roles') && ! $actor->hasPermission('admin_gestionar_roles')) {
                throw ValidationException::withMessages(['activo' => 'No puede desactivar un administrador de roles.']);
            }
            DB::table('usuarios')->where('id_usuario', $id)->update(['activo' => $active]);
            $this->assertAdministrator();
            $this->audit->record((int) $actor->getKey(), 'estado', 'usuarios', $id, ['activo' => $active]);
        });
    }

    public function permissions(Usuario $actor, int $role, array $permissions): void
    {
        DB::transaction(function () use ($actor, $role, $permissions): void {
            DB::table('usuarios')->orderBy('id_usuario')->lockForUpdate()->get();
            DB::table('roles')->where('id_rol', $role)->firstOrFail();
            DB::table('rol_permiso')->where('id_rol', $role)->delete();
            foreach (array_unique($permissions) as $permission) {
                DB::table('rol_permiso')->insert(['id_rol' => $role, 'id_permiso' => $permission]);
            }
            $this->assertAdministrator();
            $this->audit->record((int) $actor->getKey(), 'permisos', 'roles', $role, ['permisos' => $permissions]);
        });
    }

    private function assertAdministrator(): void
    {
        if (! DB::table('usuarios')->join('rol_permiso', 'usuarios.id_rol', '=', 'rol_permiso.id_rol')->join('permisos', 'rol_permiso.id_permiso', '=', 'permisos.id_permiso')->where('usuarios.activo', 1)->where('permisos.nombre_permiso', 'admin_gestionar_roles')->exists()) {
            throw ValidationException::withMessages(['id_rol' => 'Debe conservar al menos un administrador de roles activo.']);
        }
    }
}
