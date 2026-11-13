<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Cash\CashSession;
use App\Services\Reports\ReportQuery;
use App\Services\Sales\SaleCheckout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CommercialTestCase;

final class CatalogAndExportsTest extends CommercialTestCase
{
    public function test_catalog_only_exposes_published_fields_and_reflects_changes(): void
    {
        $response = $this->getJson('/api/catalogo/productos')->assertOk()->assertJsonCount(1, 'data');
        $item = $response->json('data.0');
        foreach (['precio_compra', 'stock', 'id_proveedor_preferido', 'id_usuario'] as $private) {
            $this->assertArrayNotHasKey($private, $item);
        }
        DB::table('productos')->where('id_producto', $this->product)->update(['nombre' => 'Papel actualizado', 'precio_venta' => '30.00']);
        $this->get('/catalogo')->assertOk()->assertSee('Papel actualizado')->assertSee('30.00');
        $this->get('/catalogo/productos/papel')->assertOk()->assertSee('application/ld+json', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/catalogo/productos/papel');
        DB::table('categorias')->update(['activo' => 0]);
        $this->getJson('/api/catalogo/productos')->assertJsonCount(0, 'data');
        $this->get('/catalogo/productos/papel')->assertNotFound();
    }

    public function test_reports_do_not_duplicate_headers_and_exports_are_valid_files(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['carrito' => [['tipo' => 'producto', 'id' => $this->product, 'cantidad' => 1], ['tipo' => 'producto', 'id' => $this->product, 'cantidad' => 1]]]));
        $rows = app(ReportQuery::class)->build('ventas', today()->toDateString(), today()->toDateString())->get();
        $this->assertCount(1, $rows);
        $this->assertEquals(50, $rows->first()->importe);
        $reporter = $this->userWithPermissions(['reportes_ver_ventas']);
        $this->actingAs($reporter);
        $xlsx = $this->get('/reporte/ventas?formato=xlsx')->assertOk();
        $file = $xlsx->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', file_get_contents($file, false, null, 0, 2));
        $pdf = $this->get('/reporte/ventas?formato=pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $directory = base_path('../docs/validation');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        copy($file, $directory.'/reporte-prueba.xlsx');
        file_put_contents($directory.'/reporte-prueba.pdf', $pdf->getContent());
    }

    public function test_admin_category_product_photo_price_and_deactivation_reach_public_catalog(): void
    {
        Storage::fake('local');
        $actor = $this->userWithPermissions(['categorias_crear_editar', 'categorias_ver_lista', 'categorias_cambiar_estado', 'productos_crear', 'productos_editar', 'productos_cambiar_estado']);
        $this->actingAs($actor)->post('/categoria/guardar', ['nombre' => 'Nueva categoría', 'publicado' => 1])->assertRedirect();
        $category = DB::table('categorias')->where('nombre', 'Nueva categoría')->value('id_categoria');
        $fields = ['nombre' => 'Nuevo producto', 'id_categoria' => $category, 'id_almacen' => $this->warehouse, 'precio_compra' => '2.00', 'precio_venta' => '5.00', 'stock_minimo' => 1, 'stock_inicial' => 2, 'publicado' => 1];
        $image = new UploadedFile(public_path('img/logo.png'), 'foto.png', 'image/png', null, true);
        $response = $this->postJson('/producto/guardar', $fields + ['imagen' => $image])->assertOk();
        $id = $response->json('id_producto');
        $this->get('/catalogo/imagenes/'.$id)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->getJson('/api/catalogo/productos/producto-'.$id)->assertOk()->assertJsonPath('data.nombre', 'Nuevo producto');
        unset($fields['stock_inicial']);
        $fields['precio_venta'] = '7.50';
        $fields['nombre'] = 'Producto actualizado';
        $this->putJson('/producto/'.$id, $fields)->assertOk();
        $this->getJson('/api/catalogo/productos/producto-'.$id)->assertJsonPath('data.precio', '7.50');
        $this->patch('/categoria/'.$category.'/estado', ['activo' => 0])->assertRedirect();
        $this->getJson('/api/catalogo/productos/producto-'.$id)->assertNotFound();
        $this->patch('/categoria/'.$category.'/estado', ['activo' => 1])->assertRedirect();
        $this->patch('/producto/'.$id.'/estado', ['activo' => 0])->assertRedirect();
        $this->getJson('/api/catalogo/productos/producto-'.$id)->assertNotFound();
    }
}
