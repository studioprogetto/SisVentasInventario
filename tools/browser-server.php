<?php

declare(strict_types=1);

require dirname(__DIR__).'/config/database.php';
$name = 'studio_browser_'.gmdate('Ymd_His').'_test';
$conexion->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
$variables = getenv();
foreach (['APP_ENV'=>'testing','APP_URL'=>'http://127.0.0.1:8099','DB_CONNECTION'=>'mariadb','DB_HOST'=>DB_HOST,'DB_PORT'=>'3306','DB_DATABASE'=>$name,'DB_USERNAME'=>DB_USER,'DB_PASSWORD'=>DB_PASS,'APP_READ_ONLY'=>'false'] as $key=>$value) { $variables[$key]=$value; }
$root = dirname(__DIR__).'/laravel';
$process = proc_open([PHP_BINARY,$root.'/artisan','system:prepare-browser'],[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,$root,$variables);
if (!is_resource($process) || proc_close($process)!==0) { exit(1); }
$variables['APP_ENV']='local';
$process = proc_open([PHP_BINARY,'-S','127.0.0.1:8099','-t',$root.'/public',__DIR__.'/browser-router.php'],[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,$root.'/public',$variables);
if (!is_resource($process)) { exit(1); }
exit(proc_close($process));
