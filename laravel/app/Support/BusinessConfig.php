<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Schema;

final class BusinessConfig
{
    public static function apply(): void
    {
        $defaults = require base_path('config/business.php');
        if (! Schema::hasTable('empresa_configuraciones')) {
            config()->set('business', $defaults);
            config()->set('app.name', $defaults['name'] ?? config('app.name', 'Studio & Progetto'));

            return;
        }

        $settings = EmpresaConfiguracion::query()->pluck('valor', 'clave')->all();
        $overrides = [];
        foreach ($settings as $key => $value) {
            if ($key === 'opening_weekdays') {
                $overrides['opening_hours']['weekdays'] = (string) $value;
                continue;
            }
            if ($key === 'opening_weekend') {
                $overrides['opening_hours']['weekend'] = (string) $value;
                continue;
            }
            if (in_array($key, ['return_policy_days', 'pagination'], true)) {
                $overrides[$key] = (int) $value;
                continue;
            }
            $overrides[$key] = $value;
        }

        $business = array_replace_recursive($defaults, $overrides);
        config()->set('business', $business);
        config()->set('app.name', (string) ($business['name'] ?? config('app.name', 'Studio & Progetto')));
    }
}
