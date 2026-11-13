<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CambioVenta extends Model
{
    protected $table = 'cambios_ventas';

    protected $primaryKey = 'id_cambio';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'monto_saldo' => 'decimal:2',
            'fecha_cambio' => 'datetime',
        ];
    }

    public function ventaOriginal(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta_original', 'id_venta');
    }

    public function ventaNueva(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta_nueva', 'id_venta');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCambio::class, 'id_cambio', 'id_cambio');
    }
}
