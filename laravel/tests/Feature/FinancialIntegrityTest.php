<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Cash\CashSession;
use App\Services\Sales\ReturnCredit;
use App\Services\Sales\SaleCheckout;
use App\Services\Sales\SaleQuote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\CommercialTestCase;

final class FinancialIntegrityTest extends CommercialTestCase
{
    public function test_exchange_rate_and_cost_are_snapshots_not_current_values(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        $usd = DB::table('tipo_moneda')->insertGetId(['descripcion' => 'Dólares', 'codigo' => 'USD', 'simbolo' => '$']);
        DB::table('tipo_cambio')->insert(['fecha' => today()->toDateString(), 'id_moneda' => $usd, 'tipo_moneda' => 'USD', 'valor_soles' => '4']);
        $sale = app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['id_moneda' => $usd]));
        $this->assertSame('12.50', $sale['total']);
        DB::table('tipo_cambio')->where('id_moneda', $usd)->update(['valor_soles' => '5']);
        DB::table('productos')->where('id_producto', $this->product)->update(['precio_compra' => '20']);
        $this->assertDatabaseHas('ventas', ['id_venta' => $sale['id_venta'], 'tipo_cambio_aplicado' => '4', 'total_base' => '50']);
        $this->assertDatabaseHas('detalle_ventas', ['id_venta' => $sale['id_venta'], 'costo_unitario_base' => '10']);
    }

    public function test_partial_returns_preserve_every_cent_of_discounted_sale(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        DB::table('productos')->where('id_producto', $this->product)->update(['precio_venta' => '1.11']);
        $sale = app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['descuento_manual' => '0.01', 'carrito' => [['tipo' => 'producto', 'id' => $this->product, 'cantidad' => 3]]]));
        $line = DB::table('detalle_ventas')->value('id_detalle_venta');
        $total = '0.00';
        for ($i = 0; $i < 3; $i++) {
            $result = app(ReturnCredit::class)->refund($this->operator->getKey(), ['idempotency_key' => (string) Str::uuid(), 'id_venta_original' => $sale['id_venta'], 'observacion' => 'Devolución parcial', 'productos' => [['id_detalle_venta' => $line, 'cantidad' => 1]]]);
            $total = bcadd($total, $result['saldo_generado'], 2);
        }
        $this->assertSame('3.32', $total);
        $this->assertDatabaseHas('productos', ['stock' => 10]);
    }

    public function test_reusing_key_with_changed_payload_is_rejected_without_second_sale(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        $data = $this->cart();
        app(SaleCheckout::class)->checkout($this->operator->getKey(), $data);
        try {
            app(SaleCheckout::class)->checkout($this->operator->getKey(), array_replace($data, ['observacion' => 'Modificada']));
            $this->fail('Changed payload must fail.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ventas', 1);
        }
    }

    public function test_generic_sale_requires_registered_holder_and_never_pays_cash(): void
    {
        app(CashSession::class)->open($this->operator->getKey(), '0');
        $sale = app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['id_cliente' => null]));
        $line = DB::table('detalle_ventas')->value('id_detalle_venta');
        $claim = ['idempotency_key' => (string) Str::uuid(), 'id_venta_original'=>$sale['id_venta'], 'observacion'=>'Producto defectuoso', 'productos'=>[['id_detalle_venta'=>$line,'cantidad'=>1]], 'condicion'=>'defectuoso'];
        $this->actingAs($this->operator)->postJson(route('sales.return'), $claim + ['destino'=>'efectivo'])->assertUnprocessable();
        $this->postJson(route('sales.return'), $claim + ['destino'=>'saldo'])->assertUnprocessable();
        $this->postJson(route('sales.return'), $claim + ['destino'=>'saldo','id_cliente'=>$this->client])->assertOk();
        $this->assertDatabaseHas('saldos_clientes',['id_cliente'=>$this->client,'saldo'=>25]);
        $this->assertDatabaseCount('movimientos_caja',0);
        $this->assertDatabaseHas('productos',['id_producto'=>$this->product,'stock'=>8]);
        $next = app(SaleCheckout::class)->checkout($this->operator->getKey(), $this->cart(['usar_saldo'=>true,'monto_recibido'=>'25.00']));
        $this->assertDatabaseHas('ventas',['id_venta'=>$next['id_venta'],'saldo_utilizado'=>25,'cobro_base'=>25]);
        $this->travel(49)->hours();
        $claim['idempotency_key'] = (string) Str::uuid();
        $this->postJson(route('sales.return'), $claim + ['destino'=>'saldo'])->assertUnprocessable();
    }

    public function test_quote_is_read_only_and_confirmed_total_is_rechecked(): void
    {
        DB::table('clientes')->where('id_cliente', $this->client)->update(['sellos' => 5]);
        $data = $this->cart();
        $quote = app(SaleQuote::class)->quote($data);
        $this->assertSame('47.50', $quote['total']);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseHas('clientes', ['id_cliente' => $this->client, 'sellos' => 5]);
        app(CashSession::class)->open($this->operator->getKey(), '0');
        DB::table('productos')->where('id_producto', $this->product)->update(['precio_venta' => '30']);
        try {
            app(SaleCheckout::class)->checkout($this->operator->getKey(), $data + ['accepted_total' => $quote['total']]);
            $this->fail('A changed total must be recalculated.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ventas', 0);
            $this->assertDatabaseHas('clientes', ['id_cliente' => $this->client, 'sellos' => 5]);
        }
    }

    public function test_existing_open_turn_requires_review_without_relabelling_legacy_currency(): void
    {
        $turn = app(CashSession::class)->open($this->operator->getKey(), '0');
        DB::table('ventas')->insert(['id_usuario' => $this->operator->getKey(), 'id_turno' => $turn['id_turno'], 'total_venta' => '10', 'metodo_pago' => 'efectivo', 'estado' => 'completada', 'numero_cliente' => 0]);
        try {
            app(CashSession::class)->close($this->operator->getKey(), '10');
            $this->fail('Legacy review required.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('turnos_caja', ['id_turno' => $turn['id_turno'], 'estado' => 'abierto']);
        }
        app(CashSession::class)->close($this->operator->getKey(), '10', true);
        $this->assertDatabaseHas('ventas', ['id_moneda' => null]);
        $this->assertDatabaseHas('auditoria_operaciones', ['accion' => 'cierre_historico_revisado']);
    }
}
