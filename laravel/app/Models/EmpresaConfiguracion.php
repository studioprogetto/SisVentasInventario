<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class EmpresaConfiguracion extends Model
{
    protected $table = 'empresa_configuraciones';

    protected $fillable = ['clave', 'valor'];

    public $timestamps = true;
}
