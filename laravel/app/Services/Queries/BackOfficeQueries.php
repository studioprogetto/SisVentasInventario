<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\TipoMoneda;
use App\Models\TurnoCaja;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

final class BackOfficeQueries
{
    public function lookups(): array
    {
        return ['warehouses' => Almacen::query()->where('activo', 1)->get(), 'categories' => Categoria::query()->where('activo', 1)->get(),
            'currencies' => TipoMoneda::query()->where('activo', 1)->get(), 'suppliers' => Proveedor::query()->where('activo', 1)->get(),
            'clients' => Cliente::query()->where('activo', 1)->select('id_cliente', 'nombre_cliente')->orderBy('nombre_cliente')->limit(300)->get()];
    }

    public function products(array $filters): mixed
    {
        $sorts = ['nombre' => 'nombre', 'precio_asc' => 'precio_venta', 'precio_desc' => 'precio_venta'];
        $sort = $filters['orden'] ?? 'nombre';
        $direction = in_array($sort, ['precio_desc', 'stock_desc'], true) ? 'desc' : 'asc';

        $warehouseId = isset($filters['id_almacen']) ? (int) $filters['id_almacen'] : null;
        $sortColumn = in_array($sort, ['stock_asc', 'stock_desc'], true) ? ($warehouseId ? 'stock_scope_sum' : 'stock_total') : ($sorts[$sort] ?? 'nombre');

        $products = Producto::query()->with(['categoria', 'almacen'])->withSum('existencias as stock_total', 'stock');
        if ($warehouseId) {
            $products->withSum(['existencias as stock_scope_sum' => fn ($query) => $query->where('id_almacen', $warehouseId)], 'stock');
        }

        return $products->where('activo', ($filters['estado'] ?? '') !== 'inactivos')
            ->when($filters['q'] ?? '', fn ($query, $term) => $query->where(fn ($q) => $q->where('nombre', 'like', '%'.$term.'%')->orWhere('descripcion', 'like', '%'.$term.'%')->orWhere('codigo_barra', 'like', '%'.$term.'%')))
            ->when($filters['id_categoria'] ?? null, fn ($query, $id) => $query->where('id_categoria', $id))
            ->when($filters['bajo'] ?? false, fn ($query) => $query->lowStock($warehouseId))
            ->orderBy($sortColumn, $direction)->paginate(config('business.pagination'))->withQueryString();
    }

    public function productStats(?int $warehouseId = null): array
    {
        $inventory = DB::table('existencias')->selectRaw('id_producto, SUM(stock) as stock')->when($warehouseId, fn ($query) => $query->where('id_almacen', $warehouseId))->groupBy('id_producto');
        $stats = DB::table('productos as p')->leftJoinSub($inventory, 'inventory', 'inventory.id_producto', '=', 'p.id_producto')->where('p.activo', 1)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(COALESCE(inventory.stock, 0)), 0) as units, COALESCE(SUM(COALESCE(inventory.stock, 0) * p.precio_venta), 0) as valuation, COALESCE(SUM(CASE WHEN COALESCE(inventory.stock, 0) <= p.stock_minimo THEN 1 ELSE 0 END), 0) as low_stock, COALESCE(SUM(CASE WHEN COALESCE(inventory.stock, 0) = 0 THEN 1 ELSE 0 END), 0) as out_of_stock')->first();
        $stats->categories = DB::table('productos')->where('activo', 1)->whereNotNull('id_categoria')->distinct()->count('id_categoria');

