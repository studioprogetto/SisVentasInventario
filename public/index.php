<?php
// Iniciar la sesión aquí, como la primera acción de toda la aplicación.
session_start();

// 🔹 CORREGIR: Definir BASE_URL correctamente
$base_path = '/ProyectoWeb/mi_sistema_mvc/public/';
define('BASE_URL', $base_path);

// Cargar la configuración y la base de datos
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

// --- Router Básico ---
$url = $_GET['url'] ?? 'home/index';
$url = rtrim($url, '/');
$url = explode('/', $url);

// 🔹 CORREGIR: Manejo de rutas para cambios/devoluciones
$rutaCompleta = implode('/', $url);

// 🔹 NUEVAS RUTAS PARA REPORTES ADICIONALES
if ($rutaCompleta === 'reporte/metodos-pago') {
    $controllerName = 'ReporteController';
    $methodName = 'metodosPago';
    $params = [];
} elseif ($rutaCompleta === 'reporte/top-productos') {
    $controllerName = 'ReporteController';
    $methodName = 'topProductos';
    $params = [];
} elseif ($rutaCompleta === 'reporte/rentabilidad') {
    $controllerName = 'ReporteController';
    $methodName = 'rentabilidad';
    $params = [];
}
// NUEVAS RUTAS PARA BÚSQUEDA DE PRODUCTOS
elseif ($rutaCompleta === 'producto/buscarSugerencias') {
    $controllerName = 'ProductoController';
    $methodName = 'buscarSugerencias';
    $params = [];
} elseif ($rutaCompleta === 'producto/buscarAvanzado') {
    $controllerName = 'ProductoController';
    $methodName = 'buscarAvanzado';
    $params = [];
}
// Rutas existentes para cambios y devoluciones...
elseif (strpos($rutaCompleta, 'venta/obtenerDetallesVenta/') === 0) {
    $controllerName = 'VentaController';
    $methodName = 'obtenerDetallesVenta';
    $params = [str_replace('venta/obtenerDetallesVenta/', '', $rutaCompleta)];
} elseif ($rutaCompleta === 'venta/procesarCambio') {
    $controllerName = 'VentaController';
    $methodName = 'procesarCambio';
    $params = [];
} elseif ($rutaCompleta === 'venta/procesarDevolucion') {
    $controllerName = 'VentaController';
    $methodName = 'procesarDevolucion';
    $params = [];
} elseif (strpos($rutaCompleta, 'venta/historialCambiosCliente/') === 0) {
    $controllerName = 'VentaController';
    $methodName = 'historialCambiosCliente';
    $params = [str_replace('venta/historialCambiosCliente/', '', $rutaCompleta)];
}
// 🔹 NUEVA RUTA: Para datos actualizados del dashboard
elseif ($rutaCompleta === 'home/obtenerDatosActualizados') {
    $controllerName = 'HomeController';
    $methodName = 'obtenerDatosActualizados';
    $params = [];
}
// Rutas para DevolucionesController
elseif ($rutaCompleta === 'devoluciones') {
    $controllerName = 'DevolucionesController';
    $methodName = 'index';
    $params = [];
} elseif ($rutaCompleta === 'devoluciones/buscarVentas') {
    $controllerName = 'DevolucionesController';
    $methodName = 'buscarVentas';
    $params = [];
} elseif (strpos($rutaCompleta, 'devoluciones/historialCompletoVenta/') === 0) {
    $controllerName = 'DevolucionesController';
    $methodName = 'historialCompletoVenta';
    $params = [str_replace('devoluciones/historialCompletoVenta/', '', $rutaCompleta)];
} elseif (strpos($rutaCompleta, 'devoluciones/historialVenta/') === 0) {
    $controllerName = 'DevolucionesController';
    $methodName = 'historialVenta';
    $params = [str_replace('devoluciones/historialVenta/', '', $rutaCompleta)];
} elseif ($rutaCompleta === 'caja/actualizarDatosTurnos') {
    $controllerName = 'CajaController';
    $methodName = 'actualizarDatosTurnos';
    $params = [];
}
// Agrega esta nueva ruta antes de las rutas normales
elseif ($rutaCompleta === 'producto/generarEtiquetas') {
    $controllerName = 'ProductoController';
    $methodName = 'generarEtiquetas';
    $params = [];
}
// Agrega también la ruta para pruebas si la necesitas
elseif ($rutaCompleta === 'producto/generarEtiquetasPrueba') {
    $controllerName = 'ProductoController';
    $methodName = 'generarEtiquetasPrueba';
    $params = [];
}
// Rutas normales (tu código existente)
else {
    $controllerName = ucfirst($url[0] ?? 'Home') . 'Controller';
    $methodName = $url[1] ?? 'index';
    $params = array_slice($url, 2);
}

$controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;

    if (class_exists($controllerName)) {
        $controller = new $controllerName;
        if (method_exists($controller, $methodName)) {
            call_user_func_array([$controller, $methodName], $params);
        } else {
            http_response_code(404);
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
    } else {
        http_response_code(404);
        header('Location: ' . BASE_URL . 'auth/login');
        exit;
    }
} else {
    http_response_code(404);
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}
