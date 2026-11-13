<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\TipoMoneda;
use App\Services\Operations\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class ProfileController extends Controller
{
    public function index(): mixed
    {
        return view('users.profile', ['currencies' => TipoMoneda::query()->where('activo', 1)->get()]);
    }

    public function save(Request $request, AuditTrail $audit): mixed
    {
        $data = $request->validate(['moneda' => ['required', Rule::exists('tipo_moneda', 'simbolo')->where('activo', 1)]]);
        $user = $request->user();
        $user->setAttribute('moneda', $data['moneda']);
        $user->save();
        $audit->record((int) $user->getKey(), 'preferencia', 'usuarios', $user->getKey(), $data);

        return back()->with('status', 'Preferencia guardada. La moneda de cada operación se elige al registrarla.');
    }

    public function upload(Request $request): mixed
    {
        $request->validate(['logo' => ['required', 'image', 'mimes:png', 'max:1024', 'dimensions:max_width=4096,max_height=4096']]);
        $request->file('logo')->storeAs('branding', 'logo.png', 'public');

        return back()->with('status', 'Logo actualizado.');
    }

    public function logo(): mixed
    {
        $disk = Storage::disk('public');
        $path = $disk->exists('branding/logo.png') ? $disk->path('branding/logo.png') : public_path('img/logo.png');

        return response()->file($path, ['Content-Type' => 'image/png', 'Cache-Control' => 'public,max-age=0,must-revalidate']);
    }
}
