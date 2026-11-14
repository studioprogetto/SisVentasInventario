<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SalePricing
{
    public function lines(array $cart, array $quote): array
    {
        if (empty($cart) || count($cart) > 200) {
            throw ValidationException::withMessages(['carrito' => 'El carrito debe contener entre 1 y 200 líneas.']);
        }
        $lines = [];
        $gross = 0;
        foreach ($cart as $line) {
            if (! in_array($line['tipo'], ['producto', 'servicio'], true) || (int) $line['cantidad'] <= 0) {
                throw ValidationException::withMessages(['carrito' => 'Tipo o cantidad inválidos.']);
            }
            $physical = $line['tipo'] === 'producto';
            $record = DB::table($physical ? 'productos' : 'servicios')->where($physical ? 'id_producto' : 'id_servicio', $line['id'])->where('activo', 1)->first();
            if (! $record) {
                throw ValidationException::withMessages(['carrito' => 'Producto o servicio no disponible.']);
            }
            $price = bcadd(bcdiv((string) $record->precio_venta, $quote['tipo_cambio_aplicado'], 4), '0.005', 2);
            $gross += Money::cents($price) * (int) $line['cantidad'];
            $lines[] = ['id' => (int) $line['id'], 'tipo' => $line['tipo'], 'cantidad' => (int) $line['cantidad'], 'precio' => $price, 'costo' => $physical ? $record->precio_compra : null, 'almacen' => $physical ? (int) ($line['id_almacen'] ?? $record->id_almacen) : null];
        }
        if ($gross > 9999999999) {
            throw ValidationException::withMessages(['carrito' => 'El importe excede la capacidad monetaria del esquema existente.']);
        }

        return ['lines' => $lines, 'gross' => $gross];
    }
}
