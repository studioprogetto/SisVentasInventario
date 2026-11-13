<?php

declare(strict_types=1);

use App\Support\Database\SchemaOperationGuard;
use App\Support\Database\TestDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        SchemaOperationGuard::assertAllowed();
        DB::statement('CREATE VIEW catalogo_categorias AS SELECT id_categoria,nombre,slug FROM categorias WHERE activo=1 AND publicado=1');
        DB::statement('CREATE VIEW catalogo_productos AS SELECT p.id_producto,p.nombre,p.descripcion,p.precio_venta,p.slug,p.id_categoria,p.fecha_creacion FROM productos p JOIN categorias c ON c.id_categoria=p.id_categoria WHERE p.activo=1 AND p.publicado=1 AND c.activo=1 AND c.publicado=1');
        DB::statement("CREATE VIEW catalogo_populares AS SELECT p.id_producto,p.nombre,p.descripcion,p.precio_venta,p.slug,p.id_categoria FROM catalogo_productos p LEFT JOIN detalle_ventas d ON d.id_producto=p.id_producto LEFT JOIN ventas v ON v.id_venta=d.id_venta GROUP BY p.id_producto,p.nombre,p.descripcion,p.precio_venta,p.slug,p.id_categoria ORDER BY SUM(CASE WHEN v.estado='completada' THEN d.cantidad ELSE 0 END) DESC LIMIT 16");
    }

    public function down(): void
    {
        TestDatabaseGuard::assertIsolated();
        foreach (['catalogo_populares', 'catalogo_productos', 'catalogo_categorias'] as $view) {
            DB::statement('DROP VIEW '.$view);
        }
    }
};
