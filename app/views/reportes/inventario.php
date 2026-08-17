<?php
// Calcular estadísticas del inventario - SOLO productos con precio_compra > 0
$valor_total_costo = 0;
$valor_total_venta = 0;
$total_productos = 0;
$productos_bajo_stock = 0;
$productos_sin_precio_compra = 0;

foreach ($productos as $p) {
    if ($p['activo'] == 1) {
        $total_productos++;

        // Contar productos sin precio de compra
        if ($p['precio_compra'] <= 0) {
            $productos_sin_precio_compra++;
        } else {
            // Solo calcular para productos con precio_compra > 0
            $valor_total_costo += $p['stock'] * $p['precio_compra'];
            $valor_total_venta += $p['stock'] * $p['precio_venta'];

            if ($p['stock'] <= $p['stock_minimo']) {
                $productos_bajo_stock++;
            }
        }
    }
}

// Calcular ganancia solo si hay productos con precio_compra
$ganancia_potencial = $valor_total_venta - $valor_total_costo;
$margen_ganancia = $valor_total_costo > 0 ? ($ganancia_potencial / $valor_total_costo) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .card-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
        }

        .stats-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stats-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .table thead th {
            background: #4e73df;
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }

        .stock-low {
            background-color: rgba(220, 53, 69, 0.1) !important;
            border-left: 4px solid #dc3545;
        }

        .stock-ok {
            border-left: 4px solid #28a745;
        }

        .stock-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
            border-left: 4px solid #ffc107;
        }

        .no-price {
            background-color: rgba(108, 117, 125, 0.1) !important;
            border-left: 4px solid #6c757d;
        }

        .product-image-cell {
            width: 60px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .product-image-placeholder {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 12px;
            border: 1px solid #dee2e6;
        }

        .badge-stock {
            font-size: 0.75rem;
            padding: 0.35rem 0.5rem;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .profit-positive {
            color: #28a745;
        }

        .profit-negative {
            color: #dc3545;
        }

        .no-data {
            color: #6c757d;
            font-style: italic;
        }

        .filter-buttons {
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .stats-card .card-body {
                padding: 1rem;
            }

            .stats-value {
                font-size: 1.5rem;
            }

            .export-buttons {
                justify-content: center;
                margin-top: 1rem;
            }

            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <!-- Encabezado -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary"><i class="fas fa-boxes me-2"></i>Reporte de Inventario</h1>
            <a href="<?php echo BASE_URL; ?>reporte" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Menú
            </a>
        </div>

        <!-- Tarjetas de Estadísticas Mejoradas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="stats-icon text-primary mb-3">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h3 class="stats-value text-dark"><?php echo getMoneda(); ?><?php echo number_format($valor_total_costo, 2); ?></h3>
                        <p class="stats-label text-muted">Valor Total (Costo)</p>
                        <small class="text-muted">Productos con precio</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="stats-icon text-success mb-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="stats-value <?php echo $ganancia_potencial >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            <?php echo getMoneda(); ?><?php echo number_format($ganancia_potencial, 2); ?>
                        </h3>
                        <p class="stats-label text-muted">Ganancia Potencial</p>
                        <small class="text-muted"><?php echo number_format($margen_ganancia, 1); ?>% de margen</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="stats-icon text-info mb-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <h3 class="stats-value text-dark"><?php echo $total_productos; ?></h3>
                        <p class="stats-label text-muted">Total Productos</p>
                        <small class="text-muted"><?php echo $productos_sin_precio_compra; ?> sin precio</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="stats-icon text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="stats-value text-dark"><?php echo $productos_bajo_stock; ?></h3>
                        <p class="stats-label text-muted">Stock Bajo</p>
                        <small class="text-muted">Necesitan reposición</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="filter-buttons">
                    <button class="btn btn-outline-primary active filter-btn" data-filter="all">
                        <i class="fas fa-list me-1"></i> Todos (<?php echo $total_productos; ?>)
                    </button>
                    <button class="btn btn-outline-warning filter-btn" data-filter="low-stock">
                        <i class="fas fa-exclamation-triangle me-1"></i> Stock Bajo (<?php echo $productos_bajo_stock; ?>)
                    </button>
                    <button class="btn btn-outline-danger filter-btn" data-filter="no-stock">
                        <i class="fas fa-times-circle me-1"></i> Sin Stock
                    </button>
                    <button class="btn btn-outline-secondary filter-btn" data-filter="no-price">
                        <i class="fas fa-dollar-sign me-1"></i> Sin Precio Compra (<?php echo $productos_sin_precio_compra; ?>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Inventario -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="m-0"><i class="fas fa-clipboard-list me-2"></i>Estado Actual del Stock</h4>
                <div class="export-buttons">
                    <button class="btn btn-success btn-sm" id="exportExcel">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button class="btn btn-danger btn-sm" id="exportPDF">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button class="btn btn-info btn-sm" id="printReport">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Estado</th>
                                <th>Precio Costo</th>
                                <th>Precio Venta</th>
                                <th>Ganancia Unit.</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($productos)): ?>
                                <?php foreach ($productos as $p): ?>
                                    <?php if ($p['activo'] == 1): ?>
                                        <?php
                                        // Determinar clase de estado de stock
                                        $stock_class = 'stock-ok';
                                        $stock_status = 'Normal';
                                        $stock_badge = 'success';
                                        $has_price = $p['precio_compra'] > 0;

                                        if ($p['stock'] <= 0) {
                                            $stock_class = 'stock-low';
                                            $stock_status = 'Sin Stock';
                                            $stock_badge = 'danger';
                                        } elseif ($p['stock'] <= $p['stock_minimo']) {
                                            $stock_class = 'stock-warning';
                                            $stock_status = 'Bajo Stock';
                                            $stock_badge = 'warning';
                                        }

                                        // Si no tiene precio de compra, clase especial
                                        if (!$has_price) {
                                            $stock_class = 'no-price';
                                            $stock_status = 'Sin Precio';
                                            $stock_badge = 'secondary';
                                        }

                                        // Calcular valores SOLO si tiene precio_compra
                                        $valor_total_producto = $has_price ? $p['stock'] * $p['precio_compra'] : 0;
                                        $ganancia_unitaria = $has_price ? ($p['precio_venta'] - $p['precio_compra']) : null;

                                        // Manejo de imagen SIMPLE - solo imagen_url
                                        $imgSrc = BASE_URL . 'public/img/no-image.png';
                                        if (!empty($p['imagen_url'])) {
                                            $imgSrc = $p['imagen_url'];
                                        }
                                        ?>
                                        <tr class="<?php echo $stock_class; ?>"
                                            data-stock="<?php echo $p['stock']; ?>"
                                            data-has-price="<?php echo $has_price ? 'true' : 'false'; ?>">
                                            <td class="product-image-cell">
                                                <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                                    class="product-image"
                                                    alt="<?php echo htmlspecialchars($p['nombre']); ?>"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="product-image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($p['nombre']); ?></strong>
                                                <?php if (!empty($p['descripcion'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($p['descripcion']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($p['nombre_categoria'] ?? 'Sin categoría'); ?></td>
                                            <td>
                                                <span class="fw-bold"><?php echo $p['stock']; ?></span>
                                            </td>
                                            <td><?php echo $p['stock_minimo']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $stock_badge; ?> badge-stock">
                                                    <?php echo $stock_status; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($has_price): ?>
                                                    <?php echo getMoneda(); ?><?php echo number_format($p['precio_compra'], 2); ?>
                                                <?php else: ?>
                                                    <span class="no-data">NULO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($p['precio_venta']): ?>
                                                    <?php echo getMoneda(); ?><?php echo number_format($p['precio_venta'], 2); ?>
                                                <?php else: ?>
                                                    <span class="no-data">NULO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($ganancia_unitaria !== null): ?>
                                                    <span class="<?php echo $ganancia_unitaria >= 0 ? 'profit-positive' : 'profit-negative'; ?> fw-bold">
                                                        <?php echo getMoneda(); ?><?php echo number_format($ganancia_unitaria, 2); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="no-data">NULO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($has_price): ?>
                                                    <span class="fw-bold text-primary"><?php echo getMoneda(); ?><?php echo number_format($valor_total_producto, 2); ?></span>
                                                <?php else: ?>
                                                    <span class="no-data">NULO</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No hay productos registrados</h5>
                                        <p class="text-muted">Agrega productos para ver el reporte de inventario</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($productos)): ?>
                            <tfoot>
                                <tr class="table-active">
                                    <td colspan="8" class="text-end fw-bold">Totales (productos con precio):</td>
                                    <td class="fw-bold <?php echo $ganancia_potencial >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                        <?php echo getMoneda(); ?><?php echo number_format($ganancia_potencial, 2); ?>
                                    </td>
                                    <td class="fw-bold text-success"><?php echo getMoneda(); ?><?php echo number_format($valor_total_costo, 2); ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTable en español COMPLETO
            var table = $('#inventoryTable').DataTable({
                language: {
                    "decimal": "",
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "aria": {
                        "sortAscending": ": activar para ordenar la columna ascendente",
                        "sortDescending": ": activar para ordenar la columna descendente"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                pageLength: 25,
                order: [
                    [3, 'asc']
                ], // Ordenar por stock de forma ascendente
                columnDefs: [{
                    orderable: false,
                    targets: [0]
                }]
            });

            // Filtros
            $('.filter-btn').on('click', function() {
                var filter = $(this).data('filter');

                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                switch (filter) {
                    case 'all':
                        table.search('').columns().search('').draw();
                        break;
                    case 'low-stock':
                        table.column(3).search('^[1-9]$|^[1-9][0-9]$', true, false).draw();
                        break;
                    case 'no-stock':
                        table.column(3).search('^0$', true, false).draw();
                        break;
                    case 'no-price':
                        table.column(6).search('^NULO$', true, false).draw();
                        break;
                }
            });

            // Manejo de imágenes que fallan
            document.addEventListener('DOMContentLoaded', function() {
                var images = document.querySelectorAll('.product-image');
                images.forEach(function(img) {
                    if (img.complete && img.naturalHeight === 0) {
                        img.style.display = 'none';
                        var placeholder = img.nextElementSibling;
                        if (placeholder && placeholder.classList.contains('product-image-placeholder')) {
                            placeholder.style.display = 'flex';
                        }
                    }
                });
            });

            // Exportar a Excel
            $('#exportExcel').on('click', function() {
                // Crear parámetros para la exportación
                const params = new URLSearchParams({
                    accion: 'excel',
                    tipo_reporte: 'inventario_completo'
                });

                // Redirigir al controlador para generar Excel
                window.location.href = '<?php echo BASE_URL; ?>reporte/exportarInventario?' + params.toString();
            });

            // Exportar a PDF
            $('#exportPDF').on('click', function() {
                // Crear parámetros para la exportación
                const params = new URLSearchParams({
                    accion: 'pdf',
                    tipo_reporte: 'inventario_completo'
                });

                // Redirigir al controlador para generar PDF
                window.location.href = '<?php echo BASE_URL; ?>reporte/exportarInventario?' + params.toString();
            });

            // Imprimir reporte
            $('#printReport').on('click', function() {
                window.print();
            });
        });
    </script>
</body>

</html>