<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Services\Currency\CurrencyQuote;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaleQuote
{
    public function __construct(private SalePricing $pricing, private CurrencyQuote $currency, private Loyalty $loyalty) {}

    public function quote(array $data): array
    {
        $quote = $this->currency->snapshot(isset($data['id_moneda']) ? (int) $data['id_moneda'] : null);
        $priced = $this->pricing->lines($data['carrito'], $quote);
        $client = empty($data['id_cliente']) ? null : DB::table('clientes')->where(['id_cliente' => $data['id_cliente'], 'activo' => 1])->first();
        $physical = count(array_filter($priced['lines'], fn ($line) => $line['tipo'] === 'producto')) > 0;
        $percent = $client ? $this->loyalty->percent($client, $physical) : 0;
        $loyalty = intdiv($priced['gross'] * $percent + 50, 100);
        $manual = Money::cents($data['descuento_manual'] ?? '0');
        if ($manual > $priced['gross'] - $loyalty) {
            throw ValidationException::withMessages(['descuento_manual' => 'El descuento supera el importe de la venta.']);
        }
        $total = $priced['gross'] - $loyalty - $manual;
        $quote['lineas'] = array_map(fn ($line) => ['id' => $line['id'], 'tipo' => $line['tipo'], 'id_almacen' => $line['almacen'],
            'cantidad' => $line['cantidad'], 'precio' => $line['precio'], 'subtotal' => Money::decimal(Money::cents($line['precio']) * $line['cantidad'])], $priced['lines']);
        $available = $client ? DB::table('saldos_clientes')->where(['id_cliente' => $client->id_cliente, 'id_moneda' => $quote['id_moneda'], 'estado' => 'pendiente'])->where('saldo','>',0)->sum('saldo') : 0;
        $credit = ($data['metodo_pago'] === 'saldo' || ($data['usar_saldo'] ?? false)) ? min($total, Money::cents((string) $available)) : 0;

        return $quote + ['subtotal' => Money::decimal($priced['gross']), 'descuento_sellos' => Money::decimal($loyalty), 'descuento_manual' => Money::decimal($manual), 'total' => Money::decimal($total), 'saldo_aplicable' => Money::decimal($credit), 'por_cobrar' => Money::decimal($total - $credit)];
    }
}
