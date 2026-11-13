<?php

declare(strict_types=1);

// Creates only an empty, uniquely named test database. No business data copied.
require dirname(__DIR__).'/config/database.php';
$name = 'studio_phase0_'.gmdate('Ymd_His').'_'.bin2hex(random_bytes(3)).'_test';
$conexion->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
$variables = getenv();
$variables['APP_ENV'] = 'testing';
$variables['APP_URL'] = 'http://localhost';
$variables['DB_CONNECTION'] = 'mariadb';
$variables['DB_HOST'] = DB_HOST;
$variables['DB_PORT'] = '3306';
$variables['DB_DATABASE'] = $name;
$variables['DB_USERNAME'] = DB_USER;
$variables['DB_PASSWORD'] = DB_PASS;
$variables['EXPECTED_DB_DRIVER'] = 'mariadb';
$root = dirname(__DIR__).'/laravel';
$command = [PHP_BINARY, $root.'/vendor/phpunit/phpunit/phpunit', '--testdox', '--log-junit', dirname(__DIR__).'/docs/phase-0/tests-mariadb.xml'];
$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root, $variables);
if (!is_resource($process)) {
    throw new RuntimeException('Unable to start isolated test process.');
}
$status = proc_close($process);
echo 'Isolated database retained for inspection: '.$name.PHP_EOL;
exit($status);
