<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class StockLedger
{
    /** Caller owns the surrounding transaction; all product locks use ascending ids. */
    public function move(int $product, int $warehouse, int $delta, string $cause, int $user, ?int $reference = null): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Stock movements require a transaction.');
        }
        $record = DB::table('productos')->where('id_producto', $product)->lockForUpdate()->first();
        if (! $record || ! DB::table('almacenes')->where(['id' => $warehouse, 'activo' => 1])->exists()) {
            throw ValidationException::withMessages(['inventario' => 'Producto o almacén no disponible.']);
        }
        DB::table('existencias')->insertOrIgnore(['id_producto' => $product, 'id_almacen' => $warehouse, 'stock' => 0, 'saldo_inicial' => 0, 'created_at' => now()]);
        $stock = DB::table('existencias')->where(['id_producto' => $product, 'id_almacen' => $warehouse])->lockForUpdate()->first();
        $before = (int) $stock->stock;
        if ($before + $delta < 0) {
            throw ValidationException::withMessages(['inventario' => 'Stock insuficiente en el almacén seleccionado.']);
        }
        DB::table('existencias')->where('id', $stock->id)->update(['stock' => $before + $delta]);
        DB::table('movimientos_inventario')->insert([
            'id_producto' => $product, 'id_almacen' => $warehouse, 'tipo_movimiento' => $cause,
            'cantidad' => abs($delta), 'stock_anterior' => $before, 'stock_nuevo' => $before + $delta,
            'id_usuario' => $user, 'referencia_id' => $reference,
        ]);
        DB::table('productos')->where('id_producto', $product)->update(['stock' => DB::table('existencias')->where('id_producto', $product)->sum('stock')]);
    }

    public function lockProducts(array $ids): void
    {
        $ids = array_unique(array_map('intval', $ids));
        sort($ids);
        DB::table('productos')->whereIn('id_producto', $ids)->orderBy('id_producto')->lockForUpdate()->get();
    }
}
