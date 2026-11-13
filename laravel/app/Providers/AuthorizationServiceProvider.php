<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Categoria;
use App\Models\Usuario;
use App\Policies\CategoriaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(static fn (Usuario $user, string $ability): ?bool => $user->hasPermission($ability) ? true : null);
        Gate::policy(Categoria::class, CategoriaPolicy::class);
    }
}
