<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$connection=require $root.'/config/connection.php';
$test=json_decode(file_get_contents($root.'/docs/validation/test-server.json'),true,flags:JSON_THROW_ON_ERROR);
if(!preg_match('/^studio_browser_[0-9_]+_test$/D',$test['database'])){throw new LogicException('Not an isolated browser database.');}
foreach(['APP_ENV'=>'testing','DB_CONNECTION'=>'mariadb','DB_HOST'=>$connection['HOST'],'DB_DATABASE'=>$test['database'],'DB_USERNAME'=>$connection['USER'],'DB_PASSWORD'=>$connection['PASS']] as $key=>$value){putenv($key.'='.$value);$_ENV[$key]=$value;$_SERVER[$key]=$value;}
require $root.'/laravel/vendor/autoload.php';
$app=require $root.'/laravel/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
exit(Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]));
