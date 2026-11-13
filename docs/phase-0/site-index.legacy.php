<?php
require_once 'conexion.php';

// Consulta para productos más comprados
$sql_mas_comprados = "
    SELECT 
        p.id_producto, 
        p.nombre, 
        p.precio_venta, 
        COALESCE(SUM(d.cantidad), 0) AS total_cantidad
    FROM productos p
    LEFT JOIN detalle_ventas d ON p.id_producto = d.id_producto
    LEFT JOIN ventas v ON d.id_venta = v.id_venta
    WHERE p.activo = 1
      AND (v.estado = 'completada' OR v.estado IS NULL)
    GROUP BY p.id_producto
    ORDER BY total_cantidad DESC
    LIMIT 16
";

$result_mas_comprados = $conexion->query($sql_mas_comprados);
$productosMasComprados = $result_mas_comprados ? $result_mas_comprados->fetch_all(MYSQLI_ASSOC) : [];

// Consulta para productos nuevos
$sql_novedades = '
    SELECT id_producto, nombre, precio_venta, fecha_creacion
    FROM productos
    WHERE activo = 1
    ORDER BY fecha_creacion DESC
    LIMIT 16
';

$result_novedades = $conexion->query($sql_novedades);
$ultimosProductos = $result_novedades ? $result_novedades->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <title>Studio & Progetto</title>
    <meta name="description"
        content="Tienda online de útiles escolares, universitarios y de oficina. Productos de arquitectura, diseño y servicios de impresión en Studio & Progetto.">
    <meta name="keywords"
        content="útiles escolares, útiles universitarios, oficina, productos de arquitectura, diseño, servicios de impresión, papelería, librería, Studio & Progetto">

    <meta name="author" content="Nesch0811">

    <meta property="og:title" content="Studio & Progetto">
    <meta property="og:description"
        content="Encuentra útiles escolares, universitarios, de oficina, arquitectura y diseño. También ofrecemos servicios variados.">
    <meta property="og:image" content="https://tusitio.com/imagenes/logosinfondo.png">
    <meta property="og:url" content="https://tusitio.com">
    <meta property="og:type" content="website">

    <link rel="icon" href="imagenes/logosinfondo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">

    <style>
        :root {
            --primary-green: #1A2A44;
            --primary-yellow: #F5F5F5;
            --primary-color: #1A2A44;
            --secondary-color: #5673a2;
            --accent-color: #295caf;
            --dark-color: #2d3436;
            --light-color: #F5F5F5;
            --card-bg: #ffffff;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 25px rgba(8, 13, 57, 0.15);
            --shadow-color: rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --control-bg: rgba(43,45,66,0.85);
            --control-size: 48px;
            --control-gap: 12px;
            --card-width: 270px;
            --card-height: 406px;
        }

        body {
            overflow-x: hidden;
            width: 100%;
            color: var(--text-light);
        }

        .testimonios-section {
            background: linear-gradient(to bottom, #212529, #1a2a44);
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }

        .testimonios-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(108, 99, 255, 0.1) 0%, rgba(108, 99, 255, 0) 70%);
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .testimonial-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .testimonial-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .testimonial-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.822);
        }

        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .quote-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .card-text {
            flex-grow: 1;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .client-info {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.2rem;
            margin-top: auto;
        }

        .client-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .client-name {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .client-position {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .carousel-indicators {
            bottom: -50px;
        }

        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin: 0 5px;
            background-color: rgba(255, 255, 255, 0.3);
            border: none;
        }

        .carousel-indicators button.active {
            background-color: var(--primary-color);
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            opacity: 0.7;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            opacity: 1;
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            background-size: 60%;
        }

        /* MODAL EN LA PARTE INFERIOR */
        .consent-modal {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 20px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
            z-index: 1040;
            border-radius: 12px 12px 0 0;
            text-align: center;
            color: #333;
            width: 100%;
            max-width: 100%;
        }

        .consent-modal p {
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.5;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1039;
        }

        /* Slider Styles */
        .productosPopulares .container { 
            max-width: 1400px; 
        }
        .productosPopulares h2 { 
            color: #fff; 
        }

        .slider-wrap { 
            position: relative; 
            margin-top: 10px; 
        }
        .cards {
            display: flex;
            gap: 10px;
            padding: 18px 6px;
            list-style: none;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            cursor: grab;
            margin: 0;
        }
        .cards:active { 
            cursor: grabbing; 
        }

        .cards { 
            scrollbar-width: none; 
            -ms-overflow-style: none; 
        }
        .cards::-webkit-scrollbar{ 
            display: none; 
        }

        .card {
            box-sizing: border-box;
            flex: 0 0 auto;
            width: var(--card-width);
            height: var(--card-height);
            padding: 12px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,.12);
            scroll-snap-align: start;
            transition: transform .18s;
            color: #222;
            position: relative;
        }
        .card:hover { 
            transform: translateY(-4px); 
        }

        .product-image { 
            position: relative; 
        }
        .product-image img { 
            width: 100%; 
            height: 180px; 
            object-fit: cover; 
            border-radius: 8px; 
            display: block; 
        }

        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff6b6b;
            color: white;
            padding: 6px 8px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.75rem;
            z-index: 5;
            box-shadow: 0 4px 10px rgba(0,0,0,.12);
        }

        .product-title { 
            font-size: 1rem; 
            margin: 6px 0; 
            color: #111; 
        }
        .product-price { 
            color: #111; 
            margin-bottom: 6px; 
        }
        .product-stats { 
            color: #666; 
            font-size: .9rem; 
        }

        .controls {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .control-btn {
            pointer-events: auto;
            position: absolute;
            width: var(--control-size);
            height: var(--control-size);
            border-radius: 50%;
            background: var(--control-bg);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(0,0,0,.25);
            cursor: pointer;
            transition: transform .12s;
            z-index: 10;
        }
        .control-btn:active{ 
            transform: scale(.98); 
        }
        .control-prev{ 
            left: var(--control-gap); 
        }
        .control-next{ 
            right: var(--control-gap); 
        }
        .control-icon{ 
            font-size: 18px; 
            user-select: none; 
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .testimonios-section {
                padding: 3rem 0;
            }

            .testimonial-card {
                margin-bottom: 1.5rem;
            }

            .carousel-control-prev,
            .carousel-control-next {
                width: 10%;
            }
        }

        @media (max-width: 900px){
            :root { 
                --card-width: 240px; 
                --card-height: 370px; 
            }
        }

        @media (max-width: 700px){
            .controls { 
                top: auto; 
                bottom: 8px; 
                transform: none; 
                display: flex; 
                justify-content: center; 
                gap: 12px; 
            }
            .control-btn { 
                position: static; 
                width: 40px; 
                height: 40px; 
                border-radius: 8px; 
            }
            :root { 
                --card-width: 200px; 
                --card-height: 360px; 
            }
        }

        @media (max-width: 576px) {
            .testimonios-section {
                padding: 2rem 0;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .card-body {
                padding: 1.2rem;
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Marca slider styles */
        .sliderMarca {
            background: linear-gradient(to bottom, #1A2A44, #212529);
        }
        
        .slide-track {
            display: flex;
            animation: scroll 40s linear infinite;
            width: calc(250px * 30);
        }
        
        .slides {
            width: 250px;
            padding: 20px;
        }
        
        .slides img {
            width: 100%;
            filter: grayscale(100%);
            transition: filter 0.3s ease;
        }
        
        .slides img:hover {
            filter: grayscale(0%);
        }
        
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-250px * 15)); }
        }
    </style>
</head>

<body>
    <div id="overlayer"></div>
    <div id="preloader">
        <div class="preloader-content">
            <img src="imagenes/studio/logosinfondo.png" alt="Logo Studio & Progetto" class="preloader-logo">
            <h2 class="preloader-text">STUDIO & PROGETTO</h2>
        </div>
    </div>

    <!-- MODAL DE CONSENTIMIENTO CENTRADO -->
    <div class="modal-overlay" id="modal-overlay"></div>
    <div class="consent-modal" id="consent-modal">
        <p>
            No se realizarán transacciones debido a que es un Sitio Web. Toda compra se realizará a través de Whatsapp.
            Si tienes alguna duda, entra a: <a href="preguntasFrecuentes.html" target="_blank">Preguntas Frecuentes</a>. ¿Aceptas
            nuestras
            <a href="terminosCondiciones.html" target="_blank">Términos y Condiciones</a> y
            <a href="politicasPrivacidad.html" target="_blank">Políticas de Privacidad</a>?
        </p>
        <div class="d-flex justify-content-center gap-3">
            <button class="btn btn-secondary btn-sm" id="btn-decline">No</button>
            <button class="btn btn-primary btn-sm" id="btn-accept">Sí</button>
        </div>
    </div>

    <div class="welcome-message" id="welcome-message">
    </div>

    <!-- TOP BANNER-->
    <div class="container-fluid bg-white text-dark py-0 py-sm-1 py-md-2 fixed-top"
        style="z-index: 1032; height: 40px; display: flex; align-items: center;">
        <div class="row w-100">
            <div class="col-12 text-center d-flex align-items-center justify-content-center flex-wrap banner-text">
                <i class="fas fa-truck me-1 text-menu fs-7 fs-sm-6 fs-md-5 fs-lg-4"></i>
                <span class="fw-bold text-menu fs-7 fs-sm-6 fs-md-5 fs-lg-4">DELIVERY GRATIS</span>
                <span class="fw-bold text-menu ms-1 fs-7 fs-sm-6 fs-md-5 fs-lg-4">por compras mayores a</span>
                <span class="fw-bold text-dark ms-1 fs-7 fs-sm-6 fs-md-5 fs-lg-4">S/99</span>
                <span class="fw-bold text-dark ms-2 fs-7 fs-sm-6 fs-md-5 fs-lg-4">Válido solo para la ciudad de
                    Trujillo</span>
            </div>
        </div>
    </div>

    <!-- NAVBAR CORREGIDO -->
    <nav class="navbar navbar-dark bg-menu fixed-top px-4 mb-5" style="top: 40px; z-index: 1031;">
        <div class="container-fluid py-1">

            <!-- === LAYOUT PARA MÓVILES === -->
            <div class="d-flex d-md-none w-100 align-items-center fixed-top mt-1"
                style="top: 44px; left: 0; right: 0; background-color: #1A2A44; padding: 6px 15px; z-index: 1032;">

                <!-- Botón hamburguesa -->
                <div class="me-auto">
                    <button class="btn btn-outline-light btn-custom d-flex align-items-center justify-content-center"
                        data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
                        style="font-size: clamp(1rem, 2vw, 0.9rem); height: 40px; width: 40px;">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <!-- Logo -->
                <div class="position-absolute start-50 translate-middle-x">
                    <a class="navbar-brand d-flex align-items-center m-0 text-decoration-none" href="index.php">
                        <img src="imagenes/logosinfondo.png" alt="Logo" class="me-2 img-fluid"
                            style="max-height: 45px; min-height: 35px;">
                        <span class="logo-text lh-1 fw-bold text-white" style="font-size: clamp(1rem, 3.5vw, 1.8rem);">
                            Studio &<br><span>Progetto</span>
                        </span>
                    </a>
                </div>

                <!-- Lista de deseos -->
                <div class="ms-auto d-flex align-items-center gap-2">
                    <a href="lista-deseo.html"
                        class="btn btn-light btn-custom d-inline-flex align-items-center justify-content-center position-relative"
                        style="font-size: clamp(0.9rem, 1.8vw, 0.8rem); height: 40px; width: 40px;">
                        <i class="fa fa-heart text-danger"></i>
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success d-flex align-items-center justify-content-center"
                            style="min-width: 18px; height: 18px; font-size: 0.65rem; padding: 0;">0</span>
                    </a>

                    <!-- Icono de Iniciar Sesión (Móvil) -->
                    <a href="/ProyectoWeb/mi_sistema_mvc/public/auth/login"
                        class="btn btn-outline-light d-inline-flex align-items-center justify-content-center"
                        style="font-size: 1rem; height: 40px; width: 40px;" title="Iniciar Sesión">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </div>

            <!-- Buscador móvil -->
            <div class="w-100 d-flex d-md-none fixed-top"
                style="top: 95px; left: 0; right: 0; padding: 0 15px 15px 15px; z-index: 1032; background-color: #1A2A44;">
                <form class="w-100">
                    <div class="input-group">
                        <input class="form-control border-0" type="search" placeholder="¿Qué producto deseas?"
                            aria-label="Buscar productos"
                            style="font-size: clamp(0.8rem, 2vw, 0.9rem); height: 40px; box-shadow: 0 2px 8px var(--shadow-color);">
                        <button class="btn btn-outline-light btn-custom" type="submit"
                            style="height: 40px; width: 45px;">
                            <i class="fas fa-search" style="font-size: clamp(0.85rem, 2vw, 1rem);"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- === LAYOUT PARA DESKTOP / TABLET === -->
            <div class="row w-100 align-items-center g-3 d-none d-md-flex">

                <!-- Logo -->
                <div class="col-md-3 col-lg-2">
                    <a class="navbar-brand d-flex align-items-center m-0 text-decoration-none" href="index.php">
                        <img src="imagenes/logosinfondo.png" alt="Logo"
                            style="width: clamp(35px, 6vw, 50px); height: clamp(35px, 6vw, 50px);" class="me-2">
                        <span class="logo-text lh-1 fw-bold text-white"
                            style="font-size: clamp(0.9rem, 2.8vw, 1.3rem);">
                            Studio &<br><span>Progetto</span>
                        </span>
                    </a>
                </div>

                <!-- Botón Categorías -->
                <div class="col-md-2 col-lg-2">
                    <button
                        class="btn btn-outline-light btn-custom w-100 d-flex align-items-center justify-content-center gap-2 shadow-custom"
                        data-bs-toggle="collapse" data-bs-target="#sidebarMenu" style="height: 45px;">
                        <i class="fas fa-bars"></i>
                        <span>CATEGORÍAS</span>
                    </button>
                </div>

                <!-- Buscador -->
                <div class="col-md-4 col-lg-5">
                    <form class="d-flex w-100">
                        <div class="input-group">
                            <input class="form-control border-0" type="search" placeholder="¿Qué necesitas studiolover?"
                                aria-label="Buscar productos"
                                style="font-size: clamp(0.85rem, 2.2vw, 1rem); height: 45px; box-shadow: 0 2px 8px var(--shadow-color);">
                            <button class="btn btn-outline-light btn-custom" type="submit"
                                style="height: 45px; width: 50px;">
                                <i class="fas fa-search" style="font-size: clamp(0.9rem, 2.2vw, 1.1rem);"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Deseos + Iniciar Sesión -->
                <div class="col-md-3 col-lg-3">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <!-- Lista de Deseos -->
                        <a href="lista-deseo.html"
                            class="btn btn-light btn-custom d-inline-flex align-items-center justify-content-center"
                            style="font-size: clamp(0.7rem, 2vw, 0.85rem); height: 45px; white-space: nowrap;">
                            <i class="fa fa-heart me-1 text-danger"></i>
                            <span class="d-none d-lg-inline">Lista de deseos</span>
                            <span class="d-none d-md-inline d-lg-none">Deseos</span>
                            <span class="badge bg-success ms-1" id="wishlistCountDesktop">0</span>
                        </a>

                        <!-- Iniciar Sesión -->
                        <a href="/ProyectoWeb/mi_sistema_mvc/public/auth/login"
                            class="btn btn-outline-light btn-custom d-inline-flex align-items-center justify-content-center"
                            style="font-size: clamp(0.7rem, 2vw, 0.85rem); height: 45px; white-space: nowrap;">
                            <i class="fas fa-user me-1"></i>
                            <span class="d-none d-lg-inline">Iniciar sesión</span>
                            <span class="d-none d-md-inline d-lg-none">Login</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </nav>

    <!-- Menú lateral con collapse -->
    <div class="collapse sidebar-menu" id="sidebarMenu">
        <!-- Header con logo -->
        <div class="sidebar-header">
            <button class="close-btn" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
                title="Cerrar menú">&times;</button>
            <div class="d-flex align-items-center justify-content-center">
                <img src="imagenes/logosinfondo.png" alt="Logo" class="me-3" style="height: 50px;">
                <div class="logo-text">Studio & Progetto</div>
            </div>
        </div>

        <!-- Lista de categorías -->
        <ul class="sidebar-categories">
            <li>
                <a href="#" onclick="toggleSubmenu('arte&manualidades')">
                    <i class="fas fa-paint-brush me-3"></i>ARTE & MANUALIDADES
                </a>
                <div class="sidebar-submenu" id="arte&manualidades">
                    <a href="productos.php?cat[]=6">Acuarela x12 colores</a>
                    <a href="productos.php?cat[]=6">Bajalenguas</a>
                    <a href="productos.php?cat[]=6">Bolsa de Escarcha</a>
                    <a href="productos.php?cat[]=6">Chenille</a>
                    <a href="productos.php?cat[]=6">Corrospum</a>
                    <a href="productos.php?cat[]=6">Ganchos naturales</a>
                    <a href="productos.php?cat[]=6">Goma Eva</a>
                    <a href="productos.php?cat[]=6">Ojos Movibles</a>
                    <a href="productos.php?cat[]=6">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('cartones&bases')">
                    <i class="fas fa-layer-group me-3"></i>CARTONES & BASES
                </a>
                <div class="sidebar-submenu" id="cartones&bases">
                    <a href="productos.php?cat[]=2">Base de Corte</a>
                    <a href="productos.php?cat[]=2">Cartones Corrugados</a>
                    <a href="productos.php?cat[]=2">Cartones Maquetas</a>
                    <a href="productos.php?cat[]=2">Esferas de Tecnopor</a>
                    <a href="productos.php?cat[]=2">Liner</a>
                    <a href="productos.php?cat[]=2">Plancha de corcho</a>
                    <a href="productos.php?cat[]=2">Texturas adhesivas</a>
                    <a href="productos.php?cat[]=2">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('coleccionacademica')">
                    <i class="fas fa-graduation-cap me-3"></i>COLECCIÓN ACADÉMICA
                </a>
                <div class="sidebar-submenu" id="coleccionacademica">
                    <a href="productos.php?cat[]=9">Bitácora Canson</a>
                    <a href="productos.php?cat[]=9">Block Canson</a>
                    <a href="productos.php?cat[]=9">Folder Escolar</a>
                    <a href="productos.php?cat[]=9">Tablero Acrílico</a>
                    <a href="productos.php?cat[]=9">Tablero de Madera</a>
                    <a href="productos.php?cat[]=9">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('dibujotecnico')">
                    <i class="fas fa-pencil-ruler me-3"></i>MATERIALES DE DIBUJO TÉCNICO
                </a>
                <div class="sidebar-submenu" id="dibujotecnico">
                    <a href="productos.php?cat[]=3">Borradores</a>
                    <a href="productos.php?cat[]=3">Compáces con alargadera</a>
                    <a href="productos.php?cat[]=3">Cutters</a>
                    <a href="productos.php?cat[]=3">Lápices de Colores</a>
                    <a href="productos.php?cat[]=3">Lápices de Dibujo (2B, 4B, 6B)</a>
                    <a href="productos.php?cat[]=3">Papeles milimetrados</a>
                    <a href="productos.php?cat[]=3">Portaminas 0.5 / 0.7 mm</a>
                    <a href="productos.php?cat[]=3">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('escolares&oficina')">
                    <i class="fas fa-briefcase me-3"></i>ESCOLARES & OFICINA
                </a>
                <div class="sidebar-submenu" id="escolares&oficina">
                    <a href="productos.php?cat[]=7">Alfileres</a>
                    <a href="productos.php?cat[]=7">Blinder</a>
                    <a href="productos.php?cat[]=7">Caja de tizas de colores</a>
                    <a href="productos.php?cat[]=7">Chinches de colores</a>
                    <a href="productos.php?cat[]=7">Clips Metálicos</a>
                    <a href="productos.php?cat[]=7">Fasteners</a>
                    <a href="productos.php?cat[]=7">Grapas Metálicas</a>
                    <a href="productos.php?cat[]=7">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('limpieza&emergencia')">
                    <i class="fas fa-broom me-3"></i>LIMPIEZA & BÁSICOS DE EMERGENCIA
                </a>
                <div class="sidebar-submenu" id="limpieza&emergencia">
                    <a href="productos.php?cat[]=8">Bicarbonato de Sodio</a>
                    <a href="productos.php?cat[]=8">Encendedores con diseño</a>
                    <a href="productos.php?cat[]=8">Hisopos</a>
                    <a href="productos.php?cat[]=8">Navaja de Afeitar</a>
                    <a href="productos.php?cat[]=8">Papel Higiénico</a>
                    <a href="productos.php?cat[]=8">Pilas</a>
                    <a href="productos.php?cat[]=8">Velas</a>
                    <a href="productos.php?cat[]=8">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('maquetas&arquitectura')">
                    <i class="fas fa-city me-3"></i>MAQUETAS & ARQUITECTURA
                </a>
                <div class="sidebar-submenu" id="maquetas&arquitectura">
                    <a href="productos.php?cat[]=1">Acerrín</a>
                    <a href="productos.php?cat[]=1">Árboles de 6cm</a>
                    <a href="productos.php?cat[]=1">Escalas de Colores</a>
                    <a href="productos.php?cat[]=1">Flores Secas</a>
                    <a href="productos.php?cat[]=1">Inmuebles en miniatura</a>
                    <a href="productos.php?cat[]=1">Palmeras</a>
                    <a href="productos.php?cat[]=1">Pasto de colores</a>
                    <a href="productos.php?cat[]=1">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('maderas')">
                    <i class="fas fa-tree me-3"></i>MADERAS
                </a>
                <div class="sidebar-submenu" id="maderas">
                    <a href="productos.php?cat[]=5">Madera Balsa</a>
                    <a href="productos.php?cat[]=5">Madera Caoba</a>
                    <a href="productos.php?cat[]=5">Madera Caoba Laminada</a>
                    <a href="productos.php?cat[]=5">Varilla Cuadrada</a>
                    <a href="productos.php?cat[]=5">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="#" onclick="toggleSubmenu('papeleria')">
                    <i class="fas fa-file-alt me-3"></i>PAPELERÍA
                </a>
                <div class="sidebar-submenu" id="papeleria">
                    <a href="productos.php?cat[]=4">Block de dibujo A4 / A3</a>
                    <a href="productos.php?cat[]=4">Cartulinas de colores</a>
                    <a href="productos.php?cat[]=4">Cartulina Escolar</a>
                    <a href="productos.php?cat[]=4">Papel de Regalo</a>
                    <a href="productos.php?cat[]=4">Papeles Bond</a>
                    <a href="productos.php?cat[]=4">Papeles Carta</a>
                    <a href="productos.php?cat[]=4">Ver Todos</a>
                </div>
            </li>

            <li>
                <a href="productos.php">
                    <i class="fas fa-box-open me-3"></i>TODOS LOS PRODUCTOS
                </a>
            </li>
        </ul>
    </div>

   <!-- Slider principal mejorado -->
    <div class="hero-section mb-0 ">
        <!-- Videos del slider -->
        <video autoplay loop muted class="hero-video active" id="video-1">
            <source src="vid/video.mp4" type="video/mp4">
          
          
        </video>
        <video autoplay loop muted class="hero-video active" id="video-2">
           <source src="vid/video.mp4" type="video/mp4">
          
          
        </video>
        <video autoplay loop muted class="hero-video active" id="video-3">
            <source src="vid/video.mp4" type="video/mp4">
          
        </video>
        <div class="video-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">STUDIO PROGGETTO</h1>
            <p class="hero-subtitle">Donde cada idea empieza a tomar forma</p>
            <button class="hero-btn" onclick="window.location.href='/productos'">
                Descubre nuestros productos
            </button>

        </div>

    </div>
    <div class="content-wrapper bg-dark text-white ">
        <section class="content-block bg-dark pt-5">
            <div class="container">
                <div class="row multiple-row v-align-row">
                    <div class="col-lg-4 col-md-6">
                        <div class="col-wrap">
                            <div class="block-heading">
                                <h3 class="block-top-heading text-center">¿QUÉ SERVICIOS OFRECEMOS?</h3>
                                <span class="block-sub-heading text-center">Ofrecemos una variedad de servicios</span>
                                <div class="divider text-center">
                                    <img src="imagenes/studio/logosinfondo.png" alt="studio" style="max-width: 200px; height: auto;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="col-wrap">
                            <div class="ico-box bg-gray-light has-radius-medium">
                                <div class="icon">
                                    <span class="custom-icon-pen-tool"><span class="sr-only">&amp;</span></span>
                                </div>
                                <h4 class="content-title"><a href="#">IMPRESIONES</a></h4>
                                <div class="des">
                                    <p>Realizamos impresiones a color y en blanco y negro con excelente calidad láser. Ideal para trabajos universitarios, documentos oficiales y material gráfico.</p>
                                </div>
                                <div class="link-holder">
                                    <a class="link-more" href="productos.php">Ver más</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="col-wrap">
                            <div class="ico-box bg-gray-light has-radius-medium">
                                <div class="icon">
                                    <span class="custom-icon-vector"><span class="sr-only">&amp;</span></span>
                                </div>
                                <h4 class="content-title"><a href="#">COPIAS</a></h4>
                                <div class="des">
                                    <p>Copiamos tus documentos con rapidez, nitidez y precisión. Equipos modernos para copias a gran volumen o pequeñas cantidades.</p>
                                </div>
                                <div class="link-holder">
                                    <a class="link-more" href="productos.php">Ver más</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="col-wrap">
                            <div class="ico-box bg-gray-light has-radius-medium">
                                <div class="icon">
                                    <span class="custom-icon-font-design"><span class="sr-only">&amp;</span></span>
                                </div>
                                <h4 class="content-title"><a href="#">ANILLADOS</a></h4>
                                <div class="des">
                                    <p>Encuadernamos y anillamos tus trabajos con acabados elegantes y resistentes. Anillados plásticos y metálicos.</p>
                                </div>
                                <div class="link-holder">
                                    <a class="link-more" href="productos.php">Ver más</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="col-wrap">
                            <div class="ico-box bg-gray-light has-radius-medium">
                                <div class="icon">
                                    <span class="custom-icon-layers"><span class="sr-only">&amp;</span></span>
                                </div>
                                <h4 class="content-title"><a href="#">CORTES</a></h4>
                                <div class="des">
                                    <p>Realizamos cortes precisos en papel, cartulina, vinilo y otros materiales. Perfecto para tí.</p>
                                </div>
                                <div class="link-holder">
                                    <a class="link-more" href="productos.php">Ver más</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="col-wrap">
                            <div class="ico-box bg-gray-light has-radius-medium">
                                <div class="icon">
                                    <span class="custom-icon-list"><span class="sr-only">&amp;</span></span>
                                </div>
                                <h4 class="content-title"><a href="#">ESCANEOS</a></h4>
                                <div class="des">
                                    <p>Digitalizamos tus documentos con alta resolución. Archivos en PDF, JPG listos para enviar o almacenar.</p>
                                </div>
                                <div class="link-holder">
                                    <a class="link-more" href="productos.php">Ver más</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="sliderMarca " style="background: linear-gradient(to bottom, #1A2A44, #212529);">
        <div class="container-fluid  pt-5">
            <h3 class="fw-bold fs-1 text-center text-white mb-4 reveal ">Nuestras Marcas</h3>
        </div>

        <div class="slide-track">
            <!-- Marcas originales -->
            <div class="slides"><img src="imagenes/imagenes marcas/50000000.png" alt="Marca 1"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/Canson_fr_logo.png" alt="Marca 2"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/dorco.png" alt="Marca 3"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/Enterprise-1024x951.png" alt="Marca 4"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/faber-castell-1761-logo-png_seeklogo-289924.png" alt="Marca 5"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/fabriano-logo-png-transparent.png" alt="Marca 6"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/justus-logo-860-23792.png" alt="Marca 7"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/layconsa-37193.png" alt="Marca 8"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/logo-gallo.png" alt="Marca 9"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/logo-pegafan-web.png" alt="Marca 10"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/MILLENIUM.jpg" alt="Marca 11"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/Rotring.svg.png" alt="Marca 12"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/UHU_logo.svg.png" alt="Marca 13"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/unipinlogo__51960.webp" alt="Marca 14"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/vinifan-37198.png" alt="Marca 15"></div>

            <!-- Duplicados para efecto infinito -->
            <div class="slides"><img src="imagenes/imagenes marcas/50000000.png" alt="Marca 1"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/Canson_fr_logo.png" alt="Marca 2"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/dorco.png" alt="Marca 3"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/Enterprise-1024x951.png" alt="Marca 4"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/faber-castell-1761-logo-png_seeklogo-289924.png" alt="Marca 5"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/fabriano-logo-png-transparent.png" alt="Marca 6"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/justus-logo-860-23792.png" alt="Marca 7"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/layconsa-37193.png" alt="Marca 8"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/logo-gallo.png" alt="Marca 9"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/logo-pegafan-web.png" alt="Marca 10"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/MILLENIUM.jpg" alt="Marca 11"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/Rotring.svg.png" alt="Marca 12"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/UHU_logo.svg.png" alt="Marca 13"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/unipinlogo__51960.webp" alt="Marca 14"></div>
            <div class="slides"><img src="imagenes/imagenes marcas/vinifan-37198.png" alt="Marca 15"></div>
        </div>
    </div>

    <!-- Productos Más Comprados -->
    <section class="productosPopulares bg-dark text-white mt-7 pt-5 py-5">
        <div class="container">
            <h2 class="fw-bold fs-1 text-center reveal mb-4 pb-4">Productos Populares</h2>

            <div class="slider-wrap" id="sliderWrap">
                <ul class="cards" id="cardsContainer" aria-label="Productos">
                    <?php if (! empty($productosMasComprados)) { ?>
                        <?php foreach ($productosMasComprados as $producto) {
                            $imgPath = 'imagenes/sin_imagen.jpg';
                            $nombreArchivo = preg_replace('/\s+/', '_', strtolower($producto['nombre']));
                            if (file_exists("imagenes/imagenes productos/$nombreArchivo.jpg")) {
                                $imgPath = "imagenes/imagenes productos/$nombreArchivo.jpg";
                            }
                            ?>
                            <li class="card">
                                <div class="product-card-inner" style="display:flex;flex-direction:column;height:100%;">
                                    <div class="product-image text-center" style="flex:0 0 auto;">
                                        <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="img-fluid rounded" style="max-width:100%; max-height:180px; object-fit:cover;">
                                    </div>

                                    <div class="product-info" style="padding:12px; flex:1 1 auto; display:flex; flex-direction:column;">
                                        <h3 class="product-title fw-semibold" style="font-size:1.05rem; margin-bottom:6px;"><?= htmlspecialchars($producto['nombre']) ?></h3>
                                        <div class="product-price mt-1" style="font-weight:700;">S/ <?= number_format($producto['precio_venta'], 2) ?></div>
                                        <div class="product-stats mt-2" style="font-size:.9rem; color:rgba(0,0,0,.7); margin-top:6px;">
                                            <div class="product-stat"><i class="fas fa-shopping-cart"></i> <?= (int) $producto['total_cantidad'] ?> compras</div>
                                        </div>

                                        <div style="margin-top:auto; padding-top:8px;">
                                            <a href="productos.php" class="btn btn-primary d-block mx-auto mt-3">
                                                <i class="fas fa-eye me-1"></i> Ver Producto
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php } ?>
                    <?php } else { ?>
                        <li class="card">
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;">
                                <p class="text-center text-muted">No hay productos con ventas registradas.</p>
                            </div>
                        </li>
                    <?php } ?>
                </ul>

                <div class="controls" aria-hidden="false">
                    <button class="control-btn control-prev" id="prevBtn" aria-label="Anterior">
                        <span class="control-icon" aria-hidden="true">&#10094;</span>
                    </button>
                    <button class="control-btn control-next" id="nextBtn" aria-label="Siguiente">
                        <span class="control-icon" aria-hidden="true">&#10095;</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Novedades -->
    <section class="productosPopulares" style="background: linear-gradient(to bottom, #1A2A44, #212529)">
        <div class="container">
            <h2 class="fw-bold fs-1 text-center text-white pb-4 reveal">Novedades</h2>

            <div class="slider-wrap" id="sliderWrapNovedades">
                <ul class="cards" id="cardsContainerNovedades" aria-label="Novedades">
                    <?php if (! empty($ultimosProductos)) { ?>
                        <?php foreach ($ultimosProductos as $producto) {
                            $imgPath = 'imagenes/sin_imagen.jpg';
                            $nombreArchivo = preg_replace('/\s+/', '_', strtolower($producto['nombre']));
                            if (file_exists("imagenes/imagenes productos/$nombreArchivo.jpg")) {
                                $imgPath = "imagenes/imagenes productos/$nombreArchivo.jpg";
                            }
                            ?>
                            <li class="card">
                                <div class="product-card-inner" style="display:flex;flex-direction:column;height:100%;">
                                    <div class="product-image text-center position-relative" style="flex:0 0 auto;">
                                        <span class="product-badge">NUEVO</span>
                                        <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="img-fluid rounded" />
                                    </div>

                                    <div class="product-info" style="padding:12px; flex:1 1 auto; display:flex; flex-direction:column;">
                                        <h3 class="product-title fw-semibold" style="font-size:1.05rem; margin-bottom:6px; color:#111;">
                                            <?= htmlspecialchars($producto['nombre']) ?>
                                        </h3>
                                        <div class="product-price mt-1" style="font-weight:700; color:#111;">
                                            S/ <?= number_format($producto['precio_venta'], 2) ?>
                                        </div>

                                        <div style="margin-top:auto; padding-top:8px;">
                                            <a href="productos.php" class="btn btn-primary d-block mx-auto mt-3">
                                                <i class="fas fa-eye me-1"></i> Ver Producto
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php } ?>
                    <?php } else { ?>
                        <li class="card">
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;">
                                <p class="text-center text-muted">No hay productos recientes disponibles.</p>
                            </div>
                        </li>
                    <?php } ?>
                </ul>

                <div class="controls" aria-hidden="false">
                    <button class="control-btn control-prev" id="prevBtnNovedades" aria-label="Anterior">
                        <span class="control-icon" aria-hidden="true">&#10094;</span>
                    </button>
                    <button class="control-btn control-next" id="nextBtnNovedades" aria-label="Siguiente">
                        <span class="control-icon" aria-hidden="true">&#10095;</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Carousel elegante de testimonios -->
    <section class="testimonios-section py-5">
        <div class="container">
            <h3 class="fw-bold fs-1 text-white text-center mb-4 pb-4 reveal ">Testimonios</h3>

            <div id="testimoniosCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators position-static mb-4">
                    <button type="button" data-bs-target="#testimoniosCarousel" data-bs-slide-to="0" class="active"
                        aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#testimoniosCarousel" data-bs-slide-to="1"
                        aria-label="Slide 2"></button>
                </div>

                <div class="carousel-inner">
                    <!-- Slide 1 -->
                    <div class="carousel-item active">
                        <div class="row g-4 justify-content-center">
                            <!-- Testimonio 1 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "Studio & Progetto ha revolucionado nuestro proceso de abastecimiento de
                                            materiales.
                                            Su enfoque estratégico y catálogo diversificado nos ha permitido
                                            optimizar costos
                                            sin comprometer la calidad. Una verdadera alianza de valor."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=1" alt="Andrea"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">Andrea</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonio 2 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "La excelencia en el servicio al cliente es su sello distintivo. Su
                                            equipo
                                            no solo responde con celeridad, sino que anticipa necesidades y ofrece
                                            soluciones personalizadas que añaden valor tangible a nuestras
                                            operaciones."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=5" alt="Nancy"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">Nancy</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonio 3 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "Su logística de distribución es excepcional. En un entorno empresarial
                                            donde
                                            el tiempo es crítico, sus entregas puntuales y la integridad de los
                                            productos
                                            han fortalecido nuestra cadena de suministro."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=8" alt="Miguel"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">Miguel</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonio 4 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "Han transformado nuestra experiencia de adquisición de materiales
                                            educativos.
                                            Su enfoque consultivo y productos de calidad superior han impactado
                                            positivamente
                                            nuestros procesos académicos."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=12" alt="Milagros"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">Milagros</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div class="carousel-item">
                        <div class="row g-4 justify-content-center">
                            <!-- Testimonio 5 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "La durabilidad de sus productos y su constante innovación en el
                                            catálogo
                                            nos han posicionado como referentes en nuestro sector. Una relación
                                            comercial
                                            que genera ventajas competitivas."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=32" alt="Sebastian"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">Sebastian</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonio 6 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "Recomiendo Studio & Progetto como proveedor estratégico. Su sinergia
                                            entre
                                            variedad, eficiencia operativa y atención personalizada genera
                                            experiencias
                                            comerciales excepcionales y resultados medibles."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=44" alt="William"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">William</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonio 7 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "Su modelo de negocio integral soluciona todas nuestras necesidades de
                                            abastecimiento en un solo punto. La agilidad transaccional y su programa
                                            de fidelización crean relaciones comerciales duraderas."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=60" alt="Fabrizio"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">Fabrizio</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonio 8 -->
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="testimonial-card h-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-left"></i>
                                        </div>
                                        <p class="card-text text-center flex-grow-1">
                                            "La trifecta perfecta: calidad excepcional, diversidad de productos y
                                            servicio diferenciador. Han establecido un nuevo estándar en el sector,
                                            convirtiéndose en partners indispensables para nuestro crecimiento."
                                        </p>
                                        <div class="client-info">
                                            <div class="d-flex align-items-center">
                                                <img src="https://i.pravatar.cc/150?img=68" alt="Jair"
                                                    class="client-img rounded-circle me-3">
                                                <div>
                                                    <h6 class="client-name mb-0">Jair</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#testimoniosCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimoniosCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Banner con frase -->
    <div class="container-fluid py-5 text-center text-white " style="background: linear-gradient(to bottom, #1A2A44, #212529), url('imagenes/banner.jpg'); 
        background-size: cover; 
        background-position: center;">
        <h2 class="fw-bold display-6 mb-4 shadow-text reveal">
            "En Studio & Progetto creemos que cada día es una oportunidad para aprender, crear y construir el futuro
            que imaginas."
        </h2>
        <hr class="mx-auto mb-4"
            style="width: 60px; height: 3px; background-color: #ffcc00; border: none; border-radius: 2px;">
        <p class="fs-5 fst-italic reveal">
            Descubre materiales que inspiran tu creatividad y potencian tu aprendizaje.
        </p>
    </div>

    <!-- FOOTER -->
    <footer class="bg-dark text-light  pb-3 ps-5 pe-5 fs-5">
        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-12 col-md-3">
                    <div
                        class="d-flex align-items-center mb-3 justify-content-center justify-content-md-start text-center text-md-start">
                        <a href="index.php">
                            <img src="imagenes/logosinfondo.png" width="80" height="80" alt="Studio & Progetto Logo"
                                class="me-2 me-lg-3 flex-shrink-0">
                        </a>

                        <span class="logo-text d-inline-block lh-1 fw-bold text-break">
                            <span class="d-none d-md-inline d-lg-none">
                                <span class="fs-6">Studio &</span><br>
                                <span class="fs-6">Progetto</span>
                            </span>

                            <span class="d-none d-lg-inline">
                                <span class="fs-4">Studio &</span><br>
                                <span class="fs-3">Progetto</span>
                            </span>

                            <span class="d-inline d-md-none">
                                <span class="fs-6">Studio &</span><br>
                                <span class="fs-6">Progetto</span>
                            </span>
                        </span>
                    </div>

                    <p class="small text-center text-md-start">
                        Studio & Progetto es tu tienda online donde encontrarás útiles escolares,
                        universitarios, de
                        oficina y más.
                    </p>
                </div>

                <div class="col-6 col-md-3 text-center text-md-start">
                    <h5 class="fw-semibold mb-3">Enlaces Rápidos</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="index.php" class="text-light text-decoration-none">Inicio</a>
                        </li>
                        <li class="mb-2"><a href="productos.php" class="text-light text-decoration-none">Productos</a>
                        </li>
                        <li class="mb-2"><a href="nosotros.html" class="text-light text-decoration-none">Sobre
                                Nosotros</a></li>
                        <li class="mb-2"><a href="lista-deseo.html" class="text-light text-decoration-none">Lista de
                                Deseos</a>
                        </li>

                    </ul>
                </div>

                <div class="col-6 col-md-3 text-center text-md-start">
                    <h5 class="fw-semibold mb-3">Soporte</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="contacto.html" class="text-light text-decoration-none">Contacto</a>
                        </li>
                        <li class="mb-2"><a href="preguntasFrecuentes.html"
                                class="text-light text-decoration-none">Preguntas frecuentes</a>
                        </li>
                        <li class="mb-2"><a href="politicasPrivacidad.html"
                                class="text-light text-decoration-none">Política de privacidad</a>
                        </li>
                        <li class="mb-2"><a href="terminosCondiciones.html" class="text-light text-decoration-none">Términos y
                                condiciones</a>
                        </li>

                    </ul>
                </div>

                <div class="col-12 col-md-3 text-md-start text-center">
                    <h5 class="fw-semibold mb-3">Síguenos</h5>
                    <div class="social-icons d-flex justify-content-center  justify-content-md-start     ">
                        <a href="https://www.facebook.com/profile.php?id=61576368759571" target="_blank"
                            aria-label="Facebook" class="icon facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span class="tooltip">Facebook</span>
                        </a>
                        <a href="https://www.instagram.com/studioprogettosac/?utm_source=ig_web_button_share_sheet"
                            target="_blank" aria-label="Instagram" class="icon instagram">
                            <i class="fab fa-instagram"></i>
                            <span class="tooltip">Instagram</span>
                        </a>
                        <a href="https://www.tiktok.com/@tu_usuario" target="_blank" aria-label="TikTok"
                            class="icon tiktok">
                            <i class="fab fa-tiktok"></i>
                            <span class="tooltip">TikTok</span>
                        </a>
                    </div>

                    <p class="text-center text-md-start mt-4 ">
                        <i class="fas fa-envelope me-2"></i>studioprogettosac@gmail.com<br>
                        <i class="fas fa-phone me-2"></i>+51 939 275 406
                    </p>
                </div>

            </div>

            <div class="border-top border-secondary text-center text-secondary small mt-4 pt-3">
                <div class="row">
                    <div class="col-12">
                        © 2025 Studio & Progetto. <i class="fas fa-heart text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Botón flotante de WhatsApp -->
    <a id="whatsapp-btn" href="https://wa.me/51939275406" target="_blank" aria-label="Chatea con nosotros en WhatsApp"
        class="btn btn-success rounded-circle d-flex align-items-center justify-content-center position-fixed"
        style="width: 60px; height: 60px; bottom: 20px; right: 20px; z-index: 1035;">
        <i class="fab fa-whatsapp fa-2x"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // SLIDER PROFESIONAL CORREGIDO - BOTONES FUNCIONALES
    class InfiniteSlider {
        constructor(containerId, prevBtnId, nextBtnId) {
            this.container = document.getElementById(containerId);
            this.prevBtn = document.getElementById(prevBtnId);
            this.nextBtn = document.getElementById(nextBtnId);
            
            console.log(`Slider ${containerId}:`, {
                container: !!this.container,
                prevBtn: !!this.prevBtn,
                nextBtn: !!this.nextBtn
            });

            if (!this.container) {
                console.error(`Contenedor ${containerId} no encontrado`);
                return;
            }

            this.AUTOPLAY_DELAY = 4000;
            this.SCROLL_DURATION = 600;
            this.timerId = null;
            this.isPaused = false;
            this.isUserInteracting = false;
            this.isScrolling = false;
            
            this.originalCards = Array.from(this.container.children);
            this.originalCount = this.originalCards.length;
            
            if (this.originalCount === 0) {
                console.warn(`No hay cards en ${containerId}`);
                return;
            }

            this.init();
        }

        init() {
            this.createInfiniteTrack();
            this.setupEventListeners();
            this.startAutoPlay();
            console.log(`Slider inicializado con ${this.originalCount} cards`);
        }

        createInfiniteTrack() {
            // Limpiar clones existentes
            const existingClones = this.container.querySelectorAll('.clone');
            existingClones.forEach(clone => clone.remove());
            
            // Crear clones para efecto infinito
            this.originalCards.forEach(node => {
                const clone = node.cloneNode(true);
                clone.classList.add('clone');
                this.container.appendChild(clone);
            });

            this.allCards = Array.from(this.container.querySelectorAll('.card'));
            this.updateMeasurements();
            
            // Posicionar en el inicio del contenido original
            requestAnimationFrame(() => {
                this.container.scrollLeft = 0;
            });
        }

        updateMeasurements() {
            if (this.originalCount === 0) return;
            
            const firstCard = this.allCards[0];
            if (!firstCard) return;

            const cardStyle = window.getComputedStyle(firstCard);
            const containerStyle = window.getComputedStyle(this.container);
            
            // CALCULO CORREGIDO - Manejar valores no numéricos
            const marginLeft = parseInt(cardStyle.marginLeft) || 0;
            const marginRight = parseInt(cardStyle.marginRight) || 0;
            const gap = parseInt(containerStyle.gap) || 10; // Valor por defecto 10px
            
            this.cardWidth = firstCard.offsetWidth + marginLeft + marginRight + gap;
            this.originalContentWidth = this.cardWidth * this.originalCount;
            this.totalContentWidth = this.cardWidth * this.allCards.length;
            
            console.log('Medidas calculadas:', {
                cardWidth: this.cardWidth,
                originalContentWidth: this.originalContentWidth,
                totalContentWidth: this.totalContentWidth
            });
        }

        getCurrentIndex() {
            if (!this.cardWidth || this.cardWidth === 0) return 0;
            const scrollLeft = this.container.scrollLeft;
            return Math.round(scrollLeft / this.cardWidth) % this.originalCount;
        }

        scrollToIndex(index, behavior = 'smooth') {
            if (this.isScrolling || !this.cardWidth) return;
            
            this.isScrolling = true;
            const targetScroll = index * this.cardWidth;
            
            console.log(`Scroll to index ${index}, position: ${targetScroll}px`);
            
            this.container.scrollTo({
                left: targetScroll,
                behavior: behavior
            });

            // Liberar el bloqueo después de la animación
            setTimeout(() => {
                this.isScrolling = false;
            }, this.SCROLL_DURATION);
        }

        handleInfiniteScroll() {
            if (this.isScrolling || !this.originalContentWidth) return;

            const scrollLeft = this.container.scrollLeft;
            const containerWidth = this.container.clientWidth;
            
            // Si está al final, saltar al inicio sin animación
            if (scrollLeft >= this.originalContentWidth) {
                this.container.style.scrollBehavior = 'auto';
                this.container.scrollLeft = scrollLeft - this.originalContentWidth;
                this.container.style.scrollBehavior = 'smooth';
                console.log('Loop: final → inicio');
            }
            // Si está al inicio, saltar al final sin animación
            else if (scrollLeft <= 0) {
                this.container.style.scrollBehavior = 'auto';
                this.container.scrollLeft = this.originalContentWidth - containerWidth + scrollLeft;
                this.container.style.scrollBehavior = 'smooth';
                console.log('Loop: inicio → final');
            }
        }

        next() {
            if (this.isPaused || this.isUserInteracting || this.isScrolling || !this.cardWidth) {
                console.log('Next bloqueado:', {
                    isPaused: this.isPaused,
                    isUserInteracting: this.isUserInteracting,
                    isScrolling: this.isScrolling,
                    cardWidth: this.cardWidth
                });
                return;
            }
            
            const currentIndex = this.getCurrentIndex();
            const nextIndex = (currentIndex + 1) % this.originalCount;
            
            console.log(`Navegación NEXT: ${currentIndex} → ${nextIndex}`);
            
            // Si estamos en el último elemento original, hacer transición suave al primer clon
            if (currentIndex === this.originalCount - 1) {
                this.scrollToIndex(this.originalCount, 'smooth');
            } else {
                this.scrollToIndex(nextIndex, 'smooth');
            }
        }

        prev() {
            if (this.isPaused || this.isUserInteracting || this.isScrolling || !this.cardWidth) {
                console.log('Prev bloqueado:', {
                    isPaused: this.isPaused,
                    isUserInteracting: this.isUserInteracting,
                    isScrolling: this.isScrolling,
                    cardWidth: this.cardWidth
                });
                return;
            }
            
            const currentIndex = this.getCurrentIndex();
            let prevIndex = currentIndex - 1;
            
            console.log(`Navegación PREV: ${currentIndex} → ${prevIndex}`);
            
            // Si estamos en el primer elemento, ir al último clon
            if (currentIndex === 0) {
                prevIndex = this.originalCount - 1;
                this.scrollToIndex(this.originalCount + prevIndex, 'smooth');
            } else {
                this.scrollToIndex(prevIndex, 'smooth');
            }
        }

        setupEventListeners() {
            // Scroll events
            let scrollTimeout;
            this.container.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    this.handleInfiniteScroll();
                }, 50);
            }, { passive: true });

            // Mouse/Touch events
            const pauseInteraction = () => {
                this.isUserInteracting = true;
                this.pause();
                console.log('Interacción iniciada - pausado');
            };

            const resumeInteraction = () => {
                this.isUserInteracting = false;
                setTimeout(() => {
                    if (!this.isPaused) {
                        this.startAutoPlay();
                        console.log('Interacción terminada - reanudado');
                    }
                }, 1000);
            };

            this.container.addEventListener('mousedown', pauseInteraction);
            this.container.addEventListener('touchstart', pauseInteraction, { passive: true });
            
            document.addEventListener('mouseup', resumeInteraction);
            document.addEventListener('touchend', resumeInteraction, { passive: true });

            // Hover events
            this.container.addEventListener('mouseenter', () => {
                this.pause();
                console.log('Hover - pausado');
            });
            this.container.addEventListener('mouseleave', () => {
                if (!this.isUserInteracting) {
                    this.startAutoPlay();
                    console.log('Hover terminado - reanudado');
                }
            });

            // Visibility change
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pause();
                    console.log('Página oculta - pausado');
                } else {
                    if (!this.isUserInteracting && !this.isPaused) {
                        this.startAutoPlay();
                        console.log('Página visible - reanudado');
                    }
                }
            });

            // Navigation buttons - CORREGIDO CON VERIFICACIÓN
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Botón PREV clickeado');
                    this.pause();
                    this.prev();
                    this.restartAutoPlayDelayed();
                });
            } else {
                console.warn('Botón PREV no encontrado');
            }

            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Botón NEXT clickeado');
                    this.pause();
                    this.next();
                    this.restartAutoPlayDelayed();
                });
            } else {
                console.warn('Botón NEXT no encontrado');
            }

            // Keyboard navigation
            this.container.setAttribute('tabindex', '0');
            this.container.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.pause();
                    this.prev();
                    this.restartAutoPlayDelayed();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.pause();
                    this.next();
                    this.restartAutoPlayDelayed();
                }
            });

            // Window resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    console.log('Redimensionando - actualizando medidas');
                    this.updateMeasurements();
                    this.handleInfiniteScroll();
                }, 250);
            });
        }

        startAutoPlay() {
            this.stopAutoPlay();
            this.timerId = setInterval(() => {
                this.next();
            }, this.AUTOPLAY_DELAY);
            console.log('AutoPlay iniciado');
        }

        stopAutoPlay() {
            if (this.timerId) {
                clearInterval(this.timerId);
                this.timerId = null;
                console.log('AutoPlay detenido');
            }
        }

        pause() {
            this.isPaused = true;
            this.stopAutoPlay();
        }

        resume() {
            this.isPaused = false;
            this.startAutoPlay();
        }

        restartAutoPlayDelayed() {
            setTimeout(() => {
                if (!this.isPaused) {
                    this.startAutoPlay();
                    console.log('AutoPlay reiniciado');
                }
            }, this.AUTOPLAY_DELAY);
        }
    }

    // INICIALIZACIÓN MEJORADA CON MANEJO DE ERRORES
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Cargado - Inicializando sliders...');
        
        let slidersInitialized = 0;

        // Slider de Productos Populares
        try {
            const sliderMasComprados = new InfiniteSlider('cardsContainer', 'prevBtn', 'nextBtn');
            if (sliderMasComprados.container) {
                window.sliderMasComprados = sliderMasComprados;
                slidersInitialized++;
                console.log('✅ Slider Productos Populares inicializado');
            }
        } catch (error) {
            console.error('❌ Error en Slider Productos Populares:', error);
        }

        // Slider de Novedades
        try {
            const sliderNovedades = new InfiniteSlider('cardsContainerNovedades', 'prevBtnNovedades', 'nextBtnNovedades');
            if (sliderNovedades.container) {
                window.sliderNovedades = sliderNovedades;
                slidersInitialized++;
                console.log('✅ Slider Novedades inicializado');
            }
        } catch (error) {
            console.error('❌ Error en Slider Novedades:', error);
        }

        console.log(`🎯 Sliders inicializados: ${slidersInitialized}/2`);

        // ========== RESTO DEL CÓDIGO (MANTENER IGUAL) ==========
        // CONFIGURACIÓN INICIAL
        const header = document.querySelector(".bg-banner");
        const navbar = document.querySelector(".navbar");
        if (header && navbar) {
            document.body.style.paddingTop = (header.offsetHeight + navbar.offsetHeight) + "px";
        }

        // SISTEMA DE BÚSQUEDAS UNIFICADO
        const searchRoutes = {
            'inicio': 'index.php', 'home': 'index.php', 'productos': 'productos.php',
            'ofertas': 'ofertas.html', 'promociones': 'ofertas.html', 'contacto': 'contacto.html',
            'nosotros': 'nosotros.html', 'quienes somos': 'nosotros.html'
        };

        const normalize = (str) => str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

        function handleSearch(searchTerm, isGlobal = false) {
            const q = normalize(searchTerm);
            if (!q) return false;

            if (isGlobal) {
                window.location.href = `productos.php?search=${encodeURIComponent(q)}`;
                return true;
            }

            if (searchRoutes[q]) {
                window.location.href = searchRoutes[q];
                return true;
            }

            for (const key in searchRoutes) {
                if (q.includes(key)) {
                    window.location.href = searchRoutes[key];
                    return true;
                }
            }

            alert('No se encontró coincidencia. Prueba con: productos, ofertas, contacto, nosotros…');
            return false;
        }

        // Configurar buscadores
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        if (searchForm && searchInput) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleSearch(searchInput.value);
            });
        }

        document.querySelectorAll('nav form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const input = this.querySelector('input[type="search"]');
                if (input) handleSearch(input.value.trim(), true);
            });
        });

        // CONFIGURACIÓN WHATSAPP
        const hora = new Date().getHours();
        let saludo = "¡Buenas noches";
        if (hora >= 6 && hora < 12) saludo = "¡Buenos días";
        else if (hora >= 12 && hora < 19) saludo = "¡Buenas tardes";

        const texto = `${saludo}, Studio & Progetto!\n\nQuisiera más información sobre:\n1) Catálogo completo\n2) Precios y disponibilidad\n3) Servicios (impresión, escaneo, anillado)\n4) Promociones activas\n5) Dirección y horario\n\nGracias, quedo atent@ a su respuesta.`;

        const whatsappBtn = document.getElementById("whatsapp-btn");
        if (whatsappBtn) {
            whatsappBtn.href = `https://wa.me/51939275406?text=${encodeURIComponent(texto)}`;
        }

        // MODAL DE CONSENTIMIENTO
        const consentModal = document.getElementById('consent-modal');
        const modalOverlay = document.getElementById('modal-overlay');
        const btnAccept = document.getElementById('btn-accept');
        const btnDecline = document.getElementById('btn-decline');

        if (localStorage.getItem('consentShown') !== 'true') {
            setTimeout(() => {
                if (consentModal && modalOverlay) {
                    consentModal.style.display = 'block';
                    modalOverlay.style.display = 'block';
                }
            }, 2000);
        }

        if (btnAccept) {
            btnAccept.addEventListener('click', () => {
                if (consentModal && modalOverlay) {
                    consentModal.style.display = 'none';
                    modalOverlay.style.display = 'none';
                    localStorage.setItem('consentShown', 'true');
                    localStorage.setItem('consentAccepted', 'true');
                }
            });
        }

        if (btnDecline) {
            btnDecline.addEventListener('click', () => {
                if (consentModal && modalOverlay) {
                    consentModal.style.display = 'none';
                    modalOverlay.style.display = 'none';
                    localStorage.setItem('consentShown', 'true');
                    localStorage.setItem('consentAccepted', 'false');
                    alert('No se pueden realizar transacciones. Puedes seguir navegando como visitante.');
                }
            });
        }

        // ANIMACIONES
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('active');
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom').forEach(observer.observe.bind(observer));

        // UTILIDADES
        window.cerrarSidebarMenu = function() {
            const sidebar = document.getElementById('sidebarMenu');
            if (sidebar && sidebar.classList.contains('show')) {
                new bootstrap.Collapse(sidebar).hide();
            }
        };
    });

    // PRELOADER
    jQuery(window).on("load", function() {
        setTimeout(() => {
            jQuery("#preloader").fadeOut(700);
            jQuery("#overlayer").fadeOut(700);
        }, 1500);
    });

    document.addEventListener("DOMContentLoaded", function() {
        jQuery("#preloader, #overlayer").show();
    });
</script>

</body>
</html>