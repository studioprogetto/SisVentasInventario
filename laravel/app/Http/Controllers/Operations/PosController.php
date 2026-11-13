<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\Products\ProductImage;
use App\Services\Queries\PosQuery;
use Illuminate\Http\Request;

final class PosController extends Controller
{
    public function items(Request $request, PosQuery $query): mixed
    {
        $filters = $request->validate(['term' => ['nullable', 'string', 'max:100'], 'almacen' => ['nullable', 'integer', 'exists:almacenes,id'],
            'tipo' => ['nullable', 'in:producto,servicio'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['nullable', 'integer', 'in:8,12,24'], 'id_moneda' => ['nullable', 'integer', 'exists:tipo_moneda,id']]);

        return response()->json($query->items($filters));
    }

    public function client(Request $request, PosQuery $query, int $id): mixed
    {
        $data = $request->validate(['id_moneda' => ['nullable', 'integer', 'exists:tipo_moneda,id']]);

        return response()->json($query->client($id, isset($data['id_moneda']) ? (int) $data['id_moneda'] : null));
    }

    public function image(ProductImage $image, int $id): mixed
    {
        return $image->response(Producto::query()->where('activo', 1)->findOrFail($id));
    }
}
