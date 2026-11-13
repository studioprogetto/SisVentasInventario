<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Categoria;
use App\Models\Usuario;

final class CategoriaPolicy
{
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermission('categorias_ver_lista');
    }

    public function create(Usuario $user): bool
    {
        return $user->hasPermission('categorias_crear_editar');
    }

    public function update(Usuario $user, Categoria $categoria): bool
    {
        return $user->hasPermission('categorias_crear_editar');
    }
}
