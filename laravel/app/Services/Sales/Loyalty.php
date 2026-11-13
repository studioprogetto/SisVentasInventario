<?php

declare(strict_types=1);

namespace App\Services\Sales;

use Illuminate\Support\Facades\DB;

final class Loyalty
{
    public function percent(object $client, bool $physicalProduct): int
    {
        return $physicalProduct ? match ((int) $client->sellos + 1) {
            6 => 5,12 => 10,default => 0
        } : 0;
    }

    /** Existing six/twelve-stamp rule, now inside the sale transaction. */
    public function apply(object $client, bool $physicalProduct): int
    {
        if (! $physicalProduct) {
            DB::table('clientes')->where('id_cliente', $client->id_cliente)->update(['total_compras' => (int) $client->total_compras + 1]);

            return 0;
        }
        $stamps = (int) $client->sellos + 1;
        $percent = $this->percent($client, true);
        DB::table('clientes')->where('id_cliente', $client->id_cliente)->update([
            'sellos' => $stamps >= 12 ? $stamps % 12 : $stamps,
            'tarjetas_llenas' => (int) $client->tarjetas_llenas + intdiv($stamps, 12),
            'total_compras' => (int) $client->total_compras + 1,
        ]);

        return $percent;
    }
}
