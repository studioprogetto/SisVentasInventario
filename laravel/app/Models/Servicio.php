<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Servicio extends Model
{
    protected $table = 'servicios';

    protected $primaryKey = 'id_servicio';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'precio_venta' => 'decimal:2',
        ];
    }

    public function prestaciones(): HasMany
    {
        return $this->hasMany(DetalleServicio::class, 'id_servicio', 'id_servicio');
    }
}
