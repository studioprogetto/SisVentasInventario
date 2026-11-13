<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Services\Operations\AuditTrail;
use Illuminate\Support\Facades\DB;

final class ExchangeRateWriter
{
    public function __construct(private AuditTrail $audit) {}

    public function save(int $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $currency = DB::table('tipo_moneda')->where(['id' => $data['id_moneda'], 'activo' => 1])->firstOrFail();
            DB::table('tipo_cambio')->updateOrInsert(['fecha' => $data['fecha'], 'id_moneda' => $currency->id], ['tipo_moneda' => $currency->codigo, 'valor_soles' => $data['valor_soles']]);
            $this->audit->record($user, 'tipo_cambio', 'tipo_cambio', $data['fecha'].':'.$currency->id, $data);
        });
    }
}
