<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TurnoCaja extends Model
{
    protected $table = 'turnos_caja';

    protected $primaryKey = 'id_turno';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'monto_inicial' => 'decimal:2',
            'monto_final_sistema' => 'decimal:2',
            'monto_final_real' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_turno', 'id_turno');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'id_turno', 'id_turno');
    }
}
