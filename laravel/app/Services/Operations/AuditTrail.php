<?php

declare(strict_types=1);

namespace App\Services\Operations;

use Illuminate\Support\Facades\DB;

final class AuditTrail
{
    public function record(int $user, string $action, string $entity, int|string $id, array $changes = []): void
    {
        unset($changes['password'], $changes['password_confirmation']);
        $event = DB::table('auditoria_operaciones')->insertGetId([
            'id_usuario' => $user, 'accion' => $action, 'entidad' => $entity,
            'referencia' => (string) $id, 'cambios' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
        $actor = \App\Models\Usuario::find($user);
        if ($actor && !$actor->isAdministrator()) {
            $flag = in_array($action,['ajuste','devolucion','estado','egreso']) || ($changes['tipo_movimiento'] ?? '') === 'egreso';
            app(\App\Services\Notifications\Inbox::class)->admins('audit-'.$event, $flag ? 'Movimiento para revisión' : 'Actividad del personal',
                $actor->nombre_completo.' · '.$action.' · '.$entity.' #'.$id);
        }
    }
}
