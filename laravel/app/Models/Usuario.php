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

    public function hasPermission(string $permission): bool
    {
        return $this->activo && $this->rol()->whereHas('permisos', fn ($query) => $query->where('nombre_permiso', $permission))->exists();
    }
}
