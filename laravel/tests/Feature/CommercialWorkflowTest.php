<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Cash\CashSession;
use App\Services\Inventory\TransferWorkflow;
use App\Services\Purchases\PurchaseReceipt;
use App\Services\Sales\ProductExchange;
use App\Services\Sales\ReturnCredit;
use App\Services\Sales\SaleCheckout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\CommercialTestCase;

final class CommercialWorkflowTest extends CommercialTestCase
{
    public function test_sale_recalculates_prices_records_ledger_and_is_idempotent(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '100');
        $data = $this->cart();
        $sale = app(SaleCheckout::class)->checkout($this->operator->getKey(), $data);
        $this->assertSame('50.00', $sale['total']);
        $this->assertSame($sale, app(SaleCheckout::class)->checkout($this->operator->getKey(), $data));
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseHas('productos', ['id_producto' => $this->product, 'stock' => 8]);
        $this->assertDatabaseHas('movimientos_inventario', ['tipo_movimiento' => 'venta', 'cantidad' => 2]);
        $this->assertDatabaseHas('clientes', ['id_cliente' => $this->client, 'sellos' => 1, 'total_compras' => 1]);
    }

    public function test_failed_sale_rolls_back_stock_header_and_loyalty(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        try {
            app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['carrito' => [['tipo' => 'producto', 'id' => $this->product, 'cantidad' => 11]]]));
            $this->fail('Insufficient stock must fail.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ventas', 0);
            $this->assertDatabaseCount('movimientos_inventario', 0);
            $this->assertDatabaseHas('clientes', ['id_cliente' => $this->client, 'sellos' => 0, 'total_compras' => 0]);
            $this->assertDatabaseHas('productos', ['id_producto' => $this->product, 'stock' => 10]);
        }
    }

    public function test_sale_requires_active_cash_and_services_do_not_decrease_stock(): void
    {
        try {
            app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart());
            $this->fail('Cash required.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ventas', 0);
        }
        app(CashSession::class)->open($this->operator->getKey(), '0');
        $service = DB::table('servicios')->insertGetId(['nombre' => 'Impresión', 'precio_venta' => '2.50'], 'id_servicio');
        app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['carrito' => [['tipo' => 'servicio', 'id' => $service, 'cantidad' => 3]]]));
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseHas('productos', ['id_producto' => $this->product, 'stock' => 10]);
    }

    public function test_purchase_only_increases_stock_when_received_once(): void
    {
        $supplier = DB::table('proveedores')->insertGetId(['nombre_proveedor' => 'Proveedor de prueba'], 'id_proveedor');
        $purchase = app(PurchaseReceipt::class)->order($this->operator->getKey(), ['idempotency_key' => (string) Str::uuid(), 'id_proveedor' => $supplier,
            'id_almacen' => $this->destination, 'productos' => [['id_producto' => $this->product, 'cantidad' => 5, 'costo_unitario' => '12']]]);
        $this->assertDatabaseHas('productos', ['id_producto' => $this->product, 'stock' => 10]);
        app(PurchaseReceipt::class)->receive($this->operator->getKey(), $purchase['id_compra']);
        $this->assertDatabaseHas('existencias', ['id_producto' => $this->product, 'id_almacen' => $this->destination, 'stock' => 5]);
        try {
            app(PurchaseReceipt::class)->receive($this->operator->getKey(), $purchase['id_compra']);
            $this->fail('Duplicate receipt must fail.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('productos', 1);
            $this->assertDatabaseCount('movimientos_inventario', 1);
        }
    }

    public function test_transfer_has_transit_and_preserves_product_identity(): void
    {
        $workflow = app(TransferWorkflow::class);
        $transfer = $workflow->create($this->operator->getKey(), ['idempotency_key' => (string) Str::uuid(), 'origen' => $this->warehouse, 'destino' => $this->destination,
            'productos' => [['id_producto' => $this->product, 'cantidad' => 3]]]);
        $this->assertDatabaseHas('productos', ['stock' => 10]);
        $workflow->transition($this->operator->getKey(), $transfer['id_transferencia'], 'enviada');
        $this->assertDatabaseHas('productos', ['stock' => 7]);
        $workflow->transition($this->operator->getKey(), $transfer['id_transferencia'], 'recibida');
        $this->assertDatabaseHas('productos', ['id_producto' => $this->product, 'stock' => 10]);
        $this->assertDatabaseHas('existencias', ['id_producto' => $this->product, 'id_almacen' => $this->destination, 'stock' => 3]);
        $this->assertDatabaseCount('productos', 1);
        $this->expectException(ValidationException::class);
        $workflow->transition($this->operator->getKey(), $transfer['id_transferencia'], 'recibida');
    }

    public function test_return_uses_original_net_price_and_rejects_duplicate_quantity(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        $sale = app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['descuento_manual' => '10']));
        $line = DB::table('detalle_ventas')->where('id_venta', $sale['id_venta'])->value('id_detalle_venta');
        $data = ['idempotency_key' => (string) Str::uuid(), 'id_venta_original' => $sale['id_venta'], 'observacion' => 'Devolución autorizada', 'productos' => [['id_detalle_venta' => $line, 'cantidad' => 2]]];
        $result = app(ReturnCredit::class)->refund($this->operator->getKey(), $data);
        $this->assertSame('40.00', $result['saldo_generado']);
        $this->assertDatabaseHas('productos', ['id_producto' => $this->product, 'stock' => 10]);
        $this->assertDatabaseHas('ventas', ['id_venta' => $sale['id_venta'], 'estado' => 'anulada']);
        $this->assertSame($result, app(ReturnCredit::class)->refund($this->operator->getKey(), $data));
        $this->expectException(ValidationException::class);
        app(ReturnCredit::class)->refund($this->operator->getKey(), array_replace($data, ['idempotency_key' => (string) Str::uuid()]));
    }

    public function test_exchange_uses_credit_and_collects_only_difference(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        $sale = app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart());
        $line = DB::table('detalle_ventas')->where('id_venta', $sale['id_venta'])->value('id_detalle_venta');
        $result = app(ProductExchange::class)->exchange($this->operator->getKey(), ['idempotency_key' => (string) Str::uuid(), 'id_venta_original' => $sale['id_venta'], 'observacion' => 'Cambio autorizado',
            'productos' => [['id_detalle_venta' => $line, 'cantidad' => 1]], 'carrito' => [['tipo' => 'producto', 'id' => $this->product, 'cantidad' => 2]], 'metodo_pago' => 'efectivo']);
        $this->assertDatabaseHas('ventas', ['id_venta' => $result['id_venta'], 'saldo_utilizado' => '25.00', 'cobro_base' => '25.00']);
        $closed = app(CashSession::class)->close($this->operator->getKey(), '75');
        $this->assertSame('75.00', $closed['esperado']);
        $this->assertSame('0.00', $closed['diferencia']);
    }

    public function test_cash_excludes_electronic_payments_and_cannot_close_twice(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '20');
        app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['metodo_pago' => 'yape']));
        $result = app(CashSession::class)->close($this->operator->getKey(), '20');
        $this->assertSame('20.00', $result['esperado']);
        $this->expectException(ValidationException::class);
        app(CashSession::class)->close($this->operator->getKey(), '20');
    }
}
