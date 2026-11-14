<?php

namespace Tests\Feature;

use App\Services\Cash\CashSession;
use App\Services\Sales\SaleCheckout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CommercialTestCase;

class ApprovalInboxTest extends CommercialTestCase
{
    public function test_staff_request_requires_admin_and_cannot_be_applied_twice(): void
    {
        $worker = $this->operator;
        $worker->rol()->update(['nombre_rol'=>'Multifuncional 1']);
        $admin = $this->userWithPermissions();
        $admin->rol()->update(['nombre_rol'=>'Administrador']);
        $worker->refresh(); $admin->refresh();
        app(CashSession::class)->open($worker->getKey(),'25');
        $sale = app(SaleCheckout::class)->checkout($worker->getKey(),$this->cart());
        $fields = ['id_venta_original'=>$sale['id_venta'],'idempotency_key'=>(string) Str::uuid(),'observacion'=>'Producto dañado','destino'=>'saldo',
            'productos'=>[['id_detalle_venta'=>DB::table('detalle_ventas')->value('id_detalle_venta'),'cantidad'=>1]]];
        $this->actingAs($worker)->post(route('sales.return'),$fields)->assertForbidden();
        $this->post(route('returns.request'),$fields)->assertRedirect();
        $id = DB::table('solicitudes_devolucion')->value('id');
        $this->post(route('returns.decide',$id),['decision'=>'aprobada'])->assertForbidden();
        $this->assertDatabaseCount('cambios_ventas',0);
        $this->actingAs($admin)->get(route('notifications.index'))->assertOk()->assertSee('Aprobar solicitud');
        $this->post(route('returns.decide',$id),['decision'=>'aprobada'])->assertRedirect();
        $this->post(route('returns.decide',$id),['decision'=>'aprobada'])->assertStatus(409);
        $this->assertDatabaseCount('cambios_ventas',1);
        $this->actingAs($worker)->get(route('notifications.index'))->assertOk()->assertSee('Solicitud aprobada');
        $notice = DB::table('avisos_usuario')->where('usuario',$worker->getKey())->where('clave','decision-'.$id)->first();
        $this->assertNotNull($notice->leido);
        $this->patch(route('notifications.update',$notice->id),['accion'=>'eliminar'])->assertRedirect();
        $this->get(route('notifications.index',['eliminadas'=>1]))->assertOk()->assertSee('Solicitud aprobada');
        $this->travel(31)->days();
        $this->get(route('notifications.index',['eliminadas'=>1]))->assertDontSee('Solicitud aprobada');
    }
}
