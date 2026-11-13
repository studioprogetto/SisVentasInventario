<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/database.php';
$result = [];
$result['recent_sale_snapshots'] = $conexion->query('SELECT id_venta,fecha_venta,id_moneda FROM ventas ORDER BY id_venta DESC LIMIT 3')->fetch_all(MYSQLI_ASSOC);
$result['recent_native_operations'] = $conexion->query('SELECT accion,created_at FROM auditoria_operaciones ORDER BY id DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
