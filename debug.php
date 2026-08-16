<?php
// debug.php - Para probar directamente
session_start();
require_once __DIR__ . '/config/database.php';

// Probar recibir compra directamente
$id_compra = 1; // Cambiar por el ID que quieras probar

try {
    $compraModel = new Compra($conexion);
    $resultado = $compraModel->recibirCompra($id_compra);
    
    if ($resultado) {
        echo "✅ Compra #$id_compra marcada como recibida exitosamente";
    } else {
        echo "❌ Error al recibir compra #$id_compra";
    }
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage();
    echo "<br>TRACE: " . $e->getTraceAsString();
}

// Ver logs
echo "<br><br>=== ÚLTIMOS LOGS ===";
$log_file = __DIR__ . '/error.log';
if (file_exists($log_file)) {
    $logs = tailCustom($log_file, 20);
    echo "<pre>" . htmlspecialchars($logs) . "</pre>";
}

function tailCustom($filepath, $lines = 1) {
    // Función para leer las últimas líneas del log
    $f = fopen($filepath, "rb");
    fseek($f, -1, SEEK_END);
    $buffer = "";
    $chunk = "";
    
    while ($lines > 0) {
        $seek = min(ftell($f), 4096);
        fseek($f, -$seek, SEEK_CUR);
        $chunk = fread($f, $seek);
        $buffer = $chunk . $buffer;
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        $lines -= substr_count($chunk, "\n");
    }
    
    fclose($f);
    return $buffer;
}