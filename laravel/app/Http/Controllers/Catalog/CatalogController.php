<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogProductResource;
use App\Services\Catalog\CatalogQuery;
use App\Services\Products\ProductImage;
use Illuminate\Http\Request;

final class CatalogController extends Controller
{
    public function index(Request $request, CatalogQuery $catalog): mixed
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'categoria' => ['nullable', 'integer'], 'orden' => ['nullable', 'in:nombre,precio_asc,precio_desc'], 'minimo' => ['nullable', 'numeric', 'min:0', 'max:99999999'], 'maximo' => ['nullable', 'numeric', 'min:0', 'max:99999999']]);
        $products = $catalog->search($filters);
        if ($request->routeIs('catalog.api.products')) {
            return CatalogProductResource::collection($products);
        }

        return view('catalog.index', ['products' => $products, 'categories' => $catalog->categories()]);
    }

    public function show(Request $request, CatalogQuery $catalog, string $slug): mixed
    {
        $product = $catalog->published()->where('slug', $slug)->firstOrFail();

        return $request->routeIs('catalog.api.product') ? new CatalogProductResource($product) : view('catalog.product', ['product' => $product]);
    }

    public function categories(CatalogQuery $catalog): mixed
    {
        return response()->json(['data' => $catalog->categories()]);
    }

    public function image(CatalogQuery $catalog, ProductImage $images, int $id): mixed
    {
        return $images->response($catalog->published()->findOrFail($id));
    }

    public function sitemap(CatalogQuery $catalog): mixed
    {
        return response()->view('catalog.sitemap', ['products' => $catalog->published()->select('productos.id_producto', 'productos.slug')->cursor()], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
