<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$connection = require $root.'/config/connection.php';
$settings = json_decode(file_get_contents($root.'/docs/validation/test-server.json'), true, flags: JSON_THROW_ON_ERROR);
if (! preg_match('/^studio_browser_[0-9_]+_test$/D', $settings['database'])) {
    throw new LogicException('Browser test database required.');
}
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mariadb', 'DB_HOST' => $connection['HOST'], 'DB_DATABASE' => $settings['database'], 'DB_USERNAME' => $connection['USER'], 'DB_PASSWORD' => $connection['PASS']] as $key => $value) {
    putenv($key.'='.$value); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require $root.'/laravel/vendor/autoload.php';
$app = require $root.'/laravel/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Support\Database\TestDatabaseGuard::assertIsolated();
Illuminate\Support\Facades\DB::table('clientes')->where('nombre_cliente', 'Cliente de prueba')->update(['documento_identidad' => '72709806', 'sellos' => 5, 'total_compras' => 5]);
echo 'Synthetic loyalty fixture prepared only in isolated database.'.PHP_EOL;
