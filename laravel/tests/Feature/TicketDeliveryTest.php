<?php

namespace Tests\Feature;

use App\Models\Venta;
use App\Services\Cash\CashSession;
use App\Services\Sales\SaleCheckout;
use App\Services\Tickets\TicketDelivery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CommercialTestCase;

final class TicketDeliveryTest extends CommercialTestCase
{
    public function test_display_pairing_and_original_documents_are_scoped_to_the_delivery(): void
    {
        Storage::fake('local');
        $user = $this->operator->getKey();
        app(CashSession::class)->open($user, '0');
        $first = app(SaleCheckout::class)->checkout($user, $this->cart())['id_venta'];
        $other = app(SaleCheckout::class)->checkout($user, $this->cart())['id_venta'];
        $admin = $this->userWithPermissions(['ventas_crear', 'admin_gestionar_roles']);
        $this->actingAs($admin);
        foreach ([$first, $other] as $sale) {
            $file = UploadedFile::fake()->create('boleta.pdf', 4, 'application/pdf');
            $this->post(route('tickets.attachForm'), ['venta' => $sale, 'tipo' => 'boleta', 'numero' => 'B001-'.$sale, 'documento' => $file])->assertSessionHasNoErrors();
        }
        $service = app(TicketDelivery::class);
        $this->actingAs($this->operator);
        $service->operate($user, $first, 'solicitar', 0);
        $paired = basename($this->get(route('tickets.screen'))->assertRedirect()->headers->get('Location'));
        $replacement = basename($this->get(route('tickets.screen'))->assertRedirect()->headers->get('Location'));
        auth()->logout();
        $this->get(route('tickets.screenData', $paired))->assertGone();
        $this->get(route('tickets.customerScreen', $replacement))->assertOk();
        $this->getJson(route('tickets.screenData', $replacement))->assertJsonPath('remaining', null);
        $docs = DB::table('documentos_ticket')->pluck('id', 'venta');
        $this->get(route('tickets.document', $docs[$first]))->assertGone();
        $this->post(route('tickets.claim', basename($service->display($user)['url'])))->assertRedirect();
        $this->get(route('tickets.document', $docs[$first]))->assertDownload('B001-'.$first.'.pdf');
        $this->get(route('tickets.document', $docs[$other]))->assertNotFound();
    }

    public function test_history_publishes_and_rotates_its_own_receipt(): void
    {
        $user = $this->operator->getKey();
        app(CashSession::class)->open($user, '0');
        $sale = app(SaleCheckout::class)->checkout($user, $this->cart())['id_venta'];
        $this->actingAs($this->operator)->get(route('sales.history'))->assertOk()->assertSee('Mostrar ticket por QR')->assertSee('Duración del QR');
        $internal = $this->get(route('sales.ticket', $sale))->assertOk()->assertDontSee('Mostrar ticket por QR');
        $page = route('tickets.publication', $sale);
        $publish = route('tickets.manage', $sale);
        $this->post($publish, ['accion' => 'publicar', 'segundos' => 10])->assertRedirect($page);
        $this->get($page)->assertOk()->assertSee('Volver a generar QR');
        $old = basename($this->getJson($page)->json('url'));
        $this->post($publish, ['accion' => 'publicar', 'segundos' => 0])->assertRedirect($page);
        $new = basename($this->getJson($page)->assertJsonPath('remaining', null)->json('url'));
        $this->assertNotSame($old, $new);
        auth()->logout();
        $this->get(route('tickets.confirm', $old))->assertGone();
        $this->get(route('tickets.confirm', $new))->assertDontSee('Cliente de prueba');
        $this->post(route('tickets.claim', $new))->assertRedirect();
        $receipt = $this->get(route('tickets.receipt'))->assertOk()->assertDontSee('Volver al POS');
        $paper = view('sales.partials.receipt', ['sale' => Venta::with('usuario', 'cliente', 'detalles.producto', 'detalles.servicio')->findOrFail($sale)])->render();
        $internal->assertSee($paper, false);
        $receipt->assertSee($paper, false);
    }

    public function test_qr_queue_claim_pdf_expiry_and_revocation(): void
    {
        $user = $this->operator->getKey();
        app(CashSession::class)->open($user, '0');
        $checkout = app(SaleCheckout::class);
        $first = $checkout->checkout($user, $this->cart())['id_venta'];
        $second = $checkout->checkout($user, $this->cart())['id_venta'];
        $delivery = app(TicketDelivery::class);
        $this->actingAs($this->operator)->post(route('tickets.manage', $first), ['accion' => 'solicitar'])->assertRedirect(route('tickets.center'));
        $delivery->operate($user, $second, 'solicitar', 60);
        $this->get(route('tickets.center'))->assertOk()->assertSee('Cola de entregas');
        $this->assertDatabaseHas('entregas_ticket', ['venta' => $second, 'estado' => 'pendiente']);
        $url = $delivery->display($user)['url'];
        $token = basename($url);
        $this->post(route('tickets.manage', $first), ['accion' => 'pausar'])->assertRedirect();
        $this->get(route('tickets.confirm', $token))->assertGone();
        $delivery->operate($user, $first, 'reanudar', null);
        $token = basename($delivery->display($user)['url']);
        auth()->logout();
        $this->get(route('tickets.confirm', $token))->assertOk()->assertSee('Recibir mi ticket')->assertDontSee('Papel');
        $this->get(route('tickets.receipt'))->assertGone();
        $this->post(route('tickets.claim', $token))->assertRedirect(route('tickets.receipt'));
        $this->post(route('tickets.claim', $token))->assertGone();
        $this->get(route('tickets.receipt'))->assertOk()->assertSee('Papel')->assertSee('Cliente de prueba')->assertSee('Política de cambios')->assertDontSee('Duración del QR');
        $pdf = $this->get(route('tickets.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $delivery->operate($user, $first, 'revocar', null);
        $this->get(route('tickets.receipt'))->assertGone();
        $delivery->operate($user, $second, 'mostrar', 10);
        $secondToken = basename($delivery->display($user)['url']);
        $this->travel(11)->seconds();
        $this->get(route('tickets.confirm', $secondToken))->assertGone();
        $this->assertDatabaseCount('ventas', 2);
        $this->assertSame(6, (int) DB::table('productos')->where('id_producto', $this->product)->value('stock'));
    }
}
