<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Operations\AuditTrail;
use App\Services\Operations\Idempotency;

final class InventoryAdjustment
{
    public function __construct(private Idempotency $requests, private StockLedger $stock, private AuditTrail $audit) {}

    public function adjust(int $user, array $data): array
    {
        return $this->requests->execute($user, 'ajuste', $data['idempotency_key'], $data, function () use ($user, $data): array {
            $delta = (int) $data['delta'];
            $this->stock->move((int) $data['id_producto'], (int) $data['id_almacen'], $delta, $delta > 0 ? 'ajuste_positivo' : 'ajuste_negativo', $user);
            $this->audit->record($user, 'ajuste', 'productos', $data['id_producto'], ['delta' => $delta, 'id_almacen' => $data['id_almacen'], 'motivo' => $data['motivo']]);

            return ['success' => true];
        });
    }
}
