<?php

function debug_log($data) {
    $logFile = __DIR__ . 'mi_sistema_mvc/debug.txt';
    $date = date('Y-m-d H:i:s');

    if (is_array($data) || is_object($data)) {
        $data = json_encode($data, JSON_PRETTY_PRINT);
    }

    file_put_contents($logFile, "[$date] $data" . PHP_EOL, FILE_APPEND);
}
