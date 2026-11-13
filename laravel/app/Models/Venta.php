<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property int $id_usuario */
final class Venta extends Model
{
    protected $table = 'ventas';

    protected $primaryKey = 'id_venta';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_venta' => 'datetime',
            'total_venta' => 'decimal:2',
            'descuento_manual' => 'decimal:2',
            'descuento_sellos' => 'decimal:2',
            'monto_recibido' => 'decimal:2',
            'cambio_entregado' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class, 'id_turno', 'id_turno');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta', 'id_venta');
    }

    public function cambios(): HasMany
    {
        return $this->hasMany(CambioVenta::class, 'id_venta_original', 'id_venta');
    }
}
