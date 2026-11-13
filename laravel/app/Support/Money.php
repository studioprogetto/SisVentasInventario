<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Money
{
    public static function cents(string|int|float $value): int
    {
        $text = (string) $value;
        if (! preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $text)) {
            throw ValidationException::withMessages(['monto' => 'Ingrese un importe positivo con hasta dos decimales.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $text), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }
}
