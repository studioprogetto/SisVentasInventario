<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Cash\CashSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CommercialTestCase;

final class PosTest extends CommercialTestCase
{
    public function test_pos_filters_real_warehouse_stock_and_exposes_private_product_image_only_to_sales_staff(): void
    {
        DB::table('productos')->where('id_producto', $this->product)->update(['publicado' => 0, 'codigo_barra' => 'PAP001']);
        DB::table('existencias')->insert(['id_producto' => $this->product, 'id_almacen' => $this->destination, 'stock' => 3, 'saldo_inicial' => 3, 'created_at' => now()]);
        $this->getJson(route('pos.items'))->assertUnauthorized();
        $this->actingAs($this->operator)->getJson(route('pos.items', ['term' => 'PAP001', 'almacen' => $this->destination]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.stock', 3)->assertJsonPath('data.0.almacen', 'Destino')
            ->assertJsonPath('data.0.precio_venta', '25.00')->assertJsonPath('data.0.imagen', route('pos.image', $this->product));
        $this->get(route('pos.image', $this->product))->assertOk();
        Storage::fake('local');
        Storage::disk('local')->put('productos/prueba.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jv1sAAAAASUVORK5CYII='));
        DB::table('productos')->where('id_producto', $this->product)->update(['imagen_path' => 'storage:productos/prueba.png']);
        $this->get(route('pos.image', $this->product))->assertOk()->assertHeader('Content-Type', 'image/png');
        DB::table('productos')->where('id_producto', $this->product)->update(['imagen_path' => 'https://example.test/producto.png']);
        $this->get(route('pos.image', $this->product))->assertRedirect('https://example.test/producto.png');
        $this->get(route('catalog.image', $this->product))->assertNotFound();
        $this->actingAs($this->userWithPermissions([]))->getJson(route('pos.items'))->assertForbidden();
    }

    public function test_pos_paginates_services_and_products_and_searches_customer_document_without_exposing_other_fields(): void
    {
        DB::table('clientes')->where('id_cliente', $this->client)->update(['documento_identidad' => '72709806', 'email' => 'private@example.test']);
        DB::table('servicios')->insert(['nombre' => 'Impresión', 'precio_venta' => '2.50']);
        $this->actingAs($this->operator)->getJson(route('pos.items', ['tipo' => 'servicio']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.stock', null)->assertJsonPath('data.0.tipo', 'servicio');
        $this->getJson(route('pos.items', ['per_page' => 500]))->assertUnprocessable();
        $this->getJson(route('clients.search', ['term' => '727098']))->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id_cliente', $this->client)->assertJsonMissingPath('0.email');
    }

    public function test_loyalty_card_reads_existing_stamps_and_only_currency_identified_pending_credit(): void
    {
        DB::table('clientes')->where('id_cliente', $this->client)->update(['sellos' => 5, 'total_compras' => 5]);
        DB::table('saldos_clientes')->insert([
            ['id_cliente' => $this->client, 'saldo' => '8.00', 'estado' => 'pendiente', 'id_moneda' => $this->currency],
            ['id_cliente' => $this->client, 'saldo' => '99.00', 'estado' => 'pendiente', 'id_moneda' => null],
        ]);
        $this->actingAs($this->operator)->getJson(route('pos.client', $this->client))->assertOk()
            ->assertJsonPath('sellos', 5)->assertJsonPath('descuento_proxima_compra', 5)->assertJsonPath('saldo_disponible', '8.00');
        $quote = $this->postJson(route('sales.quote'), $this->cart())->assertOk()->assertJsonPath('total', '47.50')
            ->assertJsonPath('lineas.0.precio', '25.00')->assertJsonMissingPath('lineas.0.costo');
        $this->assertDatabaseHas('clientes', ['id_cliente' => $this->client, 'sellos' => 5]);
        DB::table('clientes')->where('id_cliente', $this->client)->update(['sellos' => 11]);
        $this->getJson(route('pos.client', $this->client))->assertJsonPath('descuento_proxima_compra', 10);
        $this->postJson(route('sales.quote'), $this->cart())->assertJsonPath('total', '45.00');
    }

    public function test_cart_can_sell_same_product_from_two_warehouses_and_earn_one_stamp(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        DB::table('existencias')->insert(['id_producto' => $this->product, 'id_almacen' => $this->destination, 'stock' => 3, 'saldo_inicial' => 3, 'created_at' => now()]);
        DB::table('productos')->where('id_producto', $this->product)->update(['stock' => 13]);
        $data = $this->cart(['carrito' => [
            ['tipo' => 'producto', 'id' => $this->product, 'id_almacen' => $this->warehouse, 'cantidad' => 1],
            ['tipo' => 'producto', 'id' => $this->product, 'id_almacen' => $this->destination, 'cantidad' => 2],
        ], 'accepted_total' => '75.00']);
        $this->actingAs($this->operator)->postJson(route('sales.store'), $data)->assertSuccessful();
        $this->assertDatabaseHas('existencias', ['id_producto' => $this->product, 'id_almacen' => $this->warehouse, 'stock' => 9]);
        $this->assertDatabaseHas('existencias', ['id_producto' => $this->product, 'id_almacen' => $this->destination, 'stock' => 1]);
        $this->assertDatabaseHas('clientes', ['id_cliente' => $this->client, 'sellos' => 1]);
    }

    public function test_external_stock_drift_is_rejected_without_overwriting_stock_or_earning_stamps(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        DB::table('productos')->where('id_producto', $this->product)->update(['stock' => 7]);
        $this->actingAs($this->operator)->postJson(route('sales.quote'), $this->cart())->assertUnprocessable()->assertJsonValidationErrors('inventario');
        $this->postJson(route('sales.store'), $this->cart())->assertUnprocessable()->assertJsonValidationErrors('inventario');
        $this->assertDatabaseHas('productos', ['id_producto' => $this->product, 'stock' => 7]);
        $this->assertDatabaseHas('existencias', ['id_producto' => $this->product, 'stock' => 10]);
        $this->assertDatabaseHas('clientes', ['id_cliente' => $this->client, 'sellos' => 0]);
        $this->assertDatabaseCount('ventas', 0);
    }
}
