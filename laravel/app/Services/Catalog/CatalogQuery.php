<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;

final class CatalogQuery
{
    /** @return Builder<Producto> */
    public function published(): Builder
    {
        return Producto::query()->where('activo', 1)->where('publicado', 1)->whereHas('categoria', fn ($query) => $query->where('activo', 1)->where('publicado', 1))->with('categoria')->withSum('existencias as stock_total', 'stock');
    }

    public function categories(): mixed
    {
        return Categoria::query()->where(['activo' => 1, 'publicado' => 1])->orderBy('nombre')->get(['id_categoria', 'nombre', 'slug']);
    }

    public function search(array $filters): mixed
    {
        $order = match ($filters['orden'] ?? 'nombre') {
            'precio_asc' => ['precio_venta', 'asc'], 'precio_desc' => ['precio_venta', 'desc'], default => ['nombre', 'asc']
        };

        return $this->published()->when($filters['q'] ?? '', fn ($query, $term) => $query->where(fn ($q) => $q->where('nombre', 'like', '%'.$term.'%')->orWhere('descripcion', 'like', '%'.$term.'%')))
            ->when($filters['categoria'] ?? null, fn ($query, $id) => $query->where('id_categoria', $id))
            ->when(isset($filters['minimo']), fn ($query) => $query->where('precio_venta', '>=', $filters['minimo']))
            ->when(isset($filters['maximo']), fn ($query) => $query->where('precio_venta', '<=', $filters['maximo']))
            ->orderBy(...$order)->paginate(config('business.pagination'))->withQueryString();
    }
}
