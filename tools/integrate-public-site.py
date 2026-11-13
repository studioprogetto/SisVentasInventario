from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]
site=root.parent
p=site/'index.php'
source=p.read_text(encoding='utf-8-sig')
body=source[source.index('<!DOCTYPE'):]
head='''<?php
require_once __DIR__.'/conexion.php';
$catalogConfig=require __DIR__.'/mi_sistema_mvc/config/public-catalog.php';
$productosMasComprados=$conexion->query('SELECT id_producto,nombre,precio_venta,slug FROM catalogo_populares')->fetch_all(MYSQLI_ASSOC);
$ultimosProductos=$conexion->query('SELECT id_producto,nombre,precio_venta,slug FROM catalogo_productos ORDER BY fecha_creacion DESC LIMIT 16')->fetch_all(MYSQLI_ASSOC);
$categoriasPublicadas=$conexion->query('SELECT id_categoria,nombre FROM catalogo_categorias ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
?>
'''
start=body.index('<ul class="sidebar-categories">')
end=body.index('</ul>',start)+5
dynamic='''<ul class="sidebar-categories">
<?php foreach($categoriasPublicadas as $categoria): ?>
<li><a href="productos.php?cat[]=<?= (int)$categoria['id_categoria'] ?>"><?= htmlspecialchars($categoria['nombre'],ENT_QUOTES,'UTF-8') ?></a></li>
<?php endforeach; ?>
<li><a href="productos.php">Todos los productos</a></li>
</ul>'''
body=body[:start]+dynamic+body[end:]
pattern=r'\$imgPath\s*=\s*"imagenes/sin_imagen\.jpg";\s*\$nombreArchivo\s*=.*?\}\s*(?=\?>)'
body,count=re.subn(pattern,"$imgPath = $catalogConfig['image_path'].(int)$producto['id_producto'];\n",body,flags=re.S)
if count!=2:raise SystemExit(f'Expected two product image blocks, found {count}; no files changed')
body=body.replace('https://tusitio.com/imagenes/logosinfondo.png',"<?= htmlspecialchars($catalogConfig['site_url'].'/imagenes/logosinfondo.png',ENT_QUOTES,'UTF-8') ?>")
body=body.replace('https://tusitio.com',"<?= htmlspecialchars($catalogConfig['site_url'],ENT_QUOTES,'UTF-8') ?>")
p.write_text(head+body,encoding='utf-8')
(site/'productos.php').write_text('''<?php
$config=require __DIR__.'/mi_sistema_mvc/config/public-catalog.php';
$query=[];
foreach(['q'=>'q','min_price'=>'minimo','max_price'=>'maximo'] as $old=>$new){if(isset($_GET[$old])&&is_scalar($_GET[$old])){$query[$new]=$_GET[$old];}}
if(!empty($_GET['cat'])){$ids=array_filter(array_map('intval',(array)$_GET['cat']),fn($id)=>$id>0);if($ids){$query['categoria']=reset($ids);}}
if(isset($_GET['order'])&&is_string($_GET['order'])){$query['orden']=match($_GET['order']){'price-asc'=>'precio_asc','price-desc'=>'precio_desc',default=>'nombre'};}
header('Location: '.$config['path'].($query?'?'.http_build_query($query):''),true,302);
exit;
''',encoding='utf-8')
(site/'conexion.php').write_text('''<?php
$connection=require __DIR__.'/mi_sistema_mvc/config/catalog-local.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try {
    $conexion=new mysqli($connection['HOST'],$connection['USER'],$connection['PASS'],$connection['NAME']);
    $conexion->set_charset('utf8mb4');
    $conexion->query("SET time_zone='-05:00'");
} catch(mysqli_sql_exception $exception) {
    error_log('Public catalog database unavailable.');
    http_response_code(503);
    exit('El catálogo no está disponible temporalmente. Intenta nuevamente en un momento.');
}
''',encoding='utf-8')
print('Existing homepage preserved; categories and images now use published data. Catalog URL redirected; public connection restricted to views.')
