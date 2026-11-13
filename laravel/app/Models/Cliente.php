<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cliente extends Model
{
    protected $table = 'clientes';

    protected $primaryKey = 'id_cliente';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'sellos' => 'integer',
        ];
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_cliente', 'id_cliente');
    }

    public function saldos(): HasMany
    {
        return $this->hasMany(SaldoCliente::class, 'id_cliente', 'id_cliente');
    }
}
