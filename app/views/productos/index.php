<?php
// Verificar que las variables necesarias estén definidas
if (!isset($productos)) $productos = [];
if (!isset($datos_paginacion)) $datos_paginacion = ['total' => 0, 'pagina_actual' => 1, 'total_paginas' => 1];
if (!isset($categorias)) $categorias = [];
if (!isset($almacenes)) $almacenes = [];
if (!isset($proveedores)) $proveedores = [];

// Obtener parámetros actuales para mantenerlos en los filtros
$buscar_actual = $_GET['buscar'] ?? '';
$pagina_actual = $_GET['pagina'] ?? 1;
$categorias_actual = isset($_GET['categorias']) ? (array)$_GET['categorias'] : [];
$precio_min_actual = $_GET['precio_min'] ?? '';
$precio_max_actual = $_GET['precio_max'] ?? '';
$orden_actual = $_GET['orden'] ?? 'nombre ASC';
$viendo_inactivos = strpos($_GET['url'] ?? '', 'inactivos') !== false;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Inventario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .dropdown-menu {
            z-index: 2000;
        }

        .products-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }

        @media (min-width: 576px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (min-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        @media (min-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(8, 1fr);
            }
        }

        .product-card-wrapper {
            position: relative;
        }

        .product-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .product-card {
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0.75rem;
        }

        .product-card .card-title {
            font-size: 0.9rem;
            line-height: 1.3;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.6rem;
        }

        .product-card .price {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .product-card .card-footer {
            padding: 0.75rem;
            background: white;
        }

        .product-card .card-footer .btn {
            padding: 0.35rem 0.45rem;
            font-size: 0.8rem;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .stock-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 0.4rem;
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
            position: relative;
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
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .suggestions-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .suggestion-item:hover {
            background-color: #f8f9fa;
        }

        .suggestion-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .suggestion-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .suggestion-price {
            color: #28a745;
            font-weight: 600;
        }

        .suggestion-type {
            font-size: 0.7rem;
            background: #e9ecef;
            padding: 0.1rem 0.4rem;
            border-radius: 0.25rem;
            display: inline-block;
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
        }

        .image-container {
            height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            padding: 0.5rem;
        }

        .image-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .search-loading {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
            display: none;
        }

        .btn-group-header {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .products-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-controls,
            .sort-controls {
                min-width: 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 0.75rem;
            }

            .product-card .card-title {
                font-size: 0.85rem;
                height: 2.4rem;
            }

            .product-card .price {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="m-0 text-primary"><i class="fas fa-box-open"></i> Gestión de Inventario</h2>
                <div class="btn-group-header">
                    <div class="btn-group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-tags"></i> Generar Etiquetas
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>producto/generarEtiquetas?todos=1" id="generarTodasEtiquetas" target="_blank">
                                    <i class="fas fa-print"></i> Todas las etiquetas
                                </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="generarSeleccionadas">
                                    <i class="fas fa-check-square"></i> Seleccionadas (0)
                                </a></li>
                        </ul>
                    </div>
                    <?php if ($viendo_inactivos): ?>
                        <a href="<?php echo BASE_URL; ?>producto" class="btn btn-info">Ver Activos</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>producto/index/inactivos" class="btn btn-secondary">Ver Inactivos</a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productoModal">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <form method="GET" action="<?php echo BASE_URL; ?>producto<?php echo $viendo_inactivos ? '/index/inactivos' : ''; ?>" class="mb-3" id="filtersForm">
                            <input type="hidden" name="pagina" value="1" id="paginaInput">

                            <div class="products-controls">
                                <div class="search-controls">
                                    <div class="search-box">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" class="search-input" name="buscar" id="searchInput"
                                            placeholder="Buscar productos por nombre, descripción..."
                                            value="<?php echo htmlspecialchars($buscar_actual); ?>"
                                            autocomplete="off">
                                        <i class="fas fa-spinner fa-spin search-loading" id="searchLoading"></i>
                                    </div>
                                    <div class="suggestions-container" id="suggestionsContainer"></div>
                                </div>

                                <div class="sort-controls">
                                    <select class="sort-select" name="orden" id="sortSelect">
                                        <option value="relevancia DESC" <?= $orden_actual === 'relevancia DESC' ? 'selected' : '' ?>>Más relevantes</option>
                                        <option value="nombre ASC" <?= $orden_actual === 'nombre ASC' ? 'selected' : '' ?>>Nombre A-Z</option>
                                        <option value="nombre DESC" <?= $orden_actual === 'nombre DESC' ? 'selected' : '' ?>>Nombre Z-A</option>
                                        <option value="precio ASC" <?= $orden_actual === 'precio ASC' ? 'selected' : '' ?>>Precio menor a mayor</option>
                                        <option value="precio DESC" <?= $orden_actual === 'precio DESC' ? 'selected' : '' ?>>Precio mayor a menor</option>
                                    </select>
                                </div>

                                <button type="button" class="btn btn-outline-secondary" id="seleccionarTodosBtn">
                                    <i class="fas fa-check-double"></i> Seleccionar Todos
                                </button>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </form>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge bg-primary">
                                    <?php echo $datos_paginacion['total']; ?> producto<?php echo $datos_paginacion['total'] !== 1 ? 's' : ''; ?> encontrado<?php echo $datos_paginacion['total'] !== 1 ? 's' : ''; ?>
                                </span>
                                <?php if (!empty($buscar_actual)): ?>
                                    <span class="badge bg-info ms-2">
                                        Búsqueda: "<?php echo htmlspecialchars($buscar_actual); ?>"
                                    </span>
                                <?php endif; ?>
                                <span id="seleccionCount" class="badge bg-secondary ms-2">0 seleccionados</span>
                            </div>
                            <div>
                                Página <?php echo $datos_paginacion['pagina_actual']; ?> de <?php echo $datos_paginacion['total_paginas']; ?>
                            </div>
                        </div>

                        <div class="products-grid">
                            <?php if (!empty($productos) && is_array($productos)): ?>
                                <?php foreach ($productos as $producto): ?>
                                    <?php
                                    $imgSrc = BASE_URL . 'public/img/no-image.png';
                                    $hasValidImage = false;

                                    if (!empty($producto['imagen_path'])) {
                                        if (strpos($producto['imagen_path'], 'http') === 0) {
                                            $imgSrc = $producto['imagen_path'];
                                            $hasValidImage = true;
                                        } else {
                                            $imgSrc = BASE_URL . 'public' . $producto['imagen_path'];
                                            $hasValidImage = true;
                                        }
                                    } elseif (!empty($producto['imagen_url'])) {
                                        $imgSrc = $producto['imagen_url'];
                                        $hasValidImage = true;
                                    }
                                    ?>
                                    <div class="product-card-wrapper">
                                        <?php if (!$viendo_inactivos): ?>
                                            <input type="checkbox" class="product-checkbox" data-id="<?php echo $producto['id_producto']; ?>">
                                        <?php endif; ?>
                                        <div class="card shadow-sm h-100 product-card">
                                            <div class="image-container">
                                                <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                                    class="card-img-top product-image"
                                                    alt="<?php echo htmlspecialchars($producto['nombre'] ?? 'Producto'); ?>"
                                                    data-src="<?php echo htmlspecialchars($imgSrc); ?>"
                                                    data-placeholder="<?php echo BASE_URL . 'public/img/no-image.png'; ?>">
                                            </div>

                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <?php echo htmlspecialchars($producto['nombre'] ?? 'Sin nombre'); ?>
                                                </h6>
                                                <p class="price text-danger m-0">
                                                    <?php echo getMoneda() . number_format($producto['precio_venta'] ?? 0, 2); ?>
                                                </p>
                                                <small class="text-muted card-text">
                                                    Cat: <?php echo htmlspecialchars($producto['nombre_categoria'] ?? 'Sin categoría'); ?>
                                                </small>
                                                <div class="mt-auto pt-2">
                                                    <span class="stock-badge badge bg-<?php echo (!empty($producto['activo'])) ? 'success' : 'danger'; ?>">
                                                        Stock: <?php echo (int)($producto['stock'] ?? 0); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="card-footer d-flex justify-content-between">
                                                <div class="d-flex gap-1">
                                                    <a href="<?php echo BASE_URL; ?>producto/detalle/<?php echo $producto['id_producto'] ?? 0; ?>"
                                                        class="btn btn-sm btn-info" title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-bs-toggle="modal" data-bs-target="#productoModal"
                                                        data-id="<?php echo $producto['id_producto'] ?? 0; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>"
                                                        data-descripcion="<?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?>"
                                                        data-categoria="<?php echo $producto['id_categoria'] ?? ''; ?>"
                                                        data-almacen="<?php echo $producto['id_almacen'] ?? ''; ?>"
                                                        data-proveedor="<?php echo $producto['id_proveedor_preferido'] ?? ''; ?>"
                                                        data-precioventa="<?php echo $producto['precio_venta'] ?? 0; ?>"
                                                        data-preciocompra="<?php echo $producto['precio_compra'] ?? 0; ?>"
                                                        data-stock="<?php echo $producto['stock'] ?? 0; ?>"
                                                        data-stockminimo="<?php echo $producto['stock_minimo'] ?? 0; ?>"
                                                        data-imagen="<?php echo htmlspecialchars($producto['imagen_path'] ?? $producto['imagen_url'] ?? ''); ?>"
                                                        title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>

                                                <div>
                                                    <?php if (!empty($producto['activo'])): ?>
                                                        <a href="<?php echo BASE_URL; ?>producto/cambiarEstado/<?php echo $producto['id_producto'] ?? 0; ?>/0"
                                                            class="btn btn-sm btn-danger action-btn-confirm" title="Desactivar">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?php echo BASE_URL; ?>producto/cambiarEstado/<?php echo $producto['id_producto'] ?? 0; ?>/1"
                                                            class="btn btn-sm btn-success action-btn-confirm" title="Activar">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center p-5">
                                    <?php if (!empty($buscar_actual)): ?>
                                        <h5>No se encontraron productos</h5>
                                        <p class="text-muted">No hay resultados para "<?php echo htmlspecialchars($buscar_actual); ?>"</p>
                                        <button type="button" class="btn btn-primary mt-2" onclick="document.getElementById('searchInput').value=''; document.getElementById('filtersForm').submit();">
                                            <i class="fas fa-times"></i> Limpiar búsqueda
                                        </button>
                                    <?php else: ?>
                                        <h5>No hay productos para mostrar</h5>
                                        <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($datos_paginacion['total_paginas'] > 1): ?>
                            <nav aria-label="Paginación de productos" class="mt-4">
                                <ul class="pagination justify-content-center flex-wrap gap-1">
                                    <?php
                                    $base_params = $_GET;
                                    unset($base_params['pagina']);
                                    $base_query = http_build_query($base_params);
                                    $base_url = BASE_URL . 'producto' . ($viendo_inactivos ? '/index/inactivos' : '') . ($base_query ? '?' . $base_query . '&' : '?');
                                    ?>

                                    <?php if ($datos_paginacion['pagina_actual'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link px-3 py-2" href="<?php echo $base_url; ?>pagina=<?php echo $datos_paginacion['pagina_actual'] - 1; ?>" aria-label="Anterior">
                                                &laquo; Anterior
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $inicio = max(1, $datos_paginacion['pagina_actual'] - 2);
                                    $fin = min($datos_paginacion['total_paginas'], $datos_paginacion['pagina_actual'] + 2);

                                    for ($i = $inicio; $i <= $fin; $i++): ?>
                                        <li class="page-item <?= $i == $datos_paginacion['pagina_actual'] ? 'active' : '' ?>">
                                            <a class="page-link px-3 py-2" href="<?php echo $base_url; ?>pagina=<?php echo $i; ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($datos_paginacion['pagina_actual'] < $datos_paginacion['total_paginas']): ?>
                                        <li class="page-item">
                                            <a class="page-link px-3 py-2" href="<?php echo $base_url; ?>pagina=<?php echo $datos_paginacion['pagina_actual'] + 1; ?>" aria-label="Siguiente">
                                                Siguiente &raquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Agregar Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formProducto" enctype="multipart/form-data" method="post" action="<?php echo BASE_URL; ?>producto/guardar">
                        <input type="hidden" name="id_producto" id="id_producto" value="">
                        <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="descripcion" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="id_categoria" id="id_categoria">
                                    <option value="">Seleccione una categoría</option>
                                    <?php if (!empty($categorias) && is_array($categorias)): ?>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id_categoria']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Almacén <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_almacen" id="id_almacen" required>
                                    <option value="">Seleccione un almacén</option>
                                    <?php if (!empty($almacenes) && is_array($almacenes)): ?>
                                        <?php foreach ($almacenes as $almacen): ?>
                                            <option value="<?php echo $almacen['id']; ?>">
                                                <?php echo htmlspecialchars($almacen['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Proveedor Preferido</label>
                            <select class="form-select" name="id_proveedor_preferido" id="id_proveedor_preferido">
                                <option value="">Seleccione un proveedor</option>
                                <?php if (!empty($proveedores) && is_array($proveedores)): ?>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?php echo $proveedor['id_proveedor']; ?>">
                                            <?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>
                                            <?php if (!empty($proveedor['ruc'])): ?>
                                                (RUC: <?php echo htmlspecialchars($proveedor['ruc']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-image"></i> Imagen del Producto</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Subir imagen desde archivo</label>
                                    <input type="file" class="form-control" name="imagen_file" id="imagen_file" accept="image/*">
                                    <small class="text-muted">Formatos aceptados: JPG, PNG, GIF, WebP. Máx. 2MB</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">O pegar URL de imagen</label>
                                    <input type="url" class="form-control" name="imagen_url" id="imagen_url"
                                        placeholder="https://ejemplo.com/imagen.jpg">
                                </div>

                                <div class="mb-3" id="eliminarImagenContainer" style="display: none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="checkEliminarImagen">
                                        <label class="form-check-label text-danger" for="checkEliminarImagen">
                                            <i class="fas fa-trash"></i> Eliminar imagen actual
                                        </label>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <img id="previewImagen" src=""
                                        alt="Vista previa de imagen"
                                        style="max-height:180px; display:none; object-fit:contain; border:1px solid #ddd; border-radius:8px; padding:10px; background:#f8f9fa;">
                                    <div id="noImagenText" class="text-muted mt-2">
                                        <i class="fas fa-image fa-2x mb-2"></i><br>
                                        Vista previa de imagen
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Venta <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control" name="precio_venta" id="precio_venta" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Compra</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="precio_compra" id="precio_compra">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Actual <span class="text-danger">*</span></label>
                                <input type="number" min="0" class="form-control" name="stock" id="stock" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" min="0" class="form-control" name="stock_minimo" id="stock_minimo">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="guardarProductoBtn">
                                <i class="fas fa-save"></i> Guardar Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- NUEVO CÓDIGO: control manual del dropdown -->
<script>
    $(function() {
        var $dropdownButton = $('.btn-group .btn-success');
        var $dropdownMenu = $('.btn-group .dropdown-menu');

        $dropdownButton.on('click', function(e) {
            e.stopPropagation();
            $dropdownMenu.toggleClass('show');
            var expanded = $(this).attr('aria-expanded') === 'true' ? false : true;
            $(this).attr('aria-expanded', expanded);
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.btn-group').length) {
                $dropdownMenu.removeClass('show');
                $dropdownButton.attr('aria-expanded', 'false');
            }
        });
    });
</script>

    <script>
        $(document).ready(function() {
            const BASE_URL = '<?php echo BASE_URL; ?>';
            let searchTimeout = null;
            let productosSeleccionados = [];

            function actualizarSeleccion() {
                productosSeleccionados = [];
                $('.product-checkbox:checked').each(function() {
                    productosSeleccionados.push($(this).data('id'));
                });

                const count = productosSeleccionados.length;
                $('#seleccionCount').text(count + ' seleccionado' + (count !== 1 ? 's' : ''));
                $('#generarSeleccionadas').html(count > 0 ?
                    '<i class="fas fa-check-square"></i> Seleccionadas (' + count + ')' :
                    '<i class="fas fa-check-square"></i> Seleccionadas (0)');
            }

            $(document).on('change', '.product-checkbox', function() {
                actualizarSeleccion();
            });

            $('#seleccionarTodosBtn').on('click', function() {
                const allChecked = $('.product-checkbox:checked').length === $('.product-checkbox').length;
                $('.product-checkbox').prop('checked', !allChecked);
                actualizarSeleccion();
            });

            // Generar etiquetas de productos seleccionados
            $('#generarSeleccionadas').on('click', function(e) {
                e.preventDefault();
                if (productosSeleccionados.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin selección',
                        text: 'Por favor selecciona al menos un producto para generar etiquetas'
                    });
                    return;
                }

                const url = BASE_URL + 'producto/generarEtiquetas?ids=' + productosSeleccionados.join(',');
                window.open(url, '_blank');
            });

            // ===== DEPURACIÓN: Enlace "Todas las etiquetas" =====
            $('#generarTodasEtiquetas').on('click', function(e) {
                console.log('🔍 Clic en "Todas las etiquetas"');
                console.log('➡️ URL:', $(this).attr('href'));
                // Dejamos que el navegador maneje el enlace gracias a target="_blank"
                // No usamos preventDefault() ni stopPropagation()
            });

            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().trim();
                const suggestionsContainer = $('#suggestionsContainer');
                const searchLoading = $('#searchLoading');

                if (searchTimeout) clearTimeout(searchTimeout);

                if (searchTerm.length < 2) {
                    suggestionsContainer.hide();
                    return;
                }

                searchLoading.show();

                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: BASE_URL + 'producto/buscarSugerencias',
                        type: 'GET',
                        data: {
                            q: searchTerm,
                            limit: 8
                        },
                        success: function(response) {
                            searchLoading.hide();
                            displaySuggestions(response, searchTerm);
                        },
                        error: function() {
                            searchLoading.hide();
                            suggestionsContainer.hide();
                        }
                    });
                }, 300);
            });

            function displaySuggestions(suggestions, searchTerm) {
                const container = $('#suggestionsContainer');

                if (!suggestions || suggestions.length === 0) {
                    container.hide();
                    return;
                }

                let html = '';
                suggestions.forEach(function(suggestion) {
                    const typeClass = getSuggestionTypeClass(suggestion.tipo_coincidencia);
                    html += `
                <div class="suggestion-item" data-id="${suggestion.id}" data-name="${suggestion.nombre}">
                    <div class="suggestion-name">${highlightMatch(suggestion.nombre, searchTerm)}</div>
                    <div class="suggestion-info">
                        <span class="suggestion-price">${getMoneda()}${parseFloat(suggestion.precio).toFixed(2)}</span>
                        <span class="suggestion-type ${typeClass}">${suggestion.tipo_coincidencia}</span>
                    </div>
                    ${suggestion.descripcion ? `<small class="text-muted">${suggestion.descripcion.substring(0, 50)}...</small>` : ''}
                </div>
            `;
                });

                container.html(html).show();
            }

            function highlightMatch(text, searchTerm) {
                if (!searchTerm) return text;
                const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                return text.replace(regex, '<mark>$1</mark>');
            }

            function getSuggestionTypeClass(type) {
                switch (type) {
                    case 'Coincidencia exacta':
                        return 'exact';
                    case 'Coincidencia parcial':
                        return 'partial';
                    case 'En descripción':
                        return 'description';
                    default:
                        return '';
                }
            }

            function getMoneda() {
                return '$';
            }

            $(document).on('click', '.suggestion-item', function() {
                const productName = $(this).data('name');
                $('#searchInput').val(productName);
                $('#suggestionsContainer').hide();
                $('#paginaInput').val(1);
                $('#filtersForm').submit();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.search-controls').length) {
                    $('#suggestionsContainer').hide();
                }
            });

            $('.product-image').on('error', function() {
                const $img = $(this);
                const placeholder = $img.data('placeholder');
                if ($img.attr('src') !== placeholder) {
                    $img.attr('src', placeholder);
                }
            });

            $('select[name="orden"]').on('change', function() {
                $('#paginaInput').val(1);
                $('#filtersForm').submit();
            });

            $('input[name="buscar"]').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#suggestionsContainer').hide();
                    $('#paginaInput').val(1);
                    $('#filtersForm').submit();
                }
            });

            function setSelectValue(selectElement, value) {
                const $select = $(selectElement);
                const stringValue = String(value).trim();

                $select.val(stringValue);

                if ($select.val() !== stringValue && stringValue !== '' && stringValue !== '0') {
                    let found = false;
                    $select.find('option').each(function() {
                        if ($(this).val() === stringValue) {
                            $(this).prop('selected', true);
                            found = true;
                            return false;
                        }
                    });

                    if (!found) {
                        $select.val('');
                    }
                } else if (stringValue === '' || stringValue === '0') {
                    $select.val('');
                }

                $select.trigger('change');
            }

            $('#productoModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const modal = $(this);

                modal.find('input[type="text"], input[type="number"], input[type="url"], textarea, select').val('');
                modal.find('input[type="file"]').val('');
                modal.find('#previewImagen').hide().attr('src', '');
                modal.find('#noImagenText').show();
                modal.find('#eliminar_imagen').val('0');
                modal.find('#checkEliminarImagen').prop('checked', false);
                modal.find('#eliminarImagenContainer').hide();

                if (button.hasClass('edit-btn')) {
                    modal.find('.modal-title').text('Editar Producto');

                    const productData = {
                        id: button.data('id') || '',
                        nombre: button.data('nombre') || '',
                        descripcion: button.data('descripcion') || '',
                        categoria: button.data('categoria') || '',
                        almacen: button.data('almacen') || '',
                        proveedor: button.data('proveedor') || '',
                        precioventa: button.data('precioventa') || '0',
                        preciocompra: button.data('preciocompra') || '0',
                        stock: button.data('stock') || '0',
                        stockminimo: button.data('stockminimo') || '0',
                        imagen: button.data('imagen') || ''
                    };

                    modal.find('#id_producto').val(productData.id);
                    modal.find('#nombre').val(productData.nombre);
                    modal.find('#descripcion').val(productData.descripcion);
                    modal.find('#precio_venta').val(productData.precioventa);
                    modal.find('#precio_compra').val(productData.preciocompra);
                    modal.find('#stock').val(productData.stock);
                    modal.find('#stock_minimo').val(productData.stockminimo);

                    setSelectValue('#id_categoria', productData.categoria);
                    setSelectValue('#id_almacen', productData.almacen);
                    setSelectValue('#id_proveedor_preferido', productData.proveedor);

                    if (productData.imagen) {
                        modal.find('#previewImagen').attr('src', productData.imagen).show();
                        modal.find('#noImagenText').hide();
                        modal.find('#imagen_url').val(productData.imagen);
                        modal.find('#imagen_file').val('');
                        modal.find('#eliminarImagenContainer').show();
                    }
                }
            });

            $('#imagen_file').on('change', function() {
                const file = this.files[0];
                const preview = $('#previewImagen');
                const noImagenText = $('#noImagenText');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.attr('src', e.target.result).show();
                        noImagenText.hide();
                    };
                    reader.readAsDataURL(file);
                    $('#imagen_url').val('');
                    $('#eliminar_imagen').val('0');
                    $('#checkEliminarImagen').prop('checked', false);
                }
            });

            $('#imagen_url').on('input', function() {
                const url = $(this).val().trim();
                const preview = $('#previewImagen');
                const noImagenText = $('#noImagenText');

                if (url && (url.match(/\.(jpeg|jpg|gif|png|webp)$/i) !== null || url.startsWith('http'))) {
                    preview.attr('src', url).show();
                    noImagenText.hide();
                    $('#imagen_file').val('');
                    $('#eliminar_imagen').val('0');
                    $('#checkEliminarImagen').prop('checked', false);
                }
            });

            $(document).on('change', '#checkEliminarImagen', function() {
                const isChecked = this.checked;
                $('#eliminar_imagen').val(isChecked ? '1' : '0');

                if (isChecked) {
                    $('#previewImagen').hide();
                    $('#noImagenText').show();
                    $('#imagen_url').val('');
                    $('#imagen_file').val('');
                }
            });

            $('#formProducto').on('submit', function(e) {
                e.preventDefault();

                const nombre = $('#nombre').val().trim();
                const precioVenta = $('#precio_venta').val();
                const stock = $('#stock').val();
                const idAlmacen = $('#id_almacen').val();

                if (!nombre || !precioVenta || !stock || !idAlmacen) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Por favor complete todos los campos obligatorios'
                    });
                    return;
                }

                const formData = new FormData(this);
                const submitBtn = $('#guardarProductoBtn');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: BASE_URL + 'producto/guardar',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        submitBtn.prop('disabled', false).html(originalText);

                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;

                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Éxito!',
                                    text: result.message,
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    $('#productoModal').modal('hide');
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'Error al guardar el producto'
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al procesar la respuesta del servidor'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        submitBtn.prop('disabled', false).html(originalText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al guardar el producto'
                        });
                    }
                });
            });

            $('.action-btn-confirm').on('click', function(e) {
                e.preventDefault();
                const url = this.href;
                const action = this.title;

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `¿Quieres ${action.toLowerCase()} este producto?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>
</body>

</html>