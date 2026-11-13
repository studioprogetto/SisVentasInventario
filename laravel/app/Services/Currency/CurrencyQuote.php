<?php

declare(strict_types=1);

namespace App\Services\Currency;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CurrencyQuote
{
    public function snapshot(?int $id): array
    {
        $base = DB::table('tipo_moneda')->where(['principal' => 1, 'activo' => 1])->first();
        $currency = $id ? DB::table('tipo_moneda')->where(['id' => $id, 'activo' => 1])->first() : $base;
        if (! $base || ! $currency) {
            throw ValidationException::withMessages(['id_moneda' => 'Configure la moneda principal y una moneda activa.']);
        }
        $rate = (int) $currency->id === (int) $base->id ? '1.000000' : DB::table('tipo_cambio')
            ->where('id_moneda', $currency->id)->where('fecha', '<=', today()->toDateString())->orderByDesc('fecha')->value('valor_soles');
        if (! $rate || bccomp((string) $rate, '0', 6) <= 0) {
            throw ValidationException::withMessages(['id_moneda' => 'No hay tipo de cambio vigente para la operación.']);
        }

        return ['id_moneda' => $currency->id, 'moneda_codigo' => $currency->codigo, 'tipo_cambio_aplicado' => (string) $rate];
    }
}
