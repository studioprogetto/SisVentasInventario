<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\TipoCambio;
use App\Models\Venta;
use App\Support\Database\TestDatabaseGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\Support\BusinessTestCase;

final class ModelMappingTest extends BusinessTestCase
{
    public function test_every_entity_maps_to_existing_columns_and_relationships(): void
    {
        foreach (glob(app_path('Models/*.php')) as $path) {
            $class = 'App\\Models\\'.basename($path, '.php');
            $model = new $class;
            $this->assertTrue(Schema::hasTable($model->getTable()), $class);
            if (! $model instanceof TipoCambio) {
                $this->assertTrue(Schema::hasColumn($model->getTable(), $model->getKeyName()), $class);
            }
            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class || $method->getNumberOfRequiredParameters() > 0) {
                    continue;
                }
                $type = (string) $method->getReturnType();
                if (str_starts_with($type, 'Illuminate\\Database\\Eloquent\\Relations\\')) {
                    // Execute each relation query, not just inspect its name.
                    $model->{$method->getName()}()->get();
                    $this->addToAssertionCount(1);
                }
            }
        }
    }

    public function test_sales_and_products_resolve_their_real_foreign_keys(): void
    {
        $user = $this->userWithPermissions();
        $category = DB::table('categorias')->insertGetId(['nombre' => 'Prueba'], 'id_categoria');
        $warehouse = DB::table('almacenes')->insertGetId(['nombre' => 'Prueba']);
        $product = DB::table('productos')->insertGetId(['nombre' => 'Papel', 'id_categoria' => $category, 'id_almacen' => $warehouse, 'precio_compra' => 1, 'precio_venta' => 2, 'stock' => 10], 'id_producto');
        $sale = DB::table('ventas')->insertGetId(['id_usuario' => $user->getKey(), 'total_venta' => 2, 'numero_cliente' => 1], 'id_venta');
        DB::table('detalle_ventas')->insert(['id_venta' => $sale, 'id_producto' => $product, 'tipo_item' => 'producto', 'cantidad' => 1, 'precio_unitario' => 2]);
        $this->assertSame('Prueba', Producto::query()->findOrFail($product)->categoria->nombre);
        $this->assertSame($warehouse, Producto::query()->findOrFail($product)->almacen->getKey());
        $this->assertSame($product, Venta::query()->findOrFail($sale)->detalles->first()->producto->getKey());
    }

    public function test_composite_exchange_rate_cannot_be_saved_as_an_id_model(): void
    {
        $this->expectException(LogicException::class);
        (new TipoCambio)->save();
    }

    public function test_schema_guard_refuses_the_business_database_even_in_testing(): void
    {
        $connection = config('database.default');
        config(['database.connections.'.$connection.'.database' => 'tienda_sistema']);
        DB::purge($connection);
        $this->expectException(LogicException::class);
        TestDatabaseGuard::assertIsolated();
    }
}
