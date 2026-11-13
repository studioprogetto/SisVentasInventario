<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categorias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categorias\SaveCategoriaRequest;
use App\Models\Categoria;
use App\Services\Categories\CategoryWriter;
use App\Services\Operations\AuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class CategoriaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Categoria::class);

        return view('categorias.index', ['categorias' => Categoria::query()->orderBy('nombre')->paginate(config('business.pagination'))]);
    }

    public function store(SaveCategoriaRequest $request, CategoryWriter $categories): RedirectResponse
    {
        $categories->save((int) $request->user()->getKey(), $request->validated());

        return to_route('categorias.index')->with('status', 'Categoría registrada.');
    }

    public function update(SaveCategoriaRequest $request, Categoria $categoria, CategoryWriter $categories): RedirectResponse
    {
        $categories->save((int) $request->user()->getKey(), $request->validated(), $categoria);

        return to_route('categorias.index')->with('status', 'Categoría actualizada.');
    }

    public function state(Request $request, Categoria $categoria): RedirectResponse
    {
        Gate::authorize('categorias_cambiar_estado');
        $data = $request->validate(['activo' => ['required', 'boolean']]);
        $categoria->setAttribute('activo', $data['activo']);
        $categoria->save();
        app(AuditTrail::class)->record((int) $request->user()->getKey(), 'estado', 'categorias', $categoria->getKey(), $data);

        return back()->with('status', 'Estado de categoría actualizado.');
    }
}
