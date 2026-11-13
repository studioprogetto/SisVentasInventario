<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CatalogProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return ['id' => $product->id_producto, 'slug' => $product->slug, 'nombre' => $product->nombre, 'descripcion' => $product->descripcion,
            'precio' => $product->precio_venta, 'moneda' => 'PEN', 'disponible' => $product->stock > 0,
            'imagen' => route('catalog.image', $product->id_producto),
            'categoria' => ['id' => $product->categoria->id_categoria, 'nombre' => $product->categoria->nombre, 'slug' => $product->categoria->slug]];
    }
}
