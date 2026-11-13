<?php
// productos.php
include 'conexion.php';

// Obtener todas las categorías activas
$sql_categorias = 'SELECT id_categoria, nombre FROM categorias WHERE activo = 1 ORDER BY nombre';
$result_categorias = $conexion->query($sql_categorias);
$categorias = $result_categorias->fetch_all(MYSQLI_ASSOC);

// ----- CONFIGURACIÓN DE PAGINACIÓN -----
$productos_por_pagina = 30;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) && $_GET['pagina'] > 0 ? (int) $_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $productos_por_pagina;

// ----- PARÁMETROS -----
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$order = isset($_GET['order']) ? $_GET['order'] : 'name-asc';

// ----- FILTRO DE CATEGORÍAS -----
$selected_cats = [];
if (isset($_GET['cat'])) {
    $selected_cats = array_map('intval', (array) $_GET['cat']);
    $selected_cats = array_filter($selected_cats, fn ($v) => $v > 0);
}

$cat_filter_sql = '';
if (! empty($selected_cats)) {
    $ids_list = implode(',', $selected_cats);
    $cat_filter_sql = " AND id_categoria IN ($ids_list)";
}

// ----- FILTRO DE PRECIOS -----
$price_filter_sql = '';
if ($min_price !== null) {
    $price_filter_sql .= ' AND precio_venta >= '.$conexion->real_escape_string($min_price);
}
if ($max_price !== null) {
    $price_filter_sql .= ' AND precio_venta <= '.$conexion->real_escape_string($max_price);
}

// ----- ORDEN -----
$order_sql = match ($order) {
    'price-asc' => ' ORDER BY precio_venta ASC',
    'price-desc' => ' ORDER BY precio_venta DESC',
    'name-desc' => ' ORDER BY nombre DESC',
    default => ' ORDER BY nombre ASC',
};

// ----------------------------------------------------------------
// 🔹 CONSULTA TOTAL DE PRODUCTOS FILTRADOS (PARA PAGINACIÓN REAL)
// ----------------------------------------------------------------
$base_where = ' FROM productos WHERE activo = 1'.$cat_filter_sql.$price_filter_sql;
$params = [];
$bind_types = '';

if ($q !== '') {
    $search = '%'.$q.'%';
    $base_where .= ' AND (nombre COLLATE utf8mb4_unicode_ci LIKE ? 
                      OR descripcion COLLATE utf8mb4_unicode_ci LIKE ? 
                      OR codigo_barra COLLATE utf8mb4_unicode_ci LIKE ?)';
    $params = [$search, $search, $search];
    $bind_types = 'sss';
}

// --- Calcular total de productos filtrados ---
$count_sql = 'SELECT COUNT(*) AS total'.$base_where;
$count_stmt = $conexion->prepare($count_sql);

if (! empty($params)) {
    $count_stmt->bind_param($bind_types, ...$params);
}
$count_stmt->execute();
$count_res = $count_stmt->get_result();
$total_row = $count_res->fetch_assoc();
$total = $total_row ? (int) $total_row['total'] : 0;
$count_stmt->close();

$total_paginas = $total > 0 ? ceil($total / $productos_por_pagina) : 1;
if ($pagina > $total_paginas && $total_paginas > 0) {
    $pagina = $total_paginas;
    $inicio = ($pagina - 1) * $productos_por_pagina;
}

// ----------------------------------------------------------------
// 🔹 CONSULTA PRINCIPAL (PRODUCTOS PAGINADOS) - CORREGIDO
// ----------------------------------------------------------------
// QUITAR el "FROM" del inicio ya que $base_where ya lo incluye
$sql = 'SELECT id_producto, codigo_barra, nombre, descripcion, precio_venta, stock, id_categoria'
     .$base_where.$order_sql.' LIMIT ?, ?';

$stmt = $conexion->prepare($sql);

if (! $stmt) {
    // Debug: mostrar error si la preparación falla
    exit('Error en la consulta: '.$conexion->error);
}

