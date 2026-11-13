<?php
$public = dirname(__DIR__).'/laravel/public';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($public.$path)) { return false; }
require $public.'/index.php';
