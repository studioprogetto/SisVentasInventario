<?php

declare(strict_types=1);

namespace App\Services\Operations;

use Illuminate\Support\Facades\DB;

final class AuditTrail
{
    public function record(int $user, string $action, string $entity, int|string $id, array $changes = []): void
    {
        unset($changes['password'], $changes['password_confirmation']);
        DB::table('auditoria_operaciones')->insert([
            'id_usuario' => $user, 'accion' => $action, 'entidad' => $entity,
            'referencia' => (string) $id, 'cambios' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
