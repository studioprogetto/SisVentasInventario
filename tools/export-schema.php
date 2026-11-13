<?php

declare(strict_types=1);

// Read-only baseline: exports metadata, never customer rows or credentials.
require dirname(__DIR__).'/config/database.php';
$tables = $conexion->query('SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME')->fetch_all(MYSQLI_ASSOC);
$schema = [];
foreach ($tables as $table) {
    $name = $table['TABLE_NAME'];
    $statement = $conexion->prepare('SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
    $statement->bind_param('s', $name);
    $statement->execute();
    $columns = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $ddl = $conexion->query('SHOW CREATE TABLE `'.str_replace('`', '``', $name).'`')->fetch_row()[1];
    $schema[$name] = ['columns' => $columns, 'ddl' => $ddl];
}
$current=in_array('--current',$argv,true);
$directory = dirname(__DIR__).($current?'/docs/architecture':'/laravel/database/schema');
$filename=$current?'schema-current.json':'legacy-schema.json';
if(!$current && is_file($directory.'/'.$filename)){throw new LogicException('The legacy test baseline is immutable; use --current for a new snapshot.');}
if (!is_dir($directory)) {
    mkdir($directory, 0755, true);
}
file_put_contents($directory.'/'.$filename, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
echo count($schema)." table definitions exported; no business data.\n";
