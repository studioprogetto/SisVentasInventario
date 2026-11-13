<?php

declare(strict_types=1);

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\SaveMasterRequest;
use App\Services\Masters\MasterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class MasterController extends Controller
{
    public function index(Request $request, MasterData $masters, string $maestro): mixed
    {
        Gate::authorize($masters->permission($maestro, 'ver_lista'));
        $rows = $masters->search($maestro, mb_substr((string) $request->query('q', ''), 0, 150), (string) $request->query('estado', 'activos'));
        if ($request->expectsJson()) {
            return response()->json($rows);
        }

        return view('masters.index', ['module' => $maestro, 'definition' => $masters->definition($maestro), 'rows' => $rows,
            'canEdit' => $request->user()->can($masters->permission($maestro, 'crear_editar')),
            'canState' => $request->user()->can($masters->permission($maestro, 'cambiar_estado'))]);
    }

    public function save(SaveMasterRequest $request, MasterData $masters, string $maestro, ?int $id = null): mixed
    {
        $id = $masters->save($maestro, (int) $request->user()->getKey(), $request->validated(), $id);

        return $request->expectsJson() ? response()->json(['success' => true, 'id' => $id]) : redirect()->route('masters.index', $maestro)->with('status', 'Registro guardado.');
    }

    public function state(Request $request, MasterData $masters, string $maestro, int $id): mixed
    {
        Gate::authorize($masters->permission($maestro, 'cambiar_estado'));
        $data = $request->validate(['activo' => ['required', 'boolean']]);
        $masters->state($maestro, (int) $request->user()->getKey(), $id, (bool) $data['activo']);

        return back()->with('status', 'Estado actualizado.');
    }
}
