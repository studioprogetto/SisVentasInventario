<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetalleCambio extends Model
{
    protected $table = 'detalles_cambio';

    protected $primaryKey = 'id_detalle';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio' => 'decimal:2',
        ];
    }

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(CambioVenta::class, 'id_cambio', 'id_cambio');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
