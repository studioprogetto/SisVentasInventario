<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Usuario;
use App\Support\Database\TestDatabaseGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class BusinessTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::assertIsolated();
        $this->assertSame(getenv('EXPECTED_DB_DRIVER') ?: 'sqlite', DB::getDriverName());
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        DB::beginTransaction();
        config(['migration.read_only' => false]);
    }

    protected function tearDown(): void
    {
        try {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } finally {
            parent::tearDown();
        }
    }

    protected function userWithPermissions(array $permissions = [], bool $active = true): Usuario
    {
        $role = DB::table('roles')->insertGetId(['nombre_rol' => 'test-role-'.DB::table('roles')->count()], 'id_rol');
        foreach ($permissions as $permission) {
            $id = DB::table('permisos')->where('nombre_permiso', $permission)->value('id_permiso')
                ?? DB::table('permisos')->insertGetId(['nombre_permiso' => $permission], 'id_permiso');
            DB::table('rol_permiso')->insert(['id_rol' => $role, 'id_permiso' => $id]);
        }
        $id = DB::table('usuarios')->insertGetId([
            'nombre_completo' => 'Usuario de prueba',
            'username' => 'test-user-'.DB::table('usuarios')->count(),
            'password' => Hash::make('test-password'),
            'id_rol' => $role,
            'activo' => $active,
        ], 'id_usuario');

        return Usuario::query()->findOrFail($id);
    }
}
