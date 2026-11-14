<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use App\Support\BusinessConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class BusinessSettingsController extends Controller
{
    public function index(): mixed
    {
        return view('settings.business', ['settings' => config('business')]);
    }

    public function save(Request $request): mixed
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'url', 'max:255'],
            'opening_weekdays' => ['nullable', 'string', 'max:80'],
            'opening_weekend' => ['nullable', 'string', 'max:80'],
            'return_policy_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if (! Schema::hasTable('empresa_configuraciones')) {
            return back()->withInput()->withErrors(['name' => 'No se puede guardar: el almacenamiento de configuración no está disponible.']);
        }

       $payload = [
           'name' => $data['name'],
           'ruc' => $data['ruc'] ?? config('business.ruc'),
           'address' => $data['address'] ?? config('business.address'),
           'district' => $data['district'] ?? config('business.district'),
           'phone' => $data['phone'] ?? config('business.phone'),
           'email' => $data['email'] ?? config('business.email', 'ventas@studioyprogetto.com'),
           'website' => $data['website'] ?? config('business.website', ''),
           'opening_hours' => [
               'weekdays' => $data['opening_weekdays'] ?? config('business.opening_hours.weekdays', 'Lun - Vie: 8:00 - 20:30'),
               'weekend' => $data['opening_weekend'] ?? config('business.opening_hours.weekend', 'Sáb: 8:00 - 20:30'),
           ],
           'return_policy_days' => isset($data['return_policy_days']) ? (int) $data['return_policy_days'] : config('business.return_policy_days', 4),
       ];

       if (Schema::hasTable('empresa_configuraciones')) {
           foreach ($data as $key => $value) {
               if ($value === null || $value === '') {
                   continue;
               }

               EmpresaConfiguracion::query()->updateOrCreate(['clave' => $key], ['valor' => (string) $value]);
           }
       }

       $business = array_replace_recursive(config('business', []), $payload);
       config()->set('business', $business);
       config()->set('app.name', (string) $data['name']);

       if (Schema::hasTable('empresa_configuraciones')) {
           BusinessConfig::apply();
       }

       return back()->with('status', 'Configuración de la empresa actualizada.');
   }

    public function uploadLogo(Request $request): mixed
    {
        $request->validate(['logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048']]);
        $request->file('logo')->storeAs('branding', 'logo.png', 'public');

        return back()->with('status', 'Logo de la empresa actualizado.');
    }
}
