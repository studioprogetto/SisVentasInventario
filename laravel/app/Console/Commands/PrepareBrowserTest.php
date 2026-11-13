<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Reports\ReportQuery;
use App\Support\Database\TestDatabaseGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PrepareBrowserTest extends Command
{
    protected $signature = 'system:prepare-browser';

    protected $description = 'Create fixtures only in an isolated test database for browser QA';

    public function handle(): int
    {
        TestDatabaseGuard::assertIsolated();
        $this->call('migrate', ['--force' => true]);
        $role = DB::table('roles')->insertGetId(['nombre_rol' => 'QA aislado'], 'id_rol');
        $permissions = ['admin_gestionar_roles', 'dashboard_ver', 'ventas_crear', 'ventas_ver_listado', 'productos_ver_lista', 'productos_crear', 'productos_editar', 'productos_ver_detalle', 'productos_cambiar_estado', 'categorias_ver_lista', 'categorias_crear_editar', 'categorias_cambiar_estado', 'almacenes_gestionar', 'transferencias_crear', 'compras_ver_lista', 'compras_crear', 'compras_recibir', 'proveedores_ver_lista', 'proveedores_crear_editar', 'proveedores_cambiar_estado', 'clientes_ver_lista', 'clientes_crear_editar', 'clientes_cambiar_estado', 'caja_gestionar', 'usuarios_ver_lista', 'usuarios_crear_editar', 'usuarios_cambiar_estado', 'tipocambio_ver_lista', 'tipocambio_gestionar', 'tipomoneda_gestionar'];
        foreach (array_column(ReportQuery::TYPES, 1) as $permission) {
            $permissions[] = $permission;
        }
        foreach (array_unique($permissions) as $permission) {
            DB::table('permisos')->insertOrIgnore(['nombre_permiso' => $permission]);
        }
        foreach (DB::table('permisos')->pluck('id_permiso') as $permission) {
            DB::table('rol_permiso')->insert(['id_rol' => $role, 'id_permiso' => $permission]);
        }
        $password = Str::random(24);
        $user = DB::table('usuarios')->insertGetId(['nombre_completo' => 'QA aislado', 'username' => 'qa-browser', 'password' => Hash::make($password), 'id_rol' => $role, 'activo' => 1], 'id_usuario');
        $warehouse = DB::table('almacenes')->insertGetId(['nombre' => 'Almacén de prueba']);
        DB::table('almacenes')->insert(['nombre' => 'Destino de prueba']);
        $category = DB::table('categorias')->insertGetId(['nombre' => 'Categoría de prueba', 'slug' => 'categoria-prueba'], 'id_categoria');
        DB::table('tipo_moneda')->insert(['descripcion' => 'Soles', 'codigo' => 'PEN', 'simbolo' => 'S/', 'principal' => 1]);
        DB::table('clientes')->insert(['nombre_cliente' => 'Cliente de prueba']);
        DB::table('proveedores')->insert(['nombre_proveedor' => 'Proveedor de prueba']);
        $product = DB::table('productos')->insertGetId(['nombre' => 'Producto de prueba', 'id_categoria' => $category, 'id_almacen' => $warehouse, 'stock' => 10, 'precio_compra' => '10', 'precio_venta' => '25', 'slug' => 'producto-prueba'], 'id_producto');
        DB::table('existencias')->insert(['id_producto' => $product, 'id_almacen' => $warehouse, 'stock' => 10, 'saldo_inicial' => 10, 'created_at' => now()]);
        DB::table('turnos_caja')->insert(['id_usuario' => $user, 'monto_inicial' => 100, 'estado' => 'abierto']);
        $directory = base_path('../docs/validation');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($directory.'/test-server.json', json_encode(['url' => 'http://127.0.0.1:8099', 'username' => 'qa-browser', 'password' => $password, 'database' => DB::connection()->getDatabaseName()], JSON_THROW_ON_ERROR));
        $this->info('Isolated browser fixtures ready. No business records copied.');

        return self::SUCCESS;
    }
}
