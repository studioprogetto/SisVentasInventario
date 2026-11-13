<?php
declare(strict_types=1);
require dirname(__DIR__).'/laravel/vendor/autoload.php';
$app=require dirname(__DIR__).'/laravel/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Support\Database\TestDatabaseGuard::assertIsolated();
$user=(int)$argv[1]; $product=(int)$argv[2]; $warehouse=(int)$argv[3]; $key=$argv[4];
try {
    $action=$argv[5]??'sale';
    $result=match($action){
        'receive'=>app(App\Services\Purchases\PurchaseReceipt::class)->receive($user,$product),
        'send'=>app(App\Services\Inventory\TransferWorkflow::class)->transition($user,$product,'enviada'),
        'receive-transfer'=>app(App\Services\Inventory\TransferWorkflow::class)->transition($user,$product,'recibida'),
        'close'=>app(App\Services\Cash\CashSession::class)->close($user,'0'),
        'open'=>app(App\Services\Cash\CashSession::class)->open($user,'0'),
        default=>app(App\Services\Sales\SaleCheckout::class)->checkout($user,['idempotency_key'=>$key,'metodo_pago'=>'efectivo','carrito'=>[['tipo'=>'producto','id'=>$product,'cantidad'=>1,'id_almacen'=>$warehouse]]]),
    };
    echo json_encode(['accepted'=>true,'id'=>$result['id_venta']??$result['id_turno']??null]);
} catch(Illuminate\Validation\ValidationException $exception) {
    echo json_encode(['accepted'=>false,'validation'=>true]);
}