        return (array) $stats;
    }

    public function search(string $term, ?int $category = null): array
    {
        if (mb_strlen($term) < 2) {
            return [];
        }
        $products = DB::table('productos')->where('activo', 1)->where(fn ($q) => $q->where('nombre', 'like', '%'.$term.'%')->orWhere('codigo_barra', $term))
            ->when($category, fn ($query) => $query->where('id_categoria', $category))
            ->limit(20)->get(['id_producto as id', 'nombre', 'precio_venta', 'stock', 'id_almacen'])->map(fn ($row) => (array) $row + ['tipo' => 'producto'])->all();
        $services = DB::table('servicios')->where('activo', 1)->where('nombre', 'like', '%'.$term.'%')->limit(10)->get(['id_servicio as id', 'nombre', 'precio_venta'])->map(fn ($row) => (array) $row + ['tipo' => 'servicio'])->all();

        return $category ? $products : array_merge($products, $services);
    }

    public function clients(string $term): mixed
    {
        return Cliente::query()->where('activo', 1)->where(fn ($q) => $q->where('nombre_cliente', 'like', '%'.$term.'%')->orWhere('documento_identidad', 'like', $term.'%'))
            ->orderBy('nombre_cliente')->limit(30)->get(['id_cliente', 'nombre_cliente', 'documento_identidad']);
    }

    public function sales(array $filters): mixed
    {
        return Venta::query()->with(['cliente', 'usuario'])->when($filters['desde'] ?? null, fn ($query, $date) => $query->where('fecha_venta', '>=', $date.' 00:00:00'))
            ->when($filters['hasta'] ?? null, fn ($query, $date) => $query->where('fecha_venta', '<=', $date.' 23:59:59'))
            ->when($filters['id'] ?? null, fn ($query, $id) => $query->where('id_venta', $id))
            ->when($filters['id_cliente'] ?? null, fn ($query, $id) => $query->where('id_cliente', $id))
            ->when($filters['id_usuario'] ?? null, fn ($query, $id) => $query->where('id_usuario', $id))
            ->when($filters['cliente'] ?? null, fn ($query, $name) => $query->whereHas('cliente', fn ($q) => $q->where('nombre_cliente', 'like', '%'.$name.'%')))
            ->when($filters['usuario'] ?? null, fn ($query, $name) => $query->whereHas('usuario', fn ($q) => $q->where('nombre_completo', 'like', '%'.$name.'%')))
            ->when($filters['id_moneda'] ?? null, fn ($query, $id) => $query->where('id_moneda', $id))
            ->when($filters['metodo_pago'] ?? null, fn ($query, $method) => $query->where('metodo_pago', $method))
            ->when($filters['estado'] ?? null, fn ($query, $state) => $query->where('estado', $state))->orderByDesc('id_venta')->paginate(config('business.pagination'))->withQueryString();
    }

    public function sale(int $id): Venta
    {
        return Venta::query()->with(['cliente', 'usuario', 'detalles.producto', 'detalles.servicio', 'cambios.detalles'])->findOrFail($id);
    }

    public function purchases(): mixed
    {
        return Compra::query()->with(['proveedor', 'almacen', 'detalles.producto'])->orderByDesc('id_compra')->paginate(config('business.pagination'));
    }

    public function transfers(): mixed
    {
        return DB::table('transferencias')->join('almacenes as o', 'o.id', '=', 'transferencias.origen')->join('almacenes as d', 'd.id', '=', 'transferencias.destino')
            ->select('transferencias.*', 'o.nombre as origen_nombre', 'd.nombre as destino_nombre')->orderByDesc('transferencias.id')->paginate(config('business.pagination'));
    }

    public function cash(int $user, bool $history = true): array
    {
        $active = TurnoCaja::query()->where(['id_usuario' => $user, 'estado' => 'abierto'])->with('ventas')->first();

        return ['active' => $active, 'movements' => ! $history ? null : $active?->movimientos()->orderByDesc('fecha')->paginate(config('business.pagination'), ['*'], 'movimientos')->withQueryString(), 'turns' => ! $history ? null : TurnoCaja::query()->where('id_usuario', $user)->orderByDesc('id_turno')->paginate(config('business.pagination')),
            'backupStatus' => DB::table('auditoria_operaciones')->where('id_usuario', $user)->whereIn('accion', ['backup_fallido', 'backup_cierre'])->orderByDesc('id')->first()];
    }

    public function ledger(int $id): array
    {
        return ['product' => Producto::with(['categoria', 'almacen'])->findOrFail($id),
            'stocks' => DB::table('existencias')->join('almacenes', 'existencias.id_almacen', '=', 'almacenes.id')->where('id_producto', $id)->get(['existencias.*', 'almacenes.nombre']),
            'movements' => DB::table('movimientos_inventario')->where('id_producto', $id)->orderByDesc('id_movimiento')->paginate(config('business.pagination'))];
    }
}
