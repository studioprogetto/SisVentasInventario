<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VentaAnulada extends Model
{
    protected $table = 'ventas_anuladas';

    protected $primaryKey = 'id_anulacion';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'monto_reembolsado' => 'decimal:2',
            'fecha_anulacion' => 'datetime',
        ];
    }

    public function ventaOriginal(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta_original', 'id_venta');
    }

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(CambioVenta::class, 'id_cambio', 'id_cambio');
    }
}
