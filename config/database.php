<?php

// --- CONFIGURACIÓN GLOBAL DE LA APLICACIÓN ---
$config = [
    'moneda' => 'S/',
    'nombre_tienda' => 'Studio & Progetto',
    'ruc' => '10750263157',
    'telefono' => '+51 939275406',
    'email' => 'studioprogettosac@gmail.com',
    'direccion' => ' Calle Las Gemas 626 Urb. Santa Inés 
                    Trujillo - La Libertad',
    'horario_atencion' => 'Lun - Vie: 8:00-20:30   
                             Sáb: 8:00-20:30'
];

function getConfig($key) {
    global $config;
    return $config[$key] ?? null;
}

function getMoneda() {
    if (isset($_SESSION['moneda_usuario']) && !empty($_SESSION['moneda_usuario'])) {
        return $_SESSION['moneda_usuario'];
    }
    return getConfig('moneda');
}
// --- FIN DE LA CONFIGURACIÓN GLOBAL ---


// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
define('DB_HOST', 'localhost'); // El puerto :3306 es el nativo, se puede omitir si es el estándar
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tienda_sistema');

// Habilitar que mysqli lance excepciones en lugar de simples advertencias
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Intentamos establecer la conexión
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conexion->set_charset("utf8");

} catch (mysqli_sql_exception $e) {
    // 1. Registrar el error en el archivo log interno del servidor (silencioso para el cliente)
    error_log("Fallo crítico en Base de Datos: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
    
    // 2. Enviar un código de estado HTTP 503 (Servicio No Disponible)
    http_response_code(503);
    
    // 3. Mostrar una interfaz limpia y controlada al usuario en lugar de romper el sistema
    mostrarPantallaMantenimiento();
    exit();
}


// --- FUNCIONES DE CONTROL / UTILIDADES ---

function redirigir($url) {
    header("Location: " . $url);
    exit();
}

function tienePermiso($permiso) {
    if (isset($_SESSION['permisos']) && in_array($permiso, $_SESSION['permisos'])) {
        return true;
    }
    return false;
}

/**
 * Renderiza una vista amigable de contingencia si la BD no responde.
 */
function mostrarPantallaMantenimiento() {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mantenimiento Técnico - Studio & Progetto</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; text-align: center; padding: 50px; }
            .container { max-width: 600px; margin: 80px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            h1 { color: #e74c3c; font-size: 28px; margin-bottom: 10px; }
            p { color: #666; font-size: 16px; line-height: 1.6; }
            .logo { font-weight: bold; font-size: 22px; color: #2c3e50; margin-bottom: 20px; }
            .badge { background: #fdf2f2; color: #ec5b5b; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">Studio & Progetto</div>
            <h1>Optimización en curso</h1>
            <p>Estamos realizando mejoras intermitentes en nuestros servidores de datos para ofrecerte una experiencia más rápida y segura.</p>
            <p>Por favor, recarga la página o vuelve a intentarlo en unos minutos.</p>
            <span class="badge">Código de estado: HTTP 503 Backend Link Offline</span>
        </div>
    </body>
    </html>
    <?php
}