<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Users\UserAdministration;
use Illuminate\Validation\ValidationException;
use Tests\Support\BusinessTestCase;

final class UserAuthorizationTest extends BusinessTestCase
{
    public function test_lower_privilege_user_cannot_assign_or_modify_a_superior_role(): void
    {
        $administrator = $this->userWithPermissions(['admin_gestionar_roles']);
        $limited = $this->userWithPermissions(['usuarios_crear_editar']);
        try {
            app(UserAdministration::class)->save($limited, ['nombre_completo' => 'Escalamiento', 'username' => 'invalid-escalation', 'id_rol' => $administrator->id_rol, 'password' => 'password-test'], null);
            $this->fail('Privilege escalation must fail.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('usuarios', ['username' => 'invalid-escalation']);
        }
    }

    public function test_last_administrator_cannot_remove_own_role_permission(): void
    {
        $administrator = $this->userWithPermissions(['admin_gestionar_roles']);
        try {
            app(UserAdministration::class)->permissions($administrator, $administrator->id_rol, []);
            $this->fail('Last administrator must remain.');
        } catch (ValidationException) {
            $this->assertTrue($administrator->hasPermission('admin_gestionar_roles'));
        }
    }

    public function test_login_without_dashboard_permission_has_accessible_landing_page(): void
    {
        $user = $this->userWithPermissions(['ventas_crear']);
        $this->post('/auth/login', ['username' => $user->username, 'password' => 'test-password'])->assertRedirect(route('sales.pos'));
    }

    public function test_logo_upload_is_not_available_to_anonymous_clients(): void
    {
        $this->post('/perfil/subirLogo')->assertRedirect(route('login'));
        $this->actingAs($this->userWithPermissions())->post('/perfil/subirLogo')->assertForbidden();
    }
}
