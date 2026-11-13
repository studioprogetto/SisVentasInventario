<?php
// Compatibility entry point. All HTTP handling now belongs to Laravel.
if (is_file(__DIR__.'/../laravel/storage/framework/deploying')) {
    http_response_code(503);
    header('Retry-After: 60');
    exit('Actualización del sistema en curso. Vuelve a intentarlo en un momento.');
}
$path = trim((string)($_GET['url'] ?? 'home'), '/');
if (!preg_match('#^[a-zA-Z0-9_/-]*$#D', $path)) {
    http_response_code(404);
    exit;
}
$path = preg_replace('#/index$#', '', $path);
$query = $_GET;
unset($query['url']);
$catalogConfig=require __DIR__.'/../config/public-catalog.php';
$target = $catalogConfig['admin_path'].'/'.($path ?: 'home');
if ($query) { $target .= '?'.http_build_query($query); }
header('Location: '.$target, true, in_array($_SERVER['REQUEST_METHOD'], ['GET','HEAD'],true) ? 302 : 307);
exit;
