<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar permisos
if (!function_exists('tienePermiso')) {
    function tienePermiso($permiso)
    {
        return in_array($permiso, $_SESSION['permisos'] ?? []);
    }
}

require_once __DIR__ . '/../../../config/database.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Inventario y Ventas</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Estilos propios (con versión dinámica para evitar caché) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css?v=<?php echo time(); ?>">
</head>

<body>

    <?php if (!empty($_SESSION['id_usuario'])): ?>
        <!-- Botón toggle sidebar -->
        <button class="btn sidebar-toggler" type="button" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <a class="navbar-brand text-center" href="<?php echo BASE_URL; ?>home">
                <?php
                // Ruta del archivo en el servidor
                $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/libreria/SisVentasInventario/public/img/logo.png';
                // URL accesible desde el navegador
                $logoUrl = BASE_URL . 'img/logo.png?v=' . time();

                if (file_exists($logoPath)):
                ?>
                    <img src="<?php echo $logoUrl; ?>" alt="Logo" class="img-fluid" style="max-height: 40px;">
                <?php else: ?>
                    <i class="fas fa-store"></i>
                    <?php echo htmlspecialchars(getConfig('nombre_tienda') ?? 'Studio & Progetto'); ?>
                <?php endif; ?>
                <p class="m-0 small">STUDIO &amp; PROGETTO</p>
            </a>


            <!-- Menú de navegación -->
            <ul class="nav flex-column">
                <?php if (tienePermiso('dashboard_ver')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>home"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('ventas_crear')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>venta"><i class="fas fa-cash-register me-2"></i>Punto de Venta</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('ventas_ver_listado')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>venta/historial"><i class="fas fa-history me-2"></i>Historial de Ventas</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('productos_ver_lista')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>producto"><i class="fas fa-box-open me-2"></i>Inventario</a></li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>servicio">
                        <i class="fas fa-concierge-bell me-2"></i>Servicios
                    </a>
                </li>


                <?php if (tienePermiso('categorias_ver_lista')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>categoria"><i class="fas fa-tags me-2"></i>Categorías</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('almacenes_gestionar')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>almacen"><i class="fas fa-warehouse me-2"></i>Almacenes</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('transferencias_crear')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>transferencia"><i class="fas fa-people-carry me-2"></i>Transferencias</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('compras_ver_lista')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>compra"><i class="fas fa-truck me-2"></i>Compras</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('proveedores_ver_lista')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>proveedor"><i class="fas fa-truck-loading me-2"></i>Proveedores</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('clientes_ver_lista')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>cliente"><i class="fas fa-users me-2"></i>Clientes</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('caja_gestionar')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>caja"><i class="fas fa-calculator me-2"></i>Caja</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('reportes_ver_ventas') || tienePermiso('reportes_ver_inventario') || tienePermiso('reportes_ver_caja')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>reporte"><i class="fas fa-chart-line me-2"></i>Reportes</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('usuarios_ver_lista')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>usuario"><i class="fas fa-users-cog me-2"></i>Usuarios</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('tipocambio_ver_lista')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>tipocambio"><i class="fas fa-exchange-alt me-2"></i>Tipo de Cambio</a></li>
                <?php endif; ?>

                <?php if (tienePermiso('tipomoneda_gestionar')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>tipomoneda"><i class="fas fa-coins me-2"></i>Monedas</a></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Contenedor principal -->
    <div class="main-content d-flex flex-column min-vh-100">
        <?php if (!empty($_SESSION['id_usuario'])): ?>
            <header class="top-header d-flex justify-content-end align-items-center p-2">
                <div class="me-auto text-white fw-bold">
                    <span id="hora-local"></span>
                    <span id="avisohora" class="fw-bold ms-2"></span>
                </div>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>perfil">Mi Perfil</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/logout">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </header>
        <?php endif; ?>

        <!-- Aquí empieza el contenido dinámico -->
        <main class="flex-grow-1">