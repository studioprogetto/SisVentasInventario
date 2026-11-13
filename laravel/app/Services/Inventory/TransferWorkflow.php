<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Operations\AuditTrail;
use App\Services\Operations\Idempotency;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransferWorkflow
{
    public function __construct(private Idempotency $requests, private StockLedger $stock, private AuditTrail $audit) {}

    public function create(int $user, array $data): array
    {
        return $this->requests->execute($user, 'transferencia', $data['idempotency_key'], $data, function () use ($user, $data): array {
            if ((int) $data['origen'] === (int) $data['destino'] || DB::table('almacenes')->whereIn('id', [$data['origen'], $data['destino']])->where('activo', 1)->count() !== 2) {
                throw ValidationException::withMessages(['destino' => 'Seleccione dos almacenes activos diferentes.']);
            }
            $id = DB::table('transferencias')->insertGetId(['id_usuario' => $user, 'origen' => $data['origen'], 'destino' => $data['destino'], 'estado' => 'pendiente', 'created_at' => now()]);
            foreach ($data['productos'] as $line) {
                if ((int) $line['cantidad'] <= 0 || ! DB::table('productos')->where(['id_producto' => $line['id_producto'], 'activo' => 1])->exists()) {
                    throw ValidationException::withMessages(['productos' => 'Seleccione productos activos y cantidades positivas.']);
                }
                DB::table('detalle_transferencias')->insert(['id_transferencia' => $id, 'id_producto' => $line['id_producto'], 'cantidad' => $line['cantidad']]);
            }
            $this->audit->record($user, 'crear', 'transferencias', $id);

            return ['success' => true, 'id_transferencia' => $id];
        });
    }

    public function transition(int $user, int $id, string $target): array
    {
        return DB::transaction(function () use ($user, $id, $target): array {
            $transfer = DB::table('transferencias')->where('id', $id)->lockForUpdate()->first();
            $allowed = ['pendiente' => ['enviada', 'cancelada'], 'enviada' => ['recibida', 'cancelada']];
            if (! $transfer || ! in_array($target, $allowed[$transfer->estado] ?? [], true)) {
                throw ValidationException::withMessages(['estado' => 'Transición no permitida o ya aplicada.']);
            }
            $lines = DB::table('detalle_transferencias')->where('id_transferencia', $id)->orderBy('id_producto')->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['productos' => 'Transferencia sin productos.']);
            }
            $this->stock->lockProducts($lines->pluck('id_producto')->all());
            foreach ($lines as $line) {
                if ($target === 'enviada') {
                    $this->stock->move($line->id_producto, $transfer->origen, -$line->cantidad, 'transferencia_salida', $user, $id);
                } elseif ($target === 'recibida') {
                    $this->stock->move($line->id_producto, $transfer->destino, $line->cantidad, 'transferencia_entrada', $user, $id);
                } elseif ($transfer->estado === 'enviada') {
                    $this->stock->move($line->id_producto, $transfer->origen, $line->cantidad, 'transferencia_entrada', $user, $id);
                }
            }
            DB::table('transferencias')->where('id', $id)->update(['estado' => $target, 'updated_at' => now()]);
            $this->audit->record($user, $target, 'transferencias', $id);

            return ['success' => true, 'id_transferencia' => $id, 'estado' => $target];
        }, 3);
    }
}
