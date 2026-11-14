<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Services\Cash\CashSession;
use App\Services\Currency\CurrencyQuote;
use App\Services\Inventory\StockLedger;
use App\Services\Operations\AuditTrail;
use App\Services\Operations\Idempotency;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class SaleCheckout
{
    public function __construct(private Idempotency $requests, private CashSession $cash, private StockLedger $stock, private CurrencyQuote $currency, private Loyalty $loyalty, private AuditTrail $audit, private SalePricing $pricing) {}

    public function checkout(int $user, array $data): array
    {
        return $this->requests->execute($user, 'venta', $data['idempotency_key'], $data, function () use ($user, $data): array {
            $turn = $this->cash->active($user);
            $quote = $this->currency->snapshot(isset($data['id_moneda']) ? (int) $data['id_moneda'] : null);
            $client = empty($data['id_cliente']) ? null : DB::table('clientes')->where(['id_cliente' => $data['id_cliente'], 'activo' => 1])->lockForUpdate()->first();
            if (! empty($data['id_cliente']) && ! $client) {
                throw ValidationException::withMessages(['id_cliente' => 'Cliente no disponible.']);
            }
            if (empty($data['carrito']) || count($data['carrito']) > 200) {
                throw ValidationException::withMessages(['carrito' => 'El carrito debe contener entre 1 y 200 líneas.']);
            }
            $this->stock->lockProducts(array_column(array_filter($data['carrito'], fn ($line) => $line['tipo'] === 'producto'), 'id'));
            $priced = $this->pricing->lines($data['carrito'], $quote);
            $lines = $priced['lines'];
            $gross = $priced['gross'];
            $percent = $client && ! ($data['es_cambio'] ?? false) ? $this->loyalty->apply($client, count(array_filter($lines, fn ($line) => $line['tipo'] === 'producto')) > 0) : 0;
            $loyalty = intdiv($gross * $percent + 50, 100);
            $manual = Money::cents($data['descuento_manual'] ?? '0');
            if ($manual > $gross - $loyalty) {
                throw ValidationException::withMessages(['descuento_manual' => 'El descuento supera el importe de la venta.']);
            }
            $total = $gross - $loyalty - $manual;
            if (isset($data['accepted_total']) && Money::cents($data['accepted_total']) !== $total) {
                throw ValidationException::withMessages(['total' => 'El total cambió. Vuelva a calcular antes de confirmar.']);
            }
            $usedCredit = 0;
            if (($data['metodo_pago'] ?? '') === 'saldo' || ($data['usar_saldo'] ?? false)) {
                if (! $client) {
                    throw ValidationException::withMessages(['id_cliente' => 'Seleccione el cliente titular del saldo.']);
                }
                $usedCredit = $this->consumeCredit((int) $client->id_cliente, (int) $quote['id_moneda'], $total, $data['metodo_pago'] !== 'saldo');
            }
            $received = null;
            $change = null;
            if (in_array((string) ($data['metodo_pago'] ?? ''), ['efectivo', 'mixto'], true) && array_key_exists('monto_recibido', $data)) {
                $received = Money::cents((string) $data['monto_recibido']);
                if ($received < $total - $usedCredit) {
                    throw ValidationException::withMessages(['monto_recibido' => 'El monto recibido debe cubrir el total de la venta.']);
                }
                $change = $received - ($total - $usedCredit);
            }
            $saleData = $quote + ['id_cliente' => $client?->id_cliente, 'id_usuario' => $user, 'id_turno' => $turn->id_turno,
                'total_venta' => Money::decimal($total), 'total_base' => bcadd(bcmul(Money::decimal($total), $quote['tipo_cambio_aplicado'], 4), '0.005', 2),
                'saldo_utilizado' => Money::decimal($usedCredit), 'cobro_base' => bcadd(bcmul(Money::decimal($total - $usedCredit), $quote['tipo_cambio_aplicado'], 4), '0.005', 2),
                'descuento_manual' => Money::decimal($manual), 'descuento_sellos' => bcdiv((string) $percent, '100', 2),
                'metodo_pago' => $data['metodo_pago'], 'observacion' => $data['observacion'] ?? '', 'estado' => 'completada', 'numero_cliente' => 0, 'es_cambio' => (bool) ($data['es_cambio'] ?? false)];
            // Estas columnas pertenecen a una migración posterior; no deben impedir
            // vender en instalaciones que todavía no la han ejecutado.
            if (Schema::hasColumn('ventas', 'monto_recibido')) {
                $saleData['monto_recibido'] = $received === null ? null : Money::decimal($received);
            }
            if (Schema::hasColumn('ventas', 'cambio_entregado')) {
                $saleData['cambio_entregado'] = $change === null ? null : Money::decimal($change);
            }
            $id = DB::table('ventas')->insertGetId($saleData, 'id_venta');
            $allocated = 0;
            foreach ($lines as $index => $line) {
                $net = $index === array_key_last($lines) ? $total - $allocated : ($gross === 0 ? 0 : (int) bcdiv(bcmul((string) $total, (string) (Money::cents($line['precio']) * $line['cantidad']), 0), (string) $gross, 0));
                $allocated += $net;
                DB::table('detalle_ventas')->insert(['id_venta' => $id, 'id_producto' => $line['tipo'] === 'producto' ? $line['id'] : null,
                    'id_servicio' => $line['tipo'] === 'servicio' ? $line['id'] : null, 'tipo_item' => $line['tipo'], 'cantidad' => $line['cantidad'],
                    'precio_unitario' => $line['precio'], 'importe_neto' => Money::decimal($net), 'costo_unitario_base' => $line['costo'], 'id_almacen' => $line['almacen'], 'descuento' => '0.00']);
                if ($line['tipo'] === 'producto') {
                    $this->stock->move($line['id'], $line['almacen'], -$line['cantidad'], 'venta', $user, $id);
                }
            }
            $this->audit->record($user, 'venta', 'ventas', $id);

            return ['success' => true, 'id_venta' => $id, 'total' => Money::decimal($total)] + $quote;
        });
    }

    private function consumeCredit(int $client, int $currency, int $amount, bool $partial = false): int
    {
        $credits = DB::table('saldos_clientes')->where(['id_cliente' => $client, 'id_moneda' => $currency, 'estado' => 'pendiente'])->where('saldo','>',0)->orderBy('id_saldo')->lockForUpdate()->get();
        $available = $credits->sum(fn ($row) => Money::cents($row->saldo));
        if ($available < $amount && ! $partial) {
            throw ValidationException::withMessages(['saldo' => 'Saldo insuficiente en la moneda seleccionada.']);
        }
        $amount = min($available, $amount);
        $usedTotal = $amount;
        foreach ($credits as $credit) {
            $value = Money::cents($credit->saldo);
            $used = min($amount, $value);
            DB::table('saldos_clientes')->where('id_saldo', $credit->id_saldo)->update(['saldo' => Money::decimal($value - $used), 'estado' => $value === $used ? 'utilizado' : 'pendiente']);
            $amount -= $used;
            if ($amount === 0) {
                break;
            }
        }

        return $usedTotal;
    }
}
