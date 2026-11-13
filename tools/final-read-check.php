<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/database.php';
$result=['business_counts'=>[]];
foreach(['productos','ventas','compras','usuarios','clientes'] as $table){$result['business_counts'][$table]=(int)$conexion->query('SELECT COUNT(*) FROM `'.$table.'`')->fetch_row()[0];}
$result['stock_mismatches']=(int)$conexion->query('SELECT COUNT(*) FROM productos p LEFT JOIN (SELECT id_producto,SUM(stock) stock FROM existencias GROUP BY id_producto) e ON e.id_producto=p.id_producto WHERE e.stock IS NULL OR e.stock<>p.stock')->fetch_row()[0];
$result['negative_stock']=(int)$conexion->query('SELECT COUNT(*) FROM existencias WHERE stock<0')->fetch_row()[0];
$result['legacy_missing_currency']=(int)$conexion->query('SELECT COUNT(*) FROM ventas WHERE id_moneda IS NULL')->fetch_row()[0];
$result['legacy_unreconciled_credits']=(int)$conexion->query("SELECT COUNT(*) FROM saldos_clientes WHERE estado='' OR id_moneda IS NULL")->fetch_row()[0];
$public=require dirname(__DIR__).'/config/catalog-local.php';
$reader=new mysqli($public['HOST'],$public['USER'],$public['PASS'],$public['NAME']);
$reader->query('SELECT id_producto,nombre,precio_venta FROM catalogo_productos LIMIT 0');
foreach(['SELECT id_usuario FROM usuarios LIMIT 0','UPDATE productos SET stock=stock WHERE 1=0'] as $index=>$sql){
    try{$reader->query($sql);$result['public_reader_denied_'.$index]=false;}catch(mysqli_sql_exception){$result['public_reader_denied_'.$index]=true;}
}
$result['passed']=$result['stock_mismatches']===0 && $result['negative_stock']===0 && $result['public_reader_denied_0'] && $result['public_reader_denied_1'];
file_put_contents(dirname(__DIR__).'/docs/validation/live-read-check.json',json_encode($result,JSON_PRETTY_PRINT));
echo json_encode(['passed'=>$result['passed'],'stock_mismatches'=>$result['stock_mismatches'],'public_reader_restricted'=>$result['public_reader_denied_0']&&$result['public_reader_denied_1'],'legacy_missing_currency'=>$result['legacy_missing_currency'],'legacy_unreconciled_credits'=>$result['legacy_unreconciled_credits']]).PHP_EOL;
exit($result['passed']?0:1);
