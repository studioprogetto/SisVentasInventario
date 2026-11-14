<?php

namespace App\Http\Controllers;

use App\Http\Requests\Operations\OperationRequest;
use App\Services\Notifications\Inbox;
use App\Services\Sales\ReturnCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    public function index(Request $request, Inbox $inbox)
    {
        $user = $request->user();
        $inbox->sync($user);
        $trash = $request->boolean('eliminadas');
        $query = $inbox->mine($user);
        $trash ? $query->whereNotNull('eliminado') : $query->whereNull('eliminado');
        $items = $query->orderByDesc('id')->paginate(12)->withQueryString();
        if (!$trash && $user->avisos_autolectura && !$request->boolean('conservar')) {
            $inbox->mine($user)->whereIn('id',$items->pluck('id'))->whereNull('leido')->update(['leido'=>now()]);
            foreach ($items as $item) $item->leido ??= now();
        }
        $requests = $user->isAdministrator() && !$trash
            ? DB::table('solicitudes_devolucion')->where('estado','pendiente')->orderBy('id')->paginate(12,['*'],'solicitudes')->withQueryString() : [];
        return view('notifications.index',compact('items','trash','requests'));
    }

    public function preference(Request $request)
    {
        $request->user()->forceFill(['avisos_autolectura'=>$request->boolean('auto')])->save();
        return to_route('notifications.index',['conservar'=>1]);
    }

    public function update(Request $request, Inbox $inbox, int $id)
    {
        $action = $request->validate(['accion'=>'required|in:leer,pendiente,eliminar,restaurar'])['accion'];
        $row = $inbox->mine($request->user())->where('id',$id);
        abort_unless($row->exists(),404);
        $row->update(match($action) {
            'eliminar'=>['eliminado'=>now()], 'restaurar'=>['eliminado'=>null],
            'leer'=>['leido'=>now()], 'pendiente'=>['leido'=>null],
        });
        return to_route('notifications.index',['conservar'=>1]);
    }

    public function requestReturn(OperationRequest $request, Inbox $inbox)
    {
        $data = $request->validated();
        DB::transaction(function () use ($request,$data,$inbox) {
            $sale = $data['id_venta_original'];
            $original = DB::table('ventas')->where('id_venta',$sale)->firstOrFail();
            abort_if(now()->gt(\Carbon\Carbon::parse($original->fecha_venta)->addDays(2)),422,'Plazo de cambio vencido.');
            foreach ($data['productos'] as $line) {
                abort_unless(DB::table('detalle_ventas')->where('id_venta',$sale)->where('id_detalle_venta',$line['id_detalle_venta'])->where('cantidad','>=',$line['cantidad'])->exists(),422);
            }
            $user = $request->user();
            $added = DB::table('solicitudes_devolucion')->insertOrIgnore(['usuario'=>$user->getKey(),'clave'=>$data['idempotency_key'],'venta'=>$sale,'datos'=>json_encode($data,JSON_THROW_ON_ERROR),'created_at'=>now(),'updated_at'=>now()]);
            if ($added) $inbox->admins('sol-'.$user->getKey().'-'.$data['idempotency_key'],'Cambio o saldo por revisar',$user->nombre_completo.' solicita revisar la venta #'.$sale.'.');
        });
        return back()->with('status','Solicitud enviada al administrador.');
    }

    public function decide(Request $request, int $id, ReturnCredit $returns, Inbox $inbox)
    {
        abort_unless($request->user()->isAdministrator(),403);
        $decision = $request->validate(['decision'=>'required|in:aprobada,rechazada'])['decision'];
        DB::transaction(function () use ($decision,$id,$returns,$request,$inbox) {
            $row = DB::table('solicitudes_devolucion')->where('id',$id)->lockForUpdate()->firstOrFail();
            abort_unless($row->estado === 'pendiente',409);
            $admin = $request->user()->getKey();
            if ($decision === 'aprobada') {
                $data = json_decode($row->datos,true,512,JSON_THROW_ON_ERROR);
                $data['idempotency_key'] = 'solicitud-devolucion-'.str_pad((string)$id,12,'0',STR_PAD_LEFT);
                if (($data['destino'] ?? 'saldo') === 'cambio') {
                    app(\App\Services\Sales\ProductExchange::class)->exchange($row->usuario,$data);
                } else {
                    $returns->refund($row->usuario,$data);
                }
            }
            DB::table('solicitudes_devolucion')->where('id',$id)->update(['estado'=>$decision,'revisor'=>$admin,'updated_at'=>now()]);
            $inbox->send($row->usuario,'decision-'.$id,'Solicitud '.$decision,'Venta #'.$row->venta,route('sales.ticket',$row->venta));
        });
        return to_route('notifications.index')->with('status','Solicitud '.$decision.'.');
    }
}
