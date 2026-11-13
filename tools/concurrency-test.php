<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/database.php';
$config=json_decode(file_get_contents(dirname(__DIR__).'/docs/validation/test-server.json'),true,flags:JSON_THROW_ON_ERROR);
$name=$config['database'];
if(!preg_match('/^studio_browser_[0-9_]+_test$/D',$name)) { throw new LogicException('An isolated browser database is required.'); }
$conexion->select_db($name);
$env=getenv();
foreach(['APP_ENV'=>'testing','DB_CONNECTION'=>'mariadb','DB_HOST'=>DB_HOST,'DB_PORT'=>'3306','DB_DATABASE'=>$name,'DB_USERNAME'=>DB_USER,'DB_PASSWORD'=>DB_PASS] as $key=>$value) {$env[$key]=$value;}
$role=(int)$conexion->query('SELECT id_rol FROM roles LIMIT 1')->fetch_row()[0];
$warehouse=(int)$conexion->query('SELECT id FROM almacenes LIMIT 1')->fetch_row()[0];
$category=(int)$conexion->query('SELECT id_categoria FROM categorias LIMIT 1')->fetch_row()[0];
$users=[];
foreach([1,2] as $number){
    $username='race-'.bin2hex(random_bytes(5));
    $stmt=$conexion->prepare('INSERT INTO usuarios(nombre_completo,username,password,id_rol) VALUES(?,?,?,?)');
    $display='Prueba concurrente'; $password=password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT);
    $stmt->bind_param('sssi',$display,$username,$password,$role); $stmt->execute();
    $users[]=$user=$conexion->insert_id;
    $conexion->query("INSERT INTO turnos_caja(id_usuario,monto_inicial,estado) VALUES($user,0,'abierto')");
}
$conexion->query("INSERT INTO productos(nombre,id_categoria,id_almacen,precio_compra,precio_venta,stock,publicado) VALUES('Prueba de última unidad',$category,$warehouse,1,2,1,0)");
$product=$conexion->insert_id;
$conexion->query("INSERT INTO existencias(id_producto,id_almacen,stock,saldo_inicial,created_at) VALUES($product,$warehouse,1,1,NOW())");
$processes=[];
foreach($users as $user){
    $key='race-'.bin2hex(random_bytes(16));
    $process=proc_open([PHP_BINARY,__DIR__.'/concurrency-worker.php',(string)$user,(string)$product,(string)$warehouse,$key],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__).'/laravel',$env);
    $processes[]=[$process,$pipes];
}
$accepted=0;
foreach($processes as [$process,$pipes]){
    fclose($pipes[0]); $output=stream_get_contents($pipes[1]); $errors=stream_get_contents($pipes[2]); fclose($pipes[1]);fclose($pipes[2]);
    if(proc_close($process)!==0){throw new RuntimeException($errors.$output);}
    $result=json_decode($output,true,flags:JSON_THROW_ON_ERROR);$accepted+=(int)$result['accepted'];
}
$stock=(int)$conexion->query("SELECT stock FROM productos WHERE id_producto=$product")->fetch_row()[0];
$sold=(int)$conexion->query("SELECT COALESCE(SUM(cantidad),0) FROM detalle_ventas WHERE id_producto=$product")->fetch_row()[0];
$result=['workers'=>2,'accepted'=>$accepted,'rejected'=>2-$accepted,'stock'=>$stock,'units_sold'=>$sold,'passed'=>$accepted===1&&$stock===0&&$sold===1];
function race(array $users,int $reference,int $warehouse,string $action,array $env,bool $sameKey=false): array {
    $processes=[];$key='race-'.bin2hex(random_bytes(16));
    foreach($users as $user){
        $process=proc_open([PHP_BINARY,__DIR__.'/concurrency-worker.php',(string)$user,(string)$reference,(string)$warehouse,$sameKey?$key:'race-'.bin2hex(random_bytes(16)),$action],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__).'/laravel',$env);
        $processes[]=[$process,$pipes];
    }
    $results=[];
    foreach($processes as [$process,$pipes]){fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0){throw new RuntimeException($out.$error);}$results[]=json_decode($out,true,flags:JSON_THROW_ON_ERROR);}
    return $results;
}
$cases=['last_unit'=>$result['passed']];
$supplier=(int)$conexion->query('SELECT id_proveedor FROM proveedores LIMIT 1')->fetch_row()[0];
$currency=(int)$conexion->query('SELECT id FROM tipo_moneda WHERE principal=1 LIMIT 1')->fetch_row()[0];
$owner=$users[0];
$conexion->query("INSERT INTO compras(id_proveedor,id_almacen,id_usuario,total_compra,estado,id_moneda,moneda_codigo,tipo_cambio_aplicado) VALUES($supplier,$warehouse,$owner,3,'solicitada',$currency,'PEN',1)");
$purchase=$conexion->insert_id;
$conexion->query("INSERT INTO detalle_compras(id_compra,id_producto,cantidad,costo_unitario) VALUES($purchase,$product,3,1)");
$r=race($users,$purchase,$warehouse,'receive',$env);
$cases['single_receipt']=array_sum(array_column($r,'accepted'))===1 && (int)$conexion->query("SELECT stock FROM productos WHERE id_producto=$product")->fetch_row()[0]===3;
$destination=(int)$conexion->query("SELECT id FROM almacenes WHERE id<>$warehouse LIMIT 1")->fetch_row()[0];
$conexion->query("INSERT INTO transferencias(id_usuario,origen,destino,estado,created_at) VALUES($owner,$warehouse,$destination,'pendiente',NOW())");
$transfer=$conexion->insert_id;
$conexion->query("INSERT INTO detalle_transferencias(id_transferencia,id_producto,cantidad) VALUES($transfer,$product,2)");
$r=race($users,$transfer,$warehouse,'send',$env);
$cases['single_dispatch']=array_sum(array_column($r,'accepted'))===1 && (int)$conexion->query("SELECT stock FROM productos WHERE id_producto=$product")->fetch_row()[0]===1;
$r=race($users,$transfer,$warehouse,'receive-transfer',$env);
$cases['single_transfer_receipt']=array_sum(array_column($r,'accepted'))===1 && (int)$conexion->query("SELECT stock FROM productos WHERE id_producto=$product")->fetch_row()[0]===3;
$r=race([$owner,$owner],0,$warehouse,'close',$env);
$cases['single_cash_close']=array_sum(array_column($r,'accepted'))===1;
$r=race([$owner,$owner],0,$warehouse,'open',$env);
$cases['single_cash_open']=array_sum(array_column($r,'accepted'))===1 && (int)$conexion->query("SELECT COUNT(*) FROM turnos_caja WHERE id_usuario=$owner AND estado='abierto'")->fetch_row()[0]===1;
$r=race([$owner,$owner],$product,$destination,'sale',$env,true);
$cases['idempotent_concurrent_sale']=array_sum(array_column($r,'accepted'))===2 && $r[0]['id']===$r[1]['id'];
$result['cases']=$cases;$result['passed']=!in_array(false,$cases,true);
file_put_contents(dirname(__DIR__).'/docs/validation/concurrency.json',json_encode($result,JSON_PRETTY_PRINT));
echo json_encode($result).PHP_EOL;
exit($result['passed']?0:1);
