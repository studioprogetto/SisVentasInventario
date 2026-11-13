<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class SessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated() + ['activo' => 1];
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['username' => 'Usuario o contraseña incorrectos.']);
        }

        $request->session()->regenerate();

        $landing = 'profile.index';
        foreach (['dashboard_ver' => 'home', 'caja_gestionar' => 'cash.index', 'ventas_crear' => 'sales.pos', 'productos_ver_lista' => 'products.index', 'usuarios_ver_lista' => 'users.index'] as $permission => $route) {
            if ($request->user()->can($permission)) {
                $landing = $route;
                break;
            }
        }

        return redirect()->intended(route($landing));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
