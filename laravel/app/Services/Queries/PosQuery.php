<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Support\SearchTerms;
use App\Services\Currency\CurrencyQuote;
use App\Services\Sales\Loyalty;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

final class PosQuery
{
    public function lookups(): array
    {
        return ['currencies' => DB::table('tipo_moneda')->where('activo', 1)->orderByDesc('principal')->get(['id', 'descripcion', 'codigo', 'principal'])];
    }

    public function items(array $filters): array
    {
        $term = trim($filters['term'] ?? '');
        $quote = app(CurrencyQuote::class)->snapshot(isset($filters['id_moneda']) ? (int) $filters['id_moneda'] : null);
        $products = DB::table('productos as p')->join('existencias as e', 'e.id_producto', '=', 'p.id_producto')
            ->join('almacenes as a', 'a.id', '=', 'e.id_almacen')->where('p.activo', 1)->where('a.activo', 1)
            ->when($term !== '', fn ($q) => $q->where(SearchTerms::matching($term, ['p.nombre','p.codigo_barra'])))
            ->when($filters['almacen'] ?? null, fn ($q, $id) => $q->where('a.id', $id))
            ->selectRaw("p.id_producto as id, p.nombre, p.precio_venta, e.stock, a.id as id_almacen, a.nombre as almacen, 'producto' as tipo");
        $services = DB::table('servicios')->where('activo', 1)
            ->when($term !== '', fn ($q) => $q->where(SearchTerms::matching($term, ['nombre'])))
            ->selectRaw("id_servicio as id, nombre, precio_venta, NULL as stock, NULL as id_almacen, 'Servicio' as almacen, 'servicio' as tipo");
        $query = match ($filters['tipo'] ?? '') {
            'servicio' => $services,
            'producto' => $products,
            default => empty($filters['almacen']) ? $products->unionAll($services) : $products,
        };
        $page = DB::query()->fromSub($query, 'items')->orderBy('nombre')->orderBy('tipo')->orderBy('id')->orderBy('id_almacen')
            ->paginate(config('business.pagination'));
        $rows = $page->getCollection()->map(function ($row) use ($quote): array {
            $price = bcadd(bcdiv((string) $row->precio_venta, $quote['tipo_cambio_aplicado'], 4), '0.005', 2);

            return ['id' => (int) $row->id, 'nombre' => $row->nombre, 'tipo' => $row->tipo, 'precio_venta' => $price,
                'stock' => $row->stock === null ? null : (int) $row->stock, 'id_almacen' => $row->id_almacen === null ? null : (int) $row->id_almacen,
                'almacen' => $row->almacen, 'imagen' => $row->tipo === 'producto' ? route('pos.image', $row->id) : null];
        });

        return ['data' => $rows, 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'currency' => $quote['moneda_codigo']];
    }

    public function client(int $id, ?int $currency): array
    {
        $client = DB::table('clientes')->where(['id_cliente' => $id, 'activo' => 1])
            ->firstOrFail(['id_cliente', 'nombre_cliente', 'documento_identidad', 'sellos', 'total_compras', 'tarjetas_llenas']);
        $quote = app(CurrencyQuote::class)->snapshot($currency);
        $credit = DB::table('saldos_clientes')->where(['id_cliente' => $id, 'estado' => 'pendiente', 'id_moneda' => $quote['id_moneda']]);
        $debt = -(float) (clone $credit)->where('saldo','<',0)->sum('saldo');
        $credit = $credit->where('saldo','>',0)->sum('saldo');

        return (array) $client + ['ventas_completadas' => DB::table('ventas')->where(['id_cliente' => $id, 'estado' => 'completada'])->count(),
            'deuda_pendiente' => number_format($debt,2,'.',''), 'saldo_disponible' => Money::decimal(Money::cents((string) $credit)), 'moneda_codigo' => $quote['moneda_codigo'],
            'descuento_proxima_compra' => app(Loyalty::class)->percent($client, true)];
    }
}
