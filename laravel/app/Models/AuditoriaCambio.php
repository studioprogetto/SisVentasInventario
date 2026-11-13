<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditoriaCambio extends Model
{
    protected $table = 'auditoria_cambios';

    protected $primaryKey = 'id_auditoria';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'monto_original' => 'decimal:2',
            'monto_nuevo' => 'decimal:2',
            'saldo_generado' => 'decimal:2',
            'fecha_auditoria' => 'datetime',
        ];
    }

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(CambioVenta::class, 'id_cambio', 'id_cambio');
    }
}
