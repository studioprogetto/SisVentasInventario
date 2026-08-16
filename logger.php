<?php

function write_log($message, $type = "INFO") {

    // Carpeta y archivo donde se guardará el log
    $logDir = __DIR__ . "/logs";
    $logFile = $logDir . "/debug.log";

    // Crear carpeta si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    // Convertir arrays u objetos a JSON
    if (is_array($message) || is_object($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // Formato del registro
    $date = date("Y-m-d H:i:s");
    $line = "[$date] [$type] $message" . PHP_EOL;

    // Guardar en archivo
    file_put_contents($logFile, $line, FILE_APPEND);
}
