<?php

declare(strict_types=1);

namespace App\Services\Operations;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class Idempotency
{
    public function execute(int $user, string $action, string $key, array $payload, Closure $operation): array
    {
        if (! preg_match('/^[a-zA-Z0-9_-]{16,80}$/D', $key)) {
            throw ValidationException::withMessages(['idempotency_key' => 'Falta un identificador válido de operación.']);
        }
        $fingerprint = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($user, $action, $key, $fingerprint, $operation): array {
            DB::table('operaciones')->insertOrIgnore(['id_usuario' => $user, 'accion' => $action, 'clave' => $key, 'huella' => $fingerprint, 'created_at' => now()]);
            $row = DB::table('operaciones')->where(['id_usuario' => $user, 'accion' => $action, 'clave' => $key])->lockForUpdate()->first();
            if (! $row || $row->huella !== $fingerprint) {
                throw ValidationException::withMessages(['idempotency_key' => 'El identificador ya pertenece a otra solicitud.']);
            }
            if ($row->respuesta !== null) {
                return json_decode($row->respuesta, true, flags: JSON_THROW_ON_ERROR);
            }
            $result = $operation();
            DB::table('operaciones')->where('id', $row->id)->update(['respuesta' => json_encode($result, JSON_THROW_ON_ERROR)]);

            return $result;
        }, 3);
    }
}
