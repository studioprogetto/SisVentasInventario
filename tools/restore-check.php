<?php
declare(strict_types=1);
require dirname(__DIR__).'/laravel/vendor/autoload.php';
$app=require dirname(__DIR__).'/laravel/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$backup=app(App\Services\Backups\DatabaseBackup::class);
$name=$backup->create(); $path=$backup->path($name);
$connection=Illuminate\Support\Facades\DB::connection();
$source=$connection->getDatabaseName();
$test='studio_restore_'.gmdate('Ymd_His').'_'.bin2hex(random_bytes(3)).'_test';
$connection->statement('CREATE DATABASE `'.$test.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
$temporary=tempnam(sys_get_temp_dir(),'restore-check-');
try {
    $sql=file_get_contents($path);
    if(preg_match('/^\s*(?:USE |CREATE DATABASE)/mi',$sql)){throw new LogicException('Refusing a dump that changes database context.');}
    // Keep view definitions in the isolated schema as well.
    file_put_contents($temporary,str_replace('`'.$source.'`.','`'.$test.'`.',$sql));
    $configuration=config('database.connections.'.config('database.default'));
    $input=fopen($temporary,'rb');
    $mysql=str_replace('mysqldump.exe','mysql.exe',config('deployment.mysqldump'));
    $process=new Symfony\Component\Process\Process([$mysql,'--binary-mode','--host='.$configuration['host'],'--port='.$configuration['port'],'--user='.$configuration['username'],'--database='.$test],null,['MYSQL_PWD'=>$configuration['password']],$input,120);
    $process->mustRun();fclose($input);
    $counts=[];
    foreach(['productos','ventas','detalle_ventas','compras','usuarios','clientes','existencias'] as $table){
        $original=(int)$connection->selectOne('SELECT COUNT(*) as n FROM `'.$source.'`.`'.$table.'`')->n;
        $restored=(int)$connection->selectOne('SELECT COUNT(*) as n FROM `'.$test.'`.`'.$table.'`')->n;
        if($original!==$restored){throw new RuntimeException('Restore count mismatch: '.$table);}
        $counts[$table]=['source'=>$original,'restored'=>$restored];
    }
    $result=['passed'=>true,'backup'=>$name,'sha256'=>hash_file('sha256',$path),'counts'=>$counts,'temporary_database_removed'=>true];
    file_put_contents(dirname(__DIR__).'/docs/validation/restore.json',json_encode($result,JSON_PRETTY_PRINT));
    echo "Private backup restored successfully; seven table counts match. Temporary restored database removed.\n";
}finally{
    // The identifier is generated above, never read from input or a filesystem path.
    if(!preg_match('/^studio_restore_[0-9_]+_[a-f0-9]{6}_test$/D',$test)){throw new LogicException('Unsafe cleanup identifier.');}
    $connection->statement('DROP DATABASE `'.$test.'`');
    unlink($temporary);
}
