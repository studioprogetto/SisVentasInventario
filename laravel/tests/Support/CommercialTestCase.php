<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class CommercialTestCase extends BusinessTestCase
{
    protected Usuario $operator;

    protected int $warehouse;

    protected int $destination;

    protected int $product;

    protected int $client;

    protected int $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = $this->userWithPermissions(['ventas_crear', 'ventas_devolver', 'ventas_ver_listado', 'ventas_aplicar_descuento', 'caja_gestionar', 'compras_crear', 'compras_recibir', 'compras_ver_lista', 'transferencias_crear', 'inventario_ajustar', 'productos_ver_lista', 'productos_crear', 'productos_editar', 'productos_cambiar_estado', 'dashboard_ver']);
        $this->warehouse = DB::table('almacenes')->insertGetId(['nombre' => 'Origen']);
        $this->destination = DB::table('almacenes')->insertGetId(['nombre' => 'Destino']);
        $category = DB::table('categorias')->insertGetId(['nombre' => 'Papelería'], 'id_categoria');
        $this->currency = DB::table('tipo_moneda')->insertGetId(['descripcion' => 'Soles', 'simbolo' => 'S/', 'codigo' => 'PEN', 'principal' => 1]);
        $this->client = DB::table('clientes')->insertGetId(['nombre_cliente' => 'Cliente de prueba', 'sellos' => 0], 'id_cliente');
        $this->product = DB::table('productos')->insertGetId(['nombre' => 'Papel', 'id_categoria' => $category, 'id_almacen' => $this->warehouse, 'precio_compra' => '10.00', 'precio_venta' => '25.00', 'stock' => 10, 'stock_minimo' => 2, 'activo' => 1, 'publicado' => 1, 'slug' => 'papel'], 'id_producto');
        DB::table('existencias')->insert(['id_producto' => $this->product, 'id_almacen' => $this->warehouse, 'stock' => 10, 'saldo_inicial' => 10, 'created_at' => now()]);
    }

    protected function cart(array $overrides = []): array
    {
        return array_replace(['idempotency_key' => (string) Str::uuid(), 'id_cliente' => $this->client, 'metodo_pago' => 'efectivo',
            'carrito' => [['tipo' => 'producto', 'id' => $this->product, 'cantidad' => 2, 'precio' => '0.01', 'id_almacen' => $this->warehouse]]], $overrides);
    }
}
