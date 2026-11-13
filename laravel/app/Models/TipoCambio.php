<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Read model: existing PK is (fecha, id_moneda); Eloquent has no composite-key support. */
final class TipoCambio extends Model
{
    protected $table = 'tipo_cambio';

    public $timestamps = false;

    public $incrementing = false;

    protected function casts(): array
    {
        return ['fecha' => 'date', 'valor_soles' => 'decimal:4'];
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(TipoMoneda::class, 'id_moneda', 'id');
    }

    public function save(array $options = []): bool
    {
        throw new LogicException('Use an explicit query with both fecha and id_moneda to write exchange rates.');
    }

    public function delete(): ?bool
    {
        throw new LogicException('Exchange rate deletion requires both composite-key values.');
    }
}
