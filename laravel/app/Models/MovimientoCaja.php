<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    protected $primaryKey = 'id_movimiento_caja';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class, 'id_turno', 'id_turno');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
