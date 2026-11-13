<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TipoMoneda extends Model
{
    protected $table = 'tipo_moneda';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function tiposCambio(): HasMany
    {
        return $this->hasMany(TipoCambio::class, 'id_moneda', 'id');
    }
}