// unir parámetros dinámicos
if (! empty($params)) {
    $bind_types .= 'ii';
    $params[] = $inicio;
    $params[] = $productos_por_pagina;

    if (! $stmt->bind_param($bind_types, ...$params)) {
        exit('Error al bindear parámetros: '.$stmt->error);
    }
} else {
    if (! $stmt->bind_param('ii', $inicio, $productos_por_pagina)) {
        exit('Error al bindear límites: '.$stmt->error);
    }
}

if (! $stmt->execute()) {
    exit('Error al ejecutar consulta: '.$stmt->error);
}

$result = $stmt->get_result();
$productos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ----------------------------------------------------------------
// 🔹 FUNCIÓN PARA IMAGEN
// ----------------------------------------------------------------
function ruta_imagen($codigo)
{
    $base = __DIR__.'/imagenes/';
    $jpg = $base.$codigo.'.jpg';
    $png = $base.$codigo.'.png';
    if (file_exists($jpg)) {
        return 'imagenes/'.$codigo.'.jpg';
    }
    if (file_exists($png)) {
        return 'imagenes/'.$codigo.'.png';
    }

    return 'imagenes/sin_imagen.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <!-- 🔹 Título (máx. 60 caracteres, incluye palabras clave) -->
  <title>Studio & Progetto - Productos</title>
  <!-- 🔹 Descripción (máx. 160 caracteres, clave para Google) -->
  <meta name="description"
    content="Tienda online de útiles escolares, universitarios y de oficina. Productos de arquitectura, diseño y servicios de impresión en Studio & Progetto.">
  <!-- 🔹 Palabras clave (ya no las usa Google, pero sí buscadores pequeños) -->
  <meta name="keywords"
    content="útiles escolares, útiles universitarios, oficina, productos de arquitectura, diseño, servicios de impresión, papelería, librería, Studio & Progetto">

  <!-- 🔹 Autor (opcional, pero útil) -->
  <meta name="author" content="Nesch0811">

  <!-- 🔹 Open Graph (para compartir en redes sociales) -->
  <meta property="og:title" content="Studio & Progetto">
  <meta property="og:description"
    content="Encuentra útiles escolares, universitarios, de oficina, arquitectura y diseño. También ofrecemos servicios variados.">
  <meta property="og:image" content="https://tusitio.com/imagenes/logosinfondo.png">
  <meta property="og:url" content="https://tusitio.com">
  <meta property="og:type" content="website">

  <link rel="icon" href="imagenes/logosinfondo.png" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/main.css">

</head>

<style>
  .products-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    padding: 2rem;
    padding-top: 0.5rem;
    max-width: 1400px;
    margin: 3rem auto 3rem;
  }

  .filters-sidebar {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    height: fit-content;
    position: sticky;
    top: 120px;
  }

  .filters-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #2c3e50;
  }

  .filter-group {
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
  }

  .filter-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
  }

  .filter-group-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 1rem;
    font-weight: 600;
    color: #34495e;
    cursor: pointer;
    margin-bottom: 0.5rem;
  }

  .filter-group-title i {
    transition: transform 0.3s ease;
  }

  .filter-group-title.active i {
    transform: rotate(180deg);
  }

  .filter-group-content {
    display: none;
  }

  .filter-group-content.show {
    display: block;
  }

  .category-filters {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .category-filter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
  }

  .category-filter input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .category-filter label {
    flex: 1;
    cursor: pointer;
    font-size: 0.9rem;
    color: #555;
  }

  .category-count {
    font-size: 0.8rem;
    color: #888;
    background: #f8f9fa;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
  }

  .price-filter {
    padding: 0.5rem 0;
  }

  .price-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .price-input {
    width: 80px;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
  }

  .price-separator {
    color: #888;
  }

  /* Products Main Area */
  .products-main {
    flex: 1;
  }

  .products-header {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
  }

  .products-header-top {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .products-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
  }

  .products-count {
    font-size: 0.9rem;
    color: #7f8c8d;
    background: #f8f9fa;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
  }

  .products-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
  }

  .search-controls {
    flex: 1;
    min-width: 250px;
  }

  .search-box {
    position: relative;
    display: flex;
    align-items: center;
  }

  .search-icon {
    position: absolute;
    left: 1rem;
    color: #7f8c8d;
    z-index: 2;
  }

  .search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
  }

  .search-input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
  }

  .sort-controls {
    min-width: 200px;
  }

  .sort-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    transition: border-color 0.3s ease;
  }

  .sort-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
  }

  /* Products Grid */
  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
  }

  .product-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
  }

  .product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
  }

  .product-image {
    text-align: center;
    margin-bottom: 1rem;
    font-size: 3rem;
    color: #3498db;
  }

  .product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    line-height: 1.4;
  }

  .product-brand {
    font-size: 0.9rem;
    color: #7f8c8d;
    margin-bottom: 1rem;
  }

  .product-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: #27ae60;
    margin-bottom: 1rem;
  }

  /* PAGINACIÓN RESPONSIVA */
  .pagination {
    gap: 0.25rem;
  }

  .page-link {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    font-weight: 500;
    transition: all 0.3s ease;
    min-width: 44px;
    text-align: center;
  }

  .page-link:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
  }

  .page-item.active .page-link {
    background-color: #3498db;
    border-color: #3498db;
    color: white;
  }

  /* Responsive */
  @media (max-width: 968px) {
    .products-container {
      grid-template-columns: 1fr;
      padding: 1rem;
    }

    .filters-sidebar {
      position: static;
      margin-bottom: 2rem;
    }

    .products-controls {
      flex-direction: column;
      align-items: stretch;
    }

    .search-controls,
    .sort-controls {
      min-width: 100%;
    }
  }

  @media (max-width: 768px) {
    .products-container {
      grid-template-columns: 1fr;
      padding: 1rem;
      margin: 6rem auto 3rem;
    }

    .products-header-top {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .pagination {
      gap: 0.15rem;
    }
    
    .page-link {
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
      min-width: 40px;
    }
  }

  @media (max-width: 576px) {
    .products-grid {
      grid-template-columns: 1fr;
    }

    .products-header-top {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }
    
    .page-link {
      padding: 0.4rem 0.6rem;
      font-size: 0.8rem;
      min-width: 36px;
    }
    
    .pagination .page-item:not(.active) .page-link {
      padding: 0.4rem 0.5rem;
    }
  }
</style>

<body class="products-page" style="background-color: white;">

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
        <a href="productos.php?cat[]=6">
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
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=2">
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
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=9">
          <i class="fas fa-graduation-cap me-3"></i>COLECCIÓN ACADÉMICA
        </a>
        <div class="sidebar-submenu" id="coleccionacademica">
          <a href="productos.php?cat[]=9">Bitácora Canson</a>
          <a href="productos.php?cat[]=9">Block Canson</a>
          <a href="productos.php?cat[]=9">Folder Escolar</a>
          <a href="productos.php?cat[]=9">Tablero Acrílico</a>
          <a href="productos.php?cat[]=9">Tablero de Madera</a>
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=3">
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
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=7">
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
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=8">
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
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=1">
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
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=5">
          <i class="fas fa-tree me-3"></i>MADERAS
        </a>
        <div class="sidebar-submenu" id="maderas">
          <a href="productos.php?cat[]=5">Madera Balsa</a>
          <a href="productos.php?cat[]=5">Madera Caoba</a>
          <a href="productos.php?cat[]=5">Madera Caoba Laminada</a>
          <a href="productos.php?cat[]=5">Varilla Cuadrada</a>
        </div>
      </li>

      <li>
        <a href="productos.php?cat[]=4">
          <i class="fas fa-file-alt me-3"></i>PAPELERÍA
        </a>
        <div class="sidebar-submenu" id="papeleria">
          <a href="productos.php?cat[]=4">Block de dibujo A4 / A3</a>
          <a href="productos.php?cat[]=4">Cartulinas de colores</a>
          <a href="productos.php?cat[]=4">Cartulina Escolar</a>
          <a href="productos.php?cat[]=4">Papel de Regalo</a>
          <a href="productos.php?cat[]=4">Papeles Bond</a>
          <a href="productos.php?cat[]=4">Papeles Carta</a>
        </div>
      </li>

      <li>
        <a href="productos.php">
          <i class="fas fa-box-open me-3"></i>TODOS LOS PRODUCTOS
        </a>
      </li>
    </ul>
  </div>

  <!-- Contenido principal de productos -->
  <main class="products-container">
    <!-- Sidebar de filtros -->
    <aside class="filters-sidebar" id="filtersSidebar">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="filters-title m-0">
          <i class="fas fa-filter"></i>
          Filtros
        </h2>
        <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-times"></i> Limpiar
        </button>
      </div>

      <!-- Filtro de Categorías -->
      <div class="filter-group">
        <div class="filter-group-title" onclick="toggleFilterGroup(this)">
          <span>Categorías</span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="filter-group-content show">
          <div class="category-filters">
            <?php foreach ($categorias as $categoria) {
                $checked = in_array($categoria['id_categoria'], $selected_cats) ? 'checked' : '';
                ?>
              <div class="category-filter">
                <input type="checkbox"
                  id="cat<?php echo $categoria['id_categoria']; ?>"
                  name="cat[]"
                  value="<?php echo $categoria['id_categoria']; ?>"
                  <?php echo $checked; ?>>
                <label for="cat<?php echo $categoria['id_categoria']; ?>">
                  <?php echo htmlspecialchars($categoria['nombre']); ?>
                </label>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>

      <!-- Filtro por precio -->
      <div class="filter-group">
        <div class="filter-group-title" onclick="toggleFilterGroup(this)">
          <span>Precio</span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="filter-group-content show">
          <div class="price-filter">
            <div class="price-range">
              <input type="number" class="price-input" id="minPrice" placeholder="Mín. S/" min="0" step="0.01"
                     value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
              <span class="price-separator">-</span>
              <input type="number" class="price-input" id="maxPrice" placeholder="Máx. S/" min="0" step="0.01"
                     value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Área principal de productos -->
    <section class="products-main">
      <!-- Header de productos -->
      <div class="products-header">
        <div class="products-header-top">
          <h1 class="products-title">
            <i class="fas fa-box-open"></i>
            Nuestros Productos
          </h1>
          <span class="products-count" id="productsCount"><?php echo $total; ?> producto<?php echo $total !== 1 ? 's' : ''; ?></span>
        </div>

        <!-- Controles de productos -->
        <div class="products-controls">
          <div class="search-controls">
            <div class="search-box">
              <i class="fas fa-search search-icon"></i>
              <input type="text" class="search-input" id="productSearch" placeholder="Buscar productos..." value="<?php echo isset($q) ? htmlspecialchars($q, ENT_QUOTES) : ''; ?>">
            </div>
          </div>

          <select class="sort-select" id="sortSelect">
            <option value="name-asc" <?= $order === 'name-asc' ? 'selected' : '' ?>>Nombre A-Z</option>
            <option value="name-desc" <?= $order === 'name-desc' ? 'selected' : '' ?>>Nombre Z-A</option>
            <option value="price-asc" <?= $order === 'price-asc' ? 'selected' : '' ?>>Precio menor a mayor</option>
            <option value="price-desc" <?= $order === 'price-desc' ? 'selected' : '' ?>>Precio mayor a menor</option>
          </select>
        </div>
      </div>

      <!-- Grid de productos -->
      <div class="products-grid" id="productsGrid">
        <?php if (count($productos) === 0) { ?>
          <div class="text-muted">No hay productos disponibles.</div>
        <?php } else { ?>
          <?php foreach ($productos as $p) {
              $id = (int) $p['id_producto'];
              $nombre = htmlspecialchars($p['nombre']);
              $descripcion = nl2br(htmlspecialchars($p['descripcion']));
              $precio = number_format($p['precio_venta'], 2);
              $stock = (int) $p['stock'];
              $img = ruta_imagen($p['codigo_barra']);
              // data attributes para JS (wishlist y filtros)
              $data_name = htmlspecialchars($p['nombre'], ENT_QUOTES);
              $data_price = htmlspecialchars($p['precio_venta'], ENT_QUOTES);
              $data_image = htmlspecialchars($img, ENT_QUOTES);
              ?>
            <article class="product-card" data-product-id="<?php echo $id; ?>" data-product-name="<?php echo $data_name; ?>" data-product-price="<?php echo $data_price; ?>" data-product-image="<?php echo $data_image; ?>" data-product-brand="">
              <div class="product-image">
                <img src="<?php echo $img; ?>" alt="<?php echo $nombre; ?>" class="img-fluid">
              </div>
              <div class="product-name"><?php echo $nombre; ?></div>
              <div class="product-brand small text-muted">&nbsp;</div>
              <div class="product-price">S/ <?php echo $precio; ?></div>

              <div class="d-flex justify-content-between align-items-center mt-3 gap-3">
                <button class="wishlist-btn btn btn-outline-danger" data-product-id="<?php echo $id; ?>" title="Agregar a lista de deseos">
                  <i class="far fa-heart"></i>
                </button>

                <button class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#modal<?php echo $id; ?>">Ver más</button>
              </div>
            </article>

            <!-- Modal del producto -->
            <div class="modal fade" id="modal<?php echo $id; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title"><?php echo $nombre; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row">
                      <div class="col-md-5 text-center mb-3">
                        <img src="<?php echo $img; ?>" alt="<?php echo $nombre; ?>" class="img-fluid rounded" style="max-height:320px; object-fit:contain;">
                      </div>
                      <div class="col-md-7">
                        <p class="mb-2"><strong>Descripción:</strong></p>
                        <p class="small text-muted mb-3"><?php echo $descripcion; ?></p>

                     
                        <p class="mb-1"><strong>Precio:</strong> S/ <?php echo $precio; ?></p>
                        <p class="mb-1"><strong>Stock disponible:</strong> <?php echo $stock; ?></p>

                        <div class="mt-3">
                          <button class="btn btn-outline-danger wishlist-btn" data-product-id="<?php echo $id; ?>"><i class="far fa-heart"></i> Favorito</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
        <?php } ?>
      </div>

      <!-- PAGINACIÓN MEJORADA Y RESPONSIVA -->
      <?php if ($total_paginas > 1) { ?>
        <nav aria-label="Paginación de productos" class="mt-4">
          <ul class="pagination justify-content-center flex-wrap gap-1">
            <?php
                // Construir base URL con todos los parámetros excepto página
                $base_q = '';
          $params = $_GET;
          unset($params['pagina']);
          if (! empty($params)) {
              $base_q = http_build_query($params).'&';
          }
          ?>
            
            <?php if ($pagina > 1) { ?>
              <li class="page-item">
                <a class="page-link px-3 py-2" href="?<?= $base_q ?>pagina=<?= $pagina - 1 ?>" aria-label="Anterior">
                  &laquo; Anterior
                </a>
              </li>
            <?php } ?>

            <?php
          // Mostrar máximo 5 páginas alrededor de la actual
          $inicio = max(1, $pagina - 2);
          $fin = min($total_paginas, $pagina + 2);

          for ($i = $inicio; $i <= $fin; $i++) { ?>
              <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                <a class="page-link px-3 py-2" href="?<?= $base_q ?>pagina=<?= $i ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php } ?>

            <?php if ($pagina < $total_paginas) { ?>
              <li class="page-item">
                <a class="page-link px-3 py-2" href="?<?= $base_q ?>pagina=<?= $pagina + 1 ?>" aria-label="Siguiente">
                  Siguiente &raquo;
                </a>
              </li>
            <?php } ?>
          </ul>
        </nav>
      <?php } ?>
    </section>
  </main>

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
              <!-- Versión para TABLET (md) más pequeña -->
              <span class="d-none d-md-inline d-lg-none">
                <span class="fs-6">Studio &</span><br>
                <span class="fs-6">Progetto</span>
              </span>

              <!-- Versión para DESKTOP (lg+) tamaño normal -->
              <span class="d-none d-lg-inline">
                <span class="fs-4">Studio &</span><br>
                <span class="fs-3">Progetto</span>
              </span>

              <!-- (opcional) Versión para móvil xs/sm, si la necesitas -->
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

  <script src="js/main.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // BOTÓN LIMPIAR FILTROS
    document.addEventListener('DOMContentLoaded', function() {
      const clearBtn = document.getElementById('clearFilters');
      if (clearBtn) {
        clearBtn.addEventListener('click', function() {
          // Limpiar checkboxes de categorías
          document.querySelectorAll('input[name="cat[]"]').forEach(cb => {
            cb.checked = false;
          });
          
          // Limpiar inputs de precio
          const minPrice = document.getElementById('minPrice');
          const maxPrice = document.getElementById('maxPrice');
          if (minPrice) minPrice.value = '';
          if (maxPrice) maxPrice.value = '';
          
          // Resetear orden a predeterminado
          const sortSelect = document.getElementById('sortSelect');
          if (sortSelect) sortSelect.value = 'name-asc';
          
          // Limpiar búsqueda
          const searchInput = document.getElementById('productSearch');
          if (searchInput) searchInput.value = '';
          
          // Redirigir a productos sin filtros
          window.location.href = 'productos.php';
        });
      }

      // Función para toggle de grupos de filtros
      window.toggleFilterGroup = function(element) {
        const content = element.nextElementSibling;
        content.classList.toggle('show');
        element.classList.toggle('active');
      };

      // Función para mostrar notificaciones
      function showWishlistFeedback(productName, positive = true) {
        const n = document.createElement('div');
        n.style.cssText = `position:fixed;top:20px;right:20px;background:${positive ? '#28a745' : '#dc3545'};color:#fff;padding:12px 16px;border-radius:6px;z-index:9999;font-weight:600;box-shadow:0 6px 18px rgba(0,0,0,.12);transform:translateX(110%);transition:transform .28s ease;`;
        n.innerHTML = `${positive ? '<i class="fas fa-heart me-2"></i>' : '<i class="fas fa-heart-broken me-2"></i>'} ${productName}`;
        document.body.appendChild(n);
        setTimeout(() => n.style.transform = 'translateX(0)', 80);
        setTimeout(() => {
          n.style.transform = 'translateX(110%)';
          setTimeout(() => n.remove(), 300);
        }, 2600);
      }

      // Función reutilizable para búsqueda global
      function performGlobalSearch(searchTerm) {
        const query = searchTerm.trim();

        if (query === '') {
          window.location.href = 'productos.php';
          return;
        }

        const encodedQuery = encodeURIComponent(query);
        window.location.href = 'productos.php?q=' + encodedQuery;
      }

      // Cuando cambie cualquier checkbox de categoría, reconstruimos la URL y recargamos
      document.querySelectorAll('input[name="cat[]"]').forEach(cb => {
        cb.addEventListener('change', function() {
          const params = new URLSearchParams();
          const qv = document.getElementById('productSearch')?.value.trim();
          if (qv) params.append('q', qv);
          document.querySelectorAll('input[name="cat[]"]:checked').forEach(ch => params.append('cat[]', ch.value));
          
          const minPrice = document.getElementById('minPrice')?.value;
          const maxPrice = document.getElementById('maxPrice')?.value;
          if (minPrice) params.set('min_price', minPrice);
          if (maxPrice) params.set('max_price', maxPrice);
          
          const sortSelect = document.getElementById('sortSelect')?.value;
          if (sortSelect) params.set('order', sortSelect);
          
          const qs = params.toString();
          window.location.href = 'productos.php' + (qs ? ('?' + qs) : '');
        });
      });

      // Buscador principal de productos
      const searchInput = document.getElementById('productSearch');
      if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            performGlobalSearch(searchInput.value);
          }
        });
      }

      // Filtros de precio y orden
      const sortSelect = document.getElementById('sortSelect');
      const priceMin = document.getElementById('minPrice');
      const priceMax = document.getElementById('maxPrice');

      function applyFilters() {
        const params = new URLSearchParams(window.location.search);

        // Mantener búsqueda
        const q = document.getElementById('productSearch')?.value.trim();
        if (q) params.set('q', q);
        else params.delete('q');

        // Categorías seleccionadas
        params.delete('cat[]');
        document.querySelectorAll('input[name="cat[]"]:checked').forEach(ch => params.append('cat[]', ch.value));

        // Filtros de precio
        const min = priceMin?.value.trim();
        const max = priceMax?.value.trim();
        if (min) params.set('min_price', min);
        else params.delete('min_price');
        if (max) params.set('max_price', max);
        else params.delete('max_price');

        // Tipo de orden
        const order = sortSelect?.value;
        if (order) params.set('order', order);

        // Reiniciar a página 1
        params.delete('pagina');

        // Recargar
        window.location.href = 'productos.php?' + params.toString();
      }

      if (sortSelect) {
        sortSelect.addEventListener('change', applyFilters);
      }

      [priceMin, priceMax].forEach(el => {
        if (!el) return;
        el.addEventListener('keydown', e => {
          if (e.key === 'Enter') {
            e.preventDefault();
            applyFilters();
          }
        });
        el.addEventListener('blur', applyFilters);
      });

      // Wishlist functionality
      function getWishlist() {
        return JSON.parse(localStorage.getItem('wishlist')) || [];
      }

      function saveWishlist(w) {
        localStorage.setItem('wishlist', JSON.stringify(w));
      }

      function updateWishlistCounter() {
        const wishlist = getWishlist();
        const count = wishlist.reduce((acc, item) => acc + (item.quantity || 1), 0);
        const mobile = document.getElementById('wishlistCountMobile');
        const desktop = document.getElementById('wishlistCountDesktop');
        if (mobile) mobile.textContent = count;
        if (desktop) desktop.textContent = count;
      }

      function updateHeartButton(productId, inWishlist) {
        document.querySelectorAll(`.wishlist-btn[data-product-id="${productId}"]`).forEach(btn => {
          const icon = btn.querySelector('i');
          if (inWishlist) {
            icon.className = 'fas fa-heart';
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-danger');
          } else {
            icon.className = 'far fa-heart';
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-outline-danger');
          }
        });
      }

      function addToWishlist(productId) {
        const card = document.querySelector(`[data-product-id="${productId}"]`);
        if (!card) return;
        const item = {
          id: parseInt(productId),
          name: card.dataset.productName || '',
          price: parseFloat(card.dataset.productPrice) || 0,
          image: card.dataset.productImage || '',
          quantity: 1
        };
        let w = getWishlist();
        const exists = w.find(x => x.id === item.id);
        if (exists) exists.quantity = (exists.quantity || 0) + 1;
        else w.push(item);
        saveWishlist(w);
        updateWishlistCounter();
        updateHeartButton(productId, true);
        showWishlistFeedback(item.name, true);
      }

      function removeFromWishlist(productId) {
        let w = getWishlist();
        const removed = w.find(x => x.id === parseInt(productId));
        w = w.filter(x => x.id !== parseInt(productId));
        saveWishlist(w);
        updateWishlistCounter();
        updateHeartButton(productId, false);
        if (removed) showWishlistFeedback(removed.name, false);
      }

      // Inicializar wishlist
      updateWishlistCounter();
      getWishlist().forEach(i => updateHeartButton(i.id, true));

      // Delegación: capturar clicks en cualquier botón wishlist
      document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.wishlist-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const id = btn.getAttribute('data-product-id');
        if (!id) return;
        const exists = getWishlist().some(x => x.id === parseInt(id));
        if (exists) removeFromWishlist(id);
        else addToWishlist(id);
      });
    });
  </script>
</body>
</html>