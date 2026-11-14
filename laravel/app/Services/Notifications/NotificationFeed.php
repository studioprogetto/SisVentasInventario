<?php

namespace App\Services\Notifications;

use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationFeed
{
    public function forUser(Usuario $user): array
    {
        $items = [];
        $add = function ($title, $message, $url = null) use (&$items) {
            $items[] = compact('title', 'message', 'url');
        };
        if ($user->fecha_nacimiento) {
            $birth = \Carbon\Carbon::parse($user->fecha_nacimiento);
            for ($days = 0; $days <= 7; $days++) {
                if (today()->addDays($days)->format('m-d') === $birth->format('m-d')) {
                    $add($days ? '¡Prepárate para tu cumpleaños!' : '¡Feliz cumpleaños!',
                        $days ? "Tu cumpleaños es en $days ".($days === 1 ? 'día.' : 'días.') : 'Tu equipo te desea un feliz día.', route('profile.index'));
                    break;
                }
            }
        }
        if ($user->can('productos_ver_lista')) {
            $count = Producto::query()->where('activo', true)->lowStock()->count();
            if ($count) $add('Inventario bajo mínimo', "$count productos alcanzaron su mínimo de reposición.", route('products.index', ['bajo' => 1]));
        }
        if ($user->can('compras_ver_lista')) {
            $count = DB::table('compras')->where('estado','solicitada')->count();
            if ($count) $add('Compras por recibir', "$count órdenes pendientes de recepción.", route('purchases.index'));
        }
        if ($user->can('transferencias_crear')) {
            $count = DB::table('transferencias')->where('estado','enviada')->count();
            if ($count) $add('Transferencias en tránsito', "$count transferencias esperan recepción.", route('transfers.index'));
        }
        if ($user->can('caja_gestionar') && DB::table('turnos_caja')->where('id_usuario',$user->getKey())->where('estado','abierto')->where('fecha_apertura','<',today())->exists()) {
            $add('Turno anterior abierto', 'Revisa el efectivo y el cierre de tu turno.', route('cash.index'));
        }
        if ($user->can('backups_gestionar') && DB::table('auditoria_operaciones')->whereIn('accion',['backup_fallido','backup_cierre','crear_backup'])->orderByDesc('id')->value('accion') === 'backup_fallido') {
            $add('Falló la copia de seguridad', 'Verifica las copias recientes.', route('backups.index'));
        }
        if ($user->can('admin_gestionar_roles') && ($time = Cache::store('file')->get('server-incident'))) {
            $add('Error reciente del servidor', 'Último incidente registrado el '.$time.'. Revisa el registro de Laravel.');
        }
        return $items;
    }
}
