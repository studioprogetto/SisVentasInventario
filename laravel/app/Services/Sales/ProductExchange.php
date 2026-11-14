<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Services\Operations\Idempotency;
use Illuminate\Support\Facades\DB;

final class ProductExchange
{
    public function __construct(private Idempotency $requests, private ReturnCredit $returns, private SaleCheckout $sales) {}

    public function exchange(int $user, array $data): array
    {
        return $this->requests->execute($user, 'cambio', $data['idempotency_key'], $data, function () use ($user, $data): array {
            if (isset($data['reemplazo'])) {
                $data['carrito'] = [['id'=>(int)$data['reemplazo'],'tipo'=>'producto','cantidad'=>(int)$data['productos'][0]['cantidad']]];
            }
            $credit = $this->returns->refund($user, array_replace($data, ['idempotency_key' => hash('sha256', $data['idempotency_key'].':return')]));
            $replacement = $this->sales->checkout($user, [
                'idempotency_key' => hash('sha256', $data['idempotency_key'].':sale'), 'id_cliente' => $credit['id_cliente'], 'id_moneda' => $credit['id_moneda'],
                'es_cambio' => true,
                'carrito' => $data['carrito'], 'metodo_pago' => $data['metodo_pago'], 'usar_saldo' => true, 'observacion' => $data['observacion'] ?? '', 'descuento_manual' => '0',
            ]);
            DB::table('cambios_ventas')->where('id_cambio', $credit['id_cambio'])->update(['tipo' => 'cambio', 'id_venta_nueva' => $replacement['id_venta']]);
            DB::table('auditoria_cambios')->where('id_cambio', $credit['id_cambio'])->update(['tipo_operacion' => 'cambio', 'id_venta_nueva' => $replacement['id_venta'], 'monto_nuevo' => $replacement['total']]);

            return ['success' => true, 'id_cambio' => $credit['id_cambio'], 'id_venta' => $replacement['id_venta'], 'credito' => $credit['saldo_generado']];
        });
    }
}
