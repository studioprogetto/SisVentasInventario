<?php

// Origin restricted to the receipt portal. Run with public/ as document root.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    return;
}

$path = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
$method = $_SERVER['REQUEST_METHOD'];
$read = in_array($method, ['GET', 'HEAD'], true);
$assets = [
    '/css/tickets.css', '/css/app.css', '/css/receipt.css', '/css/admin-records.css',
    '/css/responsive.css', '/css/lectura.css', '/css/scrollbars.css',
    '/js/tickets.js', '/js/vendor/qrcode.js',
];
if ($read && in_array($path, $assets, true)) {
    return false;
}
$claim = preg_match('~^/ticket/acceso/[0-9a-f]{64}$~D', $path);
$public = preg_match('~^/(?:brand/logo|ticket/(?:portal|recibido(?:/pdf)?|documento/[0-9]+|pantalla/[0-9a-f]{64}(?:/estado)?))$~D', $path);
if (!(($read && ($public || $claim)) || ($method === 'POST' && $claim))) {
    http_response_code(404);
    return;
}

$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__).'/public/index.php';
$_SERVER['APP_DEBUG'] = 'false';
$_SERVER['SESSION_COOKIE'] = 'studio_ticket_public';
$_SERVER['SESSION_SECURE_COOKIE'] = 'true';
require $_SERVER['SCRIPT_FILENAME'];
