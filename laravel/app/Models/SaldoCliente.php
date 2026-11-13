<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaldoCliente extends Model
{
    protected $table = 'saldos_clientes';

    protected $primaryKey = 'id_saldo';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'saldo' => 'decimal:2',
            'fecha_actualizacion' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
