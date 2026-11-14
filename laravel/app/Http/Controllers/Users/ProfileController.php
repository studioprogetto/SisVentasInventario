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

    public function details(Request $request): mixed
    {
        $data = $request->validate([
            'nombres' => ['required','string','max:100'],
            'apellidos' => ['required','string','max:100'],
            'correo' => ['nullable','email','max:254'],
            'celular' => ['nullable','regex:/^[+0-9() .-]{6,25}$/'],
            'fecha_nacimiento' => ['nullable','date_format:Y-m-d','before_or_equal:today'],
            'foto' => ['nullable','image','mimes:jpeg,png,webp','max:2048','dimensions:max_width=4096,max_height=4096'],
        ]);
        $user = $request->user();
        $name = trim($data['nombres'].' '.$data['apellidos']);
        if (mb_strlen($name) > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['nombres' => 'El nombre completo admite hasta 100 caracteres.']);
        }
        unset($data['foto']);
        $previous = $user->foto_perfil;
        $photo = null;
        if ($request->hasFile('foto')) {
            $photo = $request->file('foto')->store('perfiles/'.$user->getKey(), 'local');
            abort_unless($photo, 500, 'No se pudo guardar la fotografía.');
            $data['foto_perfil'] = $photo;
        }
        try {
            $user->forceFill($data + ['nombre_completo' => $name])->saveOrFail();
        } catch (\Throwable $error) {
            if ($photo) Storage::disk('local')->delete($photo);
            throw $error;
        }
        if ($photo && $previous) Storage::disk('local')->delete($previous);

        return back()->with('status', 'Datos personales actualizados.');
    }

    public function photo(Request $request): mixed
    {
        $path = $request->user()->foto_perfil;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path));
    }

    public function password(Request $request): mixed
    {
        $data = $request->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required','string','min:8','max:72','confirmed','different:current_password'],
        ]);
        $request->user()->forceFill(['password' => $data['password']])->saveOrFail();
        $request->session()->regenerate();

        return back()->with('status', 'Contraseña actualizada.');
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
