<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Users\UserAdministration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserController extends Controller
{
    public function index(): mixed
    {
        return view('users.index', ['users' => Usuario::query()->with('rol')->orderBy('nombre_completo')->paginate(30), 'roles' => Rol::query()->with('permisos')->get(), 'permissions' => Permiso::query()->orderBy('nombre_permiso')->get()]);
    }

    public function save(Request $request, UserAdministration $users, ?int $id = null): mixed
    {
        $data = $request->validate(['nombre_completo' => ['required', 'string', 'max:100'], 'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('usuarios', 'username')->ignore($id, 'id_usuario')],
            'id_rol' => ['required', 'integer', 'exists:roles,id_rol'], 'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'max:255', 'confirmed']]);
        $users->save($request->user(), $data, $id);

        return back()->with('status', 'Usuario guardado.');
    }

    public function state(Request $request, UserAdministration $users, int $id): mixed
    {
        $data = $request->validate(['activo' => ['required', 'boolean']]);
        $users->state($request->user(), $id, (bool) $data['activo']);

        return back()->with('status', 'Estado actualizado.');
    }

    public function permissions(Request $request, UserAdministration $users, int $id): mixed
    {
        if ($request->input('permisos') === null || $request->input('permisos') === '') {
            $request->merge(['permisos' => []]);
        }
        $data = $request->validate(['permisos' => ['present', 'array'], 'permisos.*' => ['integer', 'distinct', 'exists:permisos,id_permiso']]);
        $users->permissions($request->user(), $id, $data['permisos']);

        return back()->with('status', 'Permisos actualizados.');
    }
}
