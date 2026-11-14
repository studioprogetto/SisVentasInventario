<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Existing usuarios schema; Eloquent exposes these columns dynamically.
 *
 * @property int $id_usuario
 * @property int $id_rol
 * @property string $username
 * @property string $nombre_completo
 * @property string $password
 * @property string $moneda
 * @property bool $activo
 */
final class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    protected $hidden = ['password'];

    protected $rememberTokenName = null;

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'password' => 'hashed'];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function isAdministrator(): bool
    {
        return $this->activo && mb_strtolower($this->rol?->nombre_rol ?? '') === 'administrador';
    }

    public function isMultifunctional(): bool
    {
        return str_starts_with(mb_strtolower($this->rol?->nombre_rol ?? ''), 'multifuncional');
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->activo) return false;
        if ($this->isAdministrator()) return true;
        if ($this->isMultifunctional()) return in_array($permission, [
            'dashboard_ver','ventas_crear','ventas_ver_listado','solicitar_devolucion',
            'productos_ver_lista','productos_ver_detalle','categorias_ver_lista',
            'clientes_ver_lista','clientes_crear_editar','proveedores_ver_lista',
            'almacenes_ver_lista','servicios_ver_lista','transferencias_crear',
            'compras_ver_lista','compras_crear','compras_recibir','caja_gestionar',
        ], true);
        return $this->rol()->whereHas('permisos', fn ($query) => $query->where('nombre_permiso', $permission))->exists();
    }
}
