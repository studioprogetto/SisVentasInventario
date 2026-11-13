<?php

declare(strict_types=1);

namespace App\Services\Categories;

use App\Models\Categoria;
use App\Services\Operations\AuditTrail;
use Illuminate\Support\Facades\DB;

final class CategoryWriter
{
    public function __construct(private AuditTrail $audit) {}

    public function save(int $user, array $fields, ?Categoria $category = null): void
    {
        DB::transaction(function () use ($user, $fields, $category): void {
            $category ??= new Categoria;
            $category->fill($fields);
            $category->save();
            if (! $category->getAttribute('slug')) {
                $category->setAttribute('slug', 'categoria-'.$category->getKey());
                $category->save();
            }
            $this->audit->record($user, 'guardar', 'categorias', $category->getKey(), $fields);
        });
    }
}
