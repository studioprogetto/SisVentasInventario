<?php

namespace App\Http\Controllers\Masters;

use App\Services\Operations\AuditTrail;
use App\Services\Operations\Idempotency;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientBalanceController
{
    public function __invoke(Request $request, int $client, Idempotency $operations, AuditTrail $audit)
    {
        $data = $request->validate([
            'tipo'=>'required|in:favor,deuda', 'monto'=>'required|numeric|decimal:0,2|min:0.01|max:99999999',
            'motivo'=>'required|string|max:255','idempotency_key'=>'required|string|max:64',
        ]);
        $actor = $request->user()->getKey();
        $operations->execute($actor,'saldo_manual',$data['idempotency_key'],$data+['cliente'=>$client],function () use ($actor,$client,$data,$audit) {
            DB::table('clientes')->where(['id_cliente'=>$client,'activo'=>1])->lockForUpdate()->firstOrFail();
            $currency = DB::table('tipo_moneda')->where(['codigo'=>'PEN','activo'=>1])->firstOrFail();
            $amount = Money::cents($data['monto']) * ($data['tipo'] === 'favor' ? 1 : -1);
            $id = DB::table('saldos_clientes')->insertGetId(['id_cliente'=>$client,'id_moneda'=>$currency->id,
                'estado'=>'pendiente','saldo'=>Money::decimal($amount)],'id_saldo');
            $audit->record($actor,'saldo_manual','saldos_clientes',$id,$data);
            return ['id'=>$id];
        });
        return back()->with('status','Saldo del cliente registrado.');
    }
}
