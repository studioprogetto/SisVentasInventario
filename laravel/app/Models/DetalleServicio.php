<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DetalleServicio extends Model
{
    protected $table = 'detalle_servicio';

    protected $primaryKey = 'id_servicio_realizado';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_total' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoServicio::class, 'id_servicio_realizado', 'id_servicio_realizado');
    }
}
