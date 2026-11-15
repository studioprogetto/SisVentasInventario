<?php

namespace App\Services\Notifications;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class IncidentCause
{
    public const TITLE = 'Error reciente del servidor';
    public const UNKNOWN = 'El registro disponible no identifica una causa más precisa.';

    public static function describe(string $message): string
    {
        // Match diagnostic facts only. Exception text may contain SQL, bindings or credentials.
        $view = match (true) {
            str_contains($message, 'transfers') => 'la pantalla de transferencias',
            str_contains($message, 'dashboard') => 'el dashboard',
            str_contains($message, 'auth.login') => 'la pantalla de inicio de sesión',
            default => 'una pantalla del sistema',
        };
        return match (true) {
            str_contains($message, 'Database file at path') && str_contains($message, 'does not exist') =>
                'La aplicación intentó abrir una base de datos SQLite y el archivo configurado no existía.',
            str_contains($message, 'No application encryption key') =>
                'Faltaba la clave de cifrado de la aplicación, necesaria para proteger las sesiones.',
            str_contains($message, 'Schema operations are restricted') =>
                'Se intentó modificar la estructura de la base de datos con un comando reservado para pruebas. La protección del sistema bloqueó la operación.',
            str_contains($message, 'unexpected token "endif"') =>
                'No se pudo mostrar '.$view.' porque la plantilla contenía un cierre de condición inesperado.',
            str_contains($message, 'syntax error') => 'No se pudo interpretar '.(str_contains($message, '(View:') ? 'la plantilla de '.$view : 'una instrucción del sistema').'.',
            str_contains($message, 'View [') && str_contains($message, 'not found') => 'Faltaba la plantilla necesaria para mostrar '.$view.'.',
            str_contains($message, 'Route [') && str_contains($message, 'not defined') => 'La aplicación intentó abrir un enlace interno que no estaba registrado.',
            str_contains($message, 'Unknown column') => 'La consulta pidió un campo que no existe en la tabla de la base de datos.',
            str_contains($message, 'Base table or view not found') => 'La base de datos no tenía la tabla o vista que necesitaba la consulta.',
            str_contains($message, 'no permitido por sus permisos') => 'El sistema operativo bloqueó el intento de conexión por falta de permisos.',
            str_contains($message, 'Access denied for user') => 'El servidor de base de datos rechazó las credenciales de conexión.',
            str_contains($message, 'Connection refused'), str_contains($message, 'actively refused') => 'El servidor de destino rechazó la conexión.',
            str_contains($message, 'SQLSTATE[HY000] [2002]') => 'No se pudo establecer la conexión con el servidor de base de datos.',
            str_contains($message, 'must be present and writable') => 'La aplicación necesitaba una carpeta que debía existir y permitir escritura.',
            str_contains($message, 'Permission denied') => 'El sistema operativo denegó el permiso necesario para realizar la operación.',
            str_contains($message, 'No space left on device') => 'El dispositivo se quedó sin espacio para escribir los datos.',
            str_contains($message, 'Allowed memory size') => 'La operación superó la memoria disponible para la aplicación.',
            str_contains($message, 'timed out'), str_contains($message, 'Maximum execution time') => 'La operación superó el tiempo de espera permitido.',
            str_contains($message, 'Target class [active]') => 'La aplicación no pudo cargar la comprobación de usuario activo.',
            str_contains($message, 'Class "'), str_contains($message, 'Target class [') => 'No se encontró un componente necesario para ejecutar la operación.',
            str_contains($message, 'Command "') && str_contains($message, 'not defined') => 'Se solicitó un comando de mantenimiento que no está instalado.',
            str_contains($message, 'Undefined variable') => 'La aplicación intentó usar un dato que no se había preparado.',
            str_contains($message, 'Backup requires a MySQL/MariaDB') => 'La copia de seguridad requiere una conexión con MySQL o MariaDB.',
            default => self::UNKNOWN,
        };
    }

    public static function fromException(Throwable $exception): string
    {
        do {
            $reason = self::describe($exception->getMessage());
            if ($reason !== self::UNKNOWN) return $reason;
        } while ($exception = $exception->getPrevious());
        return self::UNKNOWN;
    }

    public static function record(Throwable $exception): void
    {
        $notice = ['key'=>'incidente-'.Str::uuid(), 'message'=>now()->format('d/m/Y H:i').' · '.self::fromException($exception)];
        // The file cache remains available if the database itself caused the failure.
        Cache::store('file')->put('server-incident', $notice, now()->addDay());
        app(Inbox::class)->admins($notice['key'], self::TITLE, $notice['message']);
    }
}
