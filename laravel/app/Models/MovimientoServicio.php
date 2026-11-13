<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MovimientoServicio extends Model
{
    protected $table = 'movimiento_servicio';

    protected $primaryKey = 'id_movimiento_servicio';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function prestacion(): BelongsTo
    {
        return $this->belongsTo(DetalleServicio::class, 'id_servicio_realizado', 'id_servicio_realizado');
    }
}
