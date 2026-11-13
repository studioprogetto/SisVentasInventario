<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/database.php';
if(!preg_match('/^[a-zA-Z0-9_]+$/D',DB_NAME)){throw new LogicException('Unsupported database identifier.');}
$file=dirname(__DIR__).'/config/catalog-local.php';
if(is_file($file)){echo "Public reader already configured; credentials not changed.\n";exit;}
$user='studio_web_'.bin2hex(random_bytes(3));
$password=bin2hex(random_bytes(24));
$conexion->query("CREATE USER '$user'@'localhost' IDENTIFIED BY '$password'");
foreach(['catalogo_productos','catalogo_categorias','catalogo_populares'] as $view){$conexion->query('GRANT SELECT ON `'.DB_NAME.'`.`'.$view."` TO '$user'@'localhost'");}
file_put_contents($file,"<?php\nreturn ".var_export(['HOST'=>DB_HOST,'USER'=>$user,'PASS'=>$password,'NAME'=>DB_NAME],true).";\n");
echo "Read-only public account created; limited to three whitelisted views. Credentials stored privately.\n";
