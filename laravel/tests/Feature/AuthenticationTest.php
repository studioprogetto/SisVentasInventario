<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\BusinessTestCase;

final class AuthenticationTest extends BusinessTestCase
{
    public function test_guests_are_redirected_and_login_has_a_csrf_field(): void
    {
        $this->get('/home')->assertRedirect(route('login'));
        $this->get('/categoria')->assertRedirect(route('login'));
        $this->get('/auth/login')->assertOk()->assertSee('name="_token"', false);
    }

    public function test_existing_username_and_hash_authenticate_without_rewriting_password(): void
    {
        $user = $this->userWithPermissions(['dashboard_ver']);
        $hash = $user->password;
        $this->post('/auth/login', ['username' => $user->username, 'password' => 'test-password'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($hash, $user->refresh()->password);
        $this->get('/home')->assertOk()->assertSee('Dashboard');
        $this->post('/auth/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_authenticate(): void
    {
        $user = $this->userWithPermissions([], false);
        $this->post('/auth/login', ['username' => $user->username, 'password' => 'test-password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_permission_revocation_takes_effect_without_session_snapshot(): void
    {
        $user = $this->userWithPermissions(['dashboard_ver']);
        $this->actingAs($user)->get('/home')->assertOk();
        DB::table('rol_permiso')->where('id_rol', $user->id_rol)->delete();
        $this->get('/home')->assertForbidden();
    }

    public function test_disabled_account_loses_its_session(): void
    {
        $user = $this->userWithPermissions(['dashboard_ver']);
        $this->actingAs($user);
        DB::table('usuarios')->where('id_usuario', $user->getKey())->update(['activo' => false]);
        $user->refresh();
        $this->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/auth/login', ['username' => 'missing', 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/auth/login', ['username' => 'missing', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_mutation_without_csrf_is_rejected_by_real_web_middleware(): void
    {
        // Laravel bypasses CSRF in unit-test environment; enable its real check here.
        $this->app->instance('env', 'local');
        $this->post('/auth/login', ['username' => 'anything', 'password' => 'anything'])->assertStatus(419);
    }
}
