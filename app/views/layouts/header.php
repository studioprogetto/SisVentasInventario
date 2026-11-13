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
        <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

        <aside class="sidebar" id="sidebar" aria-label="Menú principal">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" type="button" id="sidebarCollapseBtn" aria-label="Contraer o expandir menú" aria-expanded="true" title="Contraer menú">
                    <i class="fas fa-bars"></i>
                </button>

                <a class="brand" href="<?php echo BASE_URL; ?>home" aria-label="Ir al inicio">
                    <?php
                    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/ProyectoWeb/mi_sistema_mvc/public/img/logo.png';
                    $logoUrl = BASE_URL . 'img/logo.png?v=' . time();

                    if (file_exists($logoPath)):
                    ?>
                        <img src="<?php echo $logoUrl; ?>" alt="Logo" class="brand-logo">
                    <?php else: ?>
                        <span class="brand-mark"><i class="fas fa-store"></i></span>
                    <?php endif; ?>
                    <span class="brand-text">STUDIO &amp; PROGETTO</span>
                </a>
            </div>

            <nav class="sidebar-nav" aria-label="Navegación principal">
                <ul class="nav flex-column">
                    <?php if (tienePermiso('dashboard_ver')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>home" title="Dashboard"><span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span><span class="nav-text">Dashboard</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('ventas_crear')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>venta" title="Punto de Venta"><span class="nav-icon"><i class="fas fa-cash-register"></i></span><span class="nav-text">Punto de Venta</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('ventas_ver_listado')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>venta/historial" title="Historial de Ventas"><span class="nav-icon"><i class="fas fa-history"></i></span><span class="nav-text">Historial de Ventas</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('productos_ver_lista')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>producto" title="Inventario"><span class="nav-icon"><i class="fas fa-box-open"></i></span><span class="nav-text">Inventario</span></a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>servicio" title="Servicios"><span class="nav-icon"><i class="fas fa-concierge-bell"></i></span><span class="nav-text">Servicios</span></a></li>

                    <?php if (tienePermiso('categorias_ver_lista')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>categoria" title="Categorías"><span class="nav-icon"><i class="fas fa-tags"></i></span><span class="nav-text">Categorías</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('almacenes_gestionar')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>almacen" title="Almacenes"><span class="nav-icon"><i class="fas fa-warehouse"></i></span><span class="nav-text">Almacenes</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('transferencias_crear')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>transferencia" title="Transferencias"><span class="nav-icon"><i class="fas fa-people-carry"></i></span><span class="nav-text">Transferencias</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('compras_ver_lista')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>compra" title="Compras"><span class="nav-icon"><i class="fas fa-truck"></i></span><span class="nav-text">Compras</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('proveedores_ver_lista')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>proveedor" title="Proveedores"><span class="nav-icon"><i class="fas fa-truck-loading"></i></span><span class="nav-text">Proveedores</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('clientes_ver_lista')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>cliente" title="Clientes"><span class="nav-icon"><i class="fas fa-users"></i></span><span class="nav-text">Clientes</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('caja_gestionar')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>caja" title="Caja"><span class="nav-icon"><i class="fas fa-calculator"></i></span><span class="nav-text">Caja</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('reportes_ver_ventas') || tienePermiso('reportes_ver_inventario') || tienePermiso('reportes_ver_caja')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>reporte" title="Reportes"><span class="nav-icon"><i class="fas fa-chart-line"></i></span><span class="nav-text">Reportes</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('usuarios_ver_lista')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>usuario" title="Usuarios"><span class="nav-icon"><i class="fas fa-users-cog"></i></span><span class="nav-text">Usuarios</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('tipocambio_ver_lista')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>tipocambio" title="Tipo de Cambio"><span class="nav-icon"><i class="fas fa-exchange-alt"></i></span><span class="nav-text">Tipo de Cambio</span></a></li>
                    <?php endif; ?>

                    <?php if (tienePermiso('tipomoneda_gestionar')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>tipomoneda" title="Monedas"><span class="nav-icon"><i class="fas fa-coins"></i></span><span class="nav-text">Monedas</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a class="nav-link footer-user" href="<?php echo BASE_URL; ?>perfil" title="Mi perfil">
                    <span class="nav-icon user-avatar"><i class="fas fa-user"></i></span>
                    <span class="nav-text"><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?></span>
                </a>
            </div>
        </aside>
    <?php endif; ?>

    <div class="main-content d-flex flex-column min-vh-100">
        <?php if (!empty($_SESSION['id_usuario'])): ?>
            <header class="top-header">
                <div class="top-header__left">
                    <button class="mobile-menu-btn" type="button" id="mobileMenuBtn" aria-label="Abrir menú" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>

                    <button class="desktop-collapse-btn" type="button" id="desktopSidebarToggle" aria-label="Contraer o expandir menú" aria-expanded="true">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="page-header-meta">
                        <span class="page-kicker">Sistema</span>
                        <h1 id="pageTitle">Dashboard</h1>
                    </div>
                </div>

                <div class="top-header__right">
                    <button class="header-icon-btn" type="button" aria-label="Notificaciones">
                        <i class="fas fa-bell"></i>
                        <span class="notif-badge">3</span>
                    </button>

                    <div class="dropdown user-menu-wrap">
                        <button class="user-menu-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar-mini"><i class="fas fa-user"></i></span>
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>perfil">Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/logout">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </header>
        <?php endif; ?>

        <!-- Aquí empieza el contenido dinámico -->
        <main class="flex-grow-1 content-shell">