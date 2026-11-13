<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Services\Cash\CashSession;
use App\Services\Inventory\StockLedger;
use App\Services\Operations\AuditTrail;
use App\Services\Operations\Idempotency;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReturnCredit
{
    public function __construct(private Idempotency $requests, private StockLedger $stock, private AuditTrail $audit, private CashSession $cash) {}

    public function refund(int $user, array $data): array
    {
        return $this->requests->execute($user, 'devolucion', $data['idempotency_key'], $data, function () use ($user, $data): array {
            $cashReturn = ($data['destino'] ?? 'saldo') === 'efectivo';
            $turn = $cashReturn ? $this->cash->active($user) : null;
            $sale = DB::table('ventas')->where('id_venta', $data['id_venta_original'])->lockForUpdate()->first();
            if (! $sale || ! in_array($sale->estado, ['completada', 'parcialmente_devuelta'], true) || (! $cashReturn && ! $sale->id_cliente) || ! $sale->id_moneda) {
                throw ValidationException::withMessages(['venta' => 'La venta no admite devolución automática: verifique estado, cliente y moneda histórica.']);
            }
            $details = DB::table('detalle_ventas')->where('id_venta', $sale->id_venta)->orderBy('id_detalle_venta')->lockForUpdate()->get()->keyBy('id_detalle_venta');
            $this->stock->lockProducts($details->whereNotNull('id_producto')->pluck('id_producto')->all());
            $change = DB::table('cambios_ventas')->insertGetId(['id_venta_original' => $sale->id_venta, 'id_cliente' => $sale->id_cliente,
                'id_usuario' => $user, 'tipo' => 'devolucion', 'estado' => 'completado', 'monto_saldo' => 0, 'observacion' => $data['observacion'] ?? ''], 'id_cambio');
            $amount = 0;
            $seen = [];
            foreach ($data['productos'] as $input) {
                $id = (int) $input['id_detalle_venta'];
                $line = $details->get($id);
                $quantity = (int) $input['cantidad'];
                if (isset($seen[$id]) || ! $line || ($line->id_producto && ! $line->id_almacen) || $line->importe_neto === null || $quantity <= 0) {
                    throw ValidationException::withMessages(['productos' => 'Detalle ajeno, repetido, histórico incompleto o cantidad inválida.']);
                }
                $seen[$id] = true;
                $returned = (int) DB::table('detalles_cambio')->join('cambios_ventas', 'cambios_ventas.id_cambio', '=', 'detalles_cambio.id_cambio')
                    ->where('detalles_cambio.id_detalle_venta', $id)->where('detalles_cambio.tipo', 'devolucion')->where('cambios_ventas.estado', 'completado')->sum('cantidad');
                if ($returned + $quantity > $line->cantidad) {
                    throw ValidationException::withMessages(['productos' => 'La cantidad supera lo que falta devolver.']);
                }
                $net = Money::cents($line->importe_neto);
                $value = intdiv($net * ($returned + $quantity), $line->cantidad) - intdiv($net * $returned, $line->cantidad);
                $amount += $value;
                DB::table('detalles_cambio')->insert(['id_cambio' => $change, 'id_producto' => $line->id_producto, 'id_servicio' => $line->id_servicio,
                    'id_detalle_venta' => $id, 'cantidad' => $quantity, 'precio' => $line->precio_unitario, 'tipo' => 'devolucion']);
                if ($line->id_producto) {
                    $this->stock->move($line->id_producto, $line->id_almacen, $quantity, 'devolucion', $user, $change);
                }
            }
            DB::table('cambios_ventas')->where('id_cambio', $change)->update(['monto_saldo' => Money::decimal($amount)]);
            if ($cashReturn && $amount > 0) {
                $base = bcadd(bcmul(Money::decimal($amount), (string) $sale->tipo_cambio_aplicado, 4), '0.005', 2);
                DB::table('movimientos_caja')->insert(['id_turno' => $turn->id_turno, 'id_usuario' => $user, 'tipo_movimiento' => 'egreso', 'monto' => $base, 'descripcion' => 'Devolución #'.$change.' de venta #'.$sale->id_venta, 'referencia_id' => $change]);
            } elseif ($amount > 0) {
                DB::table('saldos_clientes')->insert(['id_cliente' => $sale->id_cliente, 'id_moneda' => $sale->id_moneda, 'saldo' => Money::decimal($amount), 'estado' => 'pendiente']);
            }
            $totalQuantity = (int) $details->sum('cantidad');
            $returnedTotal = (int) DB::table('detalles_cambio')->join('cambios_ventas', 'cambios_ventas.id_cambio', '=', 'detalles_cambio.id_cambio')
                ->where('cambios_ventas.id_venta_original', $sale->id_venta)->where('cambios_ventas.estado', 'completado')->where('detalles_cambio.tipo', 'devolucion')->sum('cantidad');
            $full = $returnedTotal === $totalQuantity;
            DB::table('ventas')->where('id_venta', $sale->id_venta)->update(['estado' => $full ? 'anulada' : 'parcialmente_devuelta']);
            if ($full) {
                if ($sale->id_cliente && $details->whereNotNull('id_producto')->isNotEmpty() && ! $sale->es_cambio) {
                    $client = DB::table('clientes')->where('id_cliente', $sale->id_cliente)->lockForUpdate()->first();
                    DB::table('clientes')->where('id_cliente', $client->id_cliente)->update(['sellos' => max(0, (int) $client->sellos - 1)]);
                }
                DB::table('ventas_anuladas')->insert(['id_venta_original' => $sale->id_venta, 'id_cliente' => $sale->id_cliente, 'monto_reembolsado' => Money::decimal($amount), 'motivo' => $data['observacion'] ?? '', 'id_cambio' => $change, 'fecha_anulacion' => now()]);
            }
            DB::table('auditoria_cambios')->insert(['id_venta_original' => $sale->id_venta, 'id_cambio' => $change, 'id_cliente' => $sale->id_cliente,
                'tipo_operacion' => 'devolucion', 'monto_original' => $sale->total_venta, 'saldo_generado' => Money::decimal($amount), 'fecha_auditoria' => now()]);
            $this->audit->record($user, 'devolucion', 'cambios_ventas', $change);

            return ['success' => true, 'id_cambio' => $change, 'saldo_generado' => $cashReturn ? '0.00' : Money::decimal($amount), 'monto_devuelto' => Money::decimal($amount), 'id_cliente' => $sale->id_cliente, 'id_moneda' => $sale->id_moneda];
        });
    }
}
