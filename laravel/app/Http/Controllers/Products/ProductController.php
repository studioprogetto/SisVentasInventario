<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\SaveProductRequest;
use App\Services\Products\ProductWriter;
use App\Services\Queries\BackOfficeQueries;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function index(Request $request, BackOfficeQueries $queries): mixed
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'id_categoria' => ['nullable', 'integer'], 'id_almacen' => ['nullable', 'integer'], 'estado' => ['nullable', 'in:activos,inactivos'], 'bajo' => ['nullable', 'boolean'], 'orden' => ['nullable', 'in:nombre,precio_asc,precio_desc,stock_asc,stock_desc']]);

        return view('products.index', ['products' => $queries->products($filters), 'stats' => $queries->productStats(isset($filters['id_almacen']) ? (int) $filters['id_almacen'] : null)] + $queries->lookups());
    }

    public function save(SaveProductRequest $request, ProductWriter $products, ?int $id = null): mixed
    {
        $id = $products->save((int) $request->user()->getKey(), $request->validated(), $id, $request->file('imagen'));

        return $request->expectsJson() ? response()->json(['success' => true, 'id_producto' => $id]) : to_route('products.index')->with('status', 'Producto guardado.');
    }

    public function state(Request $request, ProductWriter $products, int $id): mixed
    {
        $data = $request->validate(['activo' => ['required', 'boolean']]);
        $products->state((int) $request->user()->getKey(), $id, (bool) $data['activo']);

        return back()->with('status', 'Estado actualizado.');
    }

    public function ledger(BackOfficeQueries $queries, int $id): mixed
    {
        return view('products.ledger', $queries->ledger($id));
    }

    public function image(\App\Models\Producto $product, \App\Services\Products\ProductImage $images): mixed
    {
        return $images->response($product);
    }
}
