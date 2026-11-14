<?php

declare(strict_types=1);

namespace App\Services\Purchases;

use App\Services\Currency\CurrencyQuote;
use App\Services\Inventory\StockLedger;
use App\Services\Operations\AuditTrail;
use App\Services\Operations\Idempotency;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PurchaseReceipt
{
    public function __construct(private Idempotency $requests, private CurrencyQuote $currency, private StockLedger $stock, private AuditTrail $audit) {}

    public function order(int $user, array $data): array
    {
        return $this->requests->execute($user, 'compra', $data['idempotency_key'], $data, function () use ($user, $data): array {
            $quote = $this->currency->snapshot(isset($data['id_moneda']) ? (int) $data['id_moneda'] : null);
            if (! DB::table('proveedores')->where(['id_proveedor' => $data['id_proveedor'], 'activo' => 1])->exists()
                || ! DB::table('almacenes')->where(['id' => $data['id_almacen'], 'activo' => 1])->exists()) {
                throw ValidationException::withMessages(['compra' => 'Proveedor o almacén no disponible.']);
            }
            $total = 0;
            foreach ($data['productos'] as $line) {
                if (! DB::table('productos')->where(['id_producto' => $line['id_producto'], 'activo' => 1])->exists() || (int) $line['cantidad'] <= 0) {
                    throw ValidationException::withMessages(['productos' => 'Producto o cantidad inválidos.']);
                }
                $total += Money::cents($line['costo_unitario']) * (int) $line['cantidad'];
            }
            if ($total > 9999999999) {
                throw ValidationException::withMessages(['productos' => 'El importe excede la capacidad monetaria del esquema existente.']);
            }
            $id = DB::table('compras')->insertGetId($quote + ['id_usuario' => $user, 'id_proveedor' => $data['id_proveedor'], 'id_almacen' => $data['id_almacen'], 'total_compra' => Money::decimal($total), 'estado' => 'solicitada'], 'id_compra');
            foreach ($data['productos'] as $line) {
                DB::table('detalle_compras')->insert(['id_compra' => $id, 'id_producto' => $line['id_producto'], 'cantidad' => $line['cantidad'], 'costo_unitario' => Money::decimal(Money::cents($line['costo_unitario']))]);
            }
            $this->audit->record($user, 'solicitud', 'compras', $id);

            return ['success' => true, 'id_compra' => $id];
        });
    }

    public function receive(int $user, int $id): array
    {
        return DB::transaction(function () use ($user, $id): array {
            $purchase = DB::table('compras')->where('id_compra', $id)->lockForUpdate()->first();
            if (! $purchase || $purchase->estado !== 'solicitada' || ! $purchase->tipo_cambio_aplicado) {
                throw ValidationException::withMessages(['compra' => 'La compra ya se recibió o falta documentar su moneda/tasa.']);
            }
            $lines = DB::table('detalle_compras')->where('id_compra', $id)->orderBy('id_producto')->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['compra' => 'Compra sin detalles.']);
            }
            $this->stock->lockProducts($lines->pluck('id_producto')->all());
            foreach ($lines as $line) {
                $product = DB::table('productos')->where('id_producto', $line->id_producto)->first();
                $currentStock = (int) DB::table('existencias')->where('id_producto', $line->id_producto)->sum('stock');
                $cost = bcmul((string) $line->costo_unitario, (string) $purchase->tipo_cambio_aplicado, 6);
                $value = bcadd(bcmul((string) $product->precio_compra, (string) $currentStock, 6), bcmul($cost, (string) $line->cantidad, 6), 6);
                $average = bcadd(bcdiv($value, (string) ($currentStock + $line->cantidad), 4), '0.005', 2);
                $this->stock->move($line->id_producto, $purchase->id_almacen, $line->cantidad, 'compra', $user, $id);
                DB::table('productos')->where('id_producto', $line->id_producto)->update(['precio_compra' => $average]);
            }
            DB::table('compras')->where('id_compra', $id)->update(['estado' => 'recibida', 'fecha_recepcion' => now()]);
            $this->audit->record($user, 'recepcion', 'compras', $id);

            return ['success' => true, 'id_compra' => $id];
        }, 3);
    }
}
