<?php

declare(strict_types=1);

use App\Support\Database\SchemaOperationGuard;
use App\Support\Database\TestDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        SchemaOperationGuard::assertAllowed();
        if (DB::table('productos')->whereRaw('ABS(precio_venta-ROUND(precio_venta,2)) > 0.0000001')->exists()) {
            throw new LogicException('Reconcile prices with more than two decimals before changing precision.');
        }
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('productos', fn (Blueprint $table) => $table->decimal('precio_venta', 14, 2)->nullable()->change());
        }
        Schema::create('operaciones', function (Blueprint $table): void {
            $table->id();
            $table->integer('id_usuario');
            $table->string('accion', 40);
            $table->string('clave', 80);
            $table->string('huella', 64);
            $table->text('respuesta')->nullable();
            $table->timestamp('created_at');
            $table->unique(['id_usuario', 'accion', 'clave']);
        });
        Schema::create('auditoria_operaciones', function (Blueprint $table): void {
            $table->id();
            $table->integer('id_usuario')->index();
            $table->string('accion', 60);
            $table->string('entidad', 60);
            $table->string('referencia', 80);
            $table->text('cambios');
            $table->timestamp('created_at')->index();
        });
        Schema::create('existencias', function (Blueprint $table): void {
            $table->id();
            $table->integer('id_producto');
            $table->integer('id_almacen');
            $table->integer('stock')->default(0);
            $table->integer('saldo_inicial')->default(0);
            $table->timestamp('created_at');
            $table->unique(['id_producto', 'id_almacen']);
            $table->foreign('id_producto')->references('id_producto')->on('productos');
            $table->foreign('id_almacen')->references('id')->on('almacenes');
        });
        $invalid = DB::table('productos')->leftJoin('almacenes', 'productos.id_almacen', '=', 'almacenes.id')->whereNull('almacenes.id')->orWhere('productos.stock', '<', 0)->exists();
        if ($invalid) {
            throw new LogicException('Reconcile missing warehouses or negative stock before importing inventory.');
        }
        DB::table('productos')->orderBy('id_producto')->chunk(250, function ($products): void {
            foreach ($products as $product) {
                DB::table('existencias')->insert(['id_producto' => $product->id_producto, 'id_almacen' => $product->id_almacen,
                    'stock' => $product->stock, 'saldo_inicial' => $product->stock, 'created_at' => now()]);
            }
        });
        Schema::create('transferencias', function (Blueprint $table): void {
            $table->id();
            $table->integer('id_usuario');
            $table->integer('origen');
            $table->integer('destino');
            $table->string('estado', 20)->index();
            $table->timestamps();
        });
        Schema::create('detalle_transferencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('id_transferencia')->constrained('transferencias');
            $table->integer('id_producto');
            $table->integer('cantidad');
            $table->unique(['id_transferencia', 'id_producto']);
        });
        Schema::table('tipo_moneda', function (Blueprint $table): void {
            $table->string('codigo', 3)->nullable()->unique();
            $table->boolean('principal')->default(false);
        });
        // Existing site/config defines Soles (S/) as its display/base currency.
        DB::table('tipo_moneda')->where('simbolo', 'S/')->update(['codigo' => 'PEN', 'principal' => true]);
        foreach (['USD' => 'Dólares Americanos', 'EUR' => 'Euros', 'JPY' => 'Yen japonés'] as $code => $name) {
            DB::table('tipo_moneda')->where('descripcion', $name)->update(['codigo' => $code]);
        }
        foreach (['ventas', 'compras'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->integer('id_moneda')->nullable()->index();
                $table->string('moneda_codigo', 3)->nullable();
                $table->decimal('tipo_cambio_aplicado', 18, 6)->nullable();
                if ($name === 'ventas') {
                    $table->decimal('total_base', 14, 2)->nullable();
                    $table->decimal('saldo_utilizado', 14, 2)->nullable();
                    $table->decimal('cobro_base', 14, 2)->nullable();
                }
            });
        }
        Schema::table('detalle_ventas', function (Blueprint $table): void {
            $table->integer('id_almacen')->nullable();
            $table->decimal('costo_unitario_base', 14, 2)->nullable();
            $table->decimal('importe_neto', 14, 2)->nullable();
        });
        Schema::table('detalles_cambio', fn (Blueprint $table) => $table->bigInteger('id_detalle_venta')->nullable()->index());
        Schema::table('cambios_ventas', fn (Blueprint $table) => $table->integer('id_usuario')->nullable()->index());
        Schema::table('saldos_clientes', fn (Blueprint $table) => $table->integer('id_moneda')->nullable()->index());
        foreach (['productos', 'categorias'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->boolean('publicado')->default(true);
                $table->string('slug', 160)->nullable()->unique();
            });
            $key = $name === 'productos' ? 'id_producto' : 'id_categoria';
            DB::table($name)->orderBy($key)->chunk(250, function ($rows) use ($name, $key): void {
                foreach ($rows as $row) {
                    DB::table($name)->where($key, $row->{$key})->update(['slug' => ($name === 'productos' ? 'producto-' : 'categoria-').$row->{$key}]);
                }
            });
        }
        // New capabilities are explicit; only existing role administrators receive them.
        $administrators = DB::table('rol_permiso')->join('permisos', 'permisos.id_permiso', '=', 'rol_permiso.id_permiso')
            ->where('permisos.nombre_permiso', 'admin_gestionar_roles')->pluck('rol_permiso.id_rol');
        foreach (['servicios_gestionar', 'inventario_ajustar', 'ventas_devolver', 'ventas_aplicar_descuento', 'backups_gestionar'] as $permission) {
            DB::table('permisos')->insertOrIgnore(['nombre_permiso' => $permission]);
            $id = DB::table('permisos')->where('nombre_permiso', $permission)->value('id_permiso');
            foreach ($administrators as $role) {
                DB::table('rol_permiso')->insertOrIgnore(['id_rol' => $role, 'id_permiso' => $id]);
            }
        }
        foreach ($this->relations() as [$source,$column,$target,$key]) {
            if (DB::table($source.' as s')->leftJoin($target.' as t', 's.'.$column, '=', 't.'.$key)->whereNotNull('s.'.$column)->whereNull('t.'.$key)->exists()) {
                throw new LogicException('Existing orphan references require reconciliation: '.$source.'.'.$column);
            }
            Schema::table($source, fn (Blueprint $table) => $table->foreign($column, 'app_'.$source.'_'.$column.'_fk')->references($key)->on($target));
        }
        Schema::table('ventas', fn (Blueprint $table) => $table->index(['fecha_venta', 'estado'], 'app_ventas_fecha_estado'));
        Schema::table('compras', fn (Blueprint $table) => $table->index('fecha_compra', 'app_compras_fecha'));
        Schema::table('movimientos_inventario', fn (Blueprint $table) => $table->index('fecha', 'app_movimientos_fecha'));
    }

    public function down(): void
    {
        TestDatabaseGuard::assertIsolated();
        foreach (array_reverse($this->relations()) as [$source,$column,$target,$key]) {
            Schema::table($source, fn (Blueprint $table) => $table->dropForeign(DB::getDriverName() === 'sqlite' ? [$column] : 'app_'.$source.'_'.$column.'_fk'));
        }
        Schema::table('ventas', fn (Blueprint $table) => $table->dropIndex('app_ventas_fecha_estado'));
        Schema::table('compras', fn (Blueprint $table) => $table->dropIndex('app_compras_fecha'));
        Schema::table('movimientos_inventario', fn (Blueprint $table) => $table->dropIndex('app_movimientos_fecha'));
        foreach (['productos', 'categorias'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropUnique(['slug']));
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['publicado', 'slug']));
        }
        Schema::table('saldos_clientes', fn (Blueprint $table) => $table->dropIndex(['id_moneda']));
        Schema::table('cambios_ventas', fn (Blueprint $table) => $table->dropIndex(['id_usuario']));
        Schema::table('detalles_cambio', fn (Blueprint $table) => $table->dropIndex(['id_detalle_venta']));
        Schema::table('saldos_clientes', fn (Blueprint $table) => $table->dropColumn('id_moneda'));
        Schema::table('cambios_ventas', fn (Blueprint $table) => $table->dropColumn('id_usuario'));
        Schema::table('detalles_cambio', fn (Blueprint $table) => $table->dropColumn('id_detalle_venta'));
        Schema::table('detalle_ventas', fn (Blueprint $table) => $table->dropColumn(['id_almacen', 'costo_unitario_base', 'importe_neto']));
        foreach (['ventas', 'compras'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropIndex(['id_moneda']));
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn($name === 'ventas' ? ['id_moneda', 'moneda_codigo', 'tipo_cambio_aplicado', 'total_base', 'saldo_utilizado', 'cobro_base'] : ['id_moneda', 'moneda_codigo', 'tipo_cambio_aplicado']));
        }
        Schema::table('tipo_moneda', fn (Blueprint $table) => $table->dropUnique(['codigo']));
        Schema::table('tipo_moneda', fn (Blueprint $table) => $table->dropColumn(['codigo', 'principal']));
        foreach (['detalle_transferencias', 'transferencias', 'existencias', 'auditoria_operaciones', 'operaciones'] as $name) {
            Schema::dropIfExists($name);
        }
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('productos', fn (Blueprint $table) => $table->double('precio_venta')->nullable()->change());
        }
    }

    private function relations(): array
    {
        return [['productos', 'id_categoria', 'categorias', 'id_categoria'], ['productos', 'id_almacen', 'almacenes', 'id'], ['productos', 'id_proveedor_preferido', 'proveedores', 'id_proveedor'],
            ['ventas', 'id_cliente', 'clientes', 'id_cliente'], ['ventas', 'id_usuario', 'usuarios', 'id_usuario'], ['ventas', 'id_turno', 'turnos_caja', 'id_turno'],
            ['detalle_ventas', 'id_servicio', 'servicios', 'id_servicio'], ['compras', 'id_almacen', 'almacenes', 'id'], ['tipo_cambio', 'id_moneda', 'tipo_moneda', 'id'], ['usuarios', 'id_rol', 'roles', 'id_rol']];
    }
};
