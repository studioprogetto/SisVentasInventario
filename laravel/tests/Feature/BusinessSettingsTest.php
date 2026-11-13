<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\BusinessTestCase;

final class BusinessSettingsTest extends BusinessTestCase
{
    public function test_business_settings_save_updates_runtime_and_database(): void
    {
        $user = $this->userWithPermissions(['admin_gestionar_roles']);
        $this->actingAs($user);

        $response = $this->from('/configuracion')->post(route('settings.business.save'), [
            'name' => 'Nueva Empresa SRL',
            'ruc' => '20456789012',
            'address' => 'Av. Principal 123',
            'district' => 'Trujillo',
            'phone' => '+51 987654321',
            'email' => 'contacto@nuevaempresa.com',
            'website' => 'https://nuevaempresa.com',
            'opening_weekdays' => 'Lun - Vie: 9:00 - 19:00',
            'opening_weekend' => 'Sáb: 9:00 - 14:00',
            'return_policy_days' => 7,
        ]);

        $response->assertRedirect('/configuracion');
        $this->assertSame('Nueva Empresa SRL', config('business.name'));
        $this->assertSame('Nueva Empresa SRL', config('app.name'));
        $this->assertDatabaseHas('empresa_configuraciones', ['clave' => 'name', 'valor' => 'Nueva Empresa SRL']);
        $this->assertDatabaseHas('empresa_configuraciones', ['clave' => 'phone', 'valor' => '+51 987654321']);
    }
}
