<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4"><i class="fas fa-exclamation-triangle text-warning"></i> Reporte de Productos con Bajo Stock</h1>
        <a href="<?php echo BASE_URL; ?>home" class="btn btn-secondary">Volver al Dashboard</a>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Productos que requieren reabastecimiento</h4>
            <div>
                <a href="<?php echo BASE_URL; ?>reporte/stockBajoPDF" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Exportar a PDF
                </a>
                <a href="<?php echo BASE_URL; ?>reporte/stockBajoExcel" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($productos) && !empty($productos)): ?>
                <!-- Vista de tarjetas (predeterminada) -->
                <div class="row" id="cardsView">
                    <?php foreach ($productos as $p): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-warning shadow-sm">
                                <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-dark fw-bold"><?php echo htmlspecialchars($p['nombre']); ?></h6>
                                    <span class="badge bg-danger">Stock Bajo</span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-4">
                                            <img src="<?php echo $p['imagen_display']; ?>" 
                                                 class="img-fluid rounded product-image" 
                                                 alt="<?php echo htmlspecialchars($p['nombre']); ?>"
                                                 style="max-height: 100px; object-fit: cover; width: 100%;"
                                                 onerror="handleImageError(this)">
                                        </div>
                                        <div class="col-8">
                                            <div class="mb-2">
                                                <small class="text-muted">Proveedor:</small>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($p['nombre_proveedor'] ?? 'Sin proveedor'); ?></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">Stock Actual:</small>
                                                    <div class="fw-bold text-danger fs-5"><?php echo $p['stock']; ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Stock Mínimo:</small>
                                                    <div class="fw-bold text-dark"><?php echo $p['stock_minimo']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="progress" style="height: 8px;">
                                        <?php 
                                        $porcentaje = $p['stock_minimo'] > 0 ? ($p['stock'] / $p['stock_minimo']) * 100 : 0;
                                        $porcentaje = min($porcentaje, 100);
                                        $clase_progress = $porcentaje <= 25 ? 'bg-danger' : ($porcentaje <= 50 ? 'bg-warning' : 'bg-success');
                                        ?>
                                        <div class="progress-bar <?php echo $clase_progress; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $porcentaje; ?>%"
                                             aria-valuenow="<?php echo $porcentaje; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block text-center mt-1">
                                        <?php echo number_format($porcentaje, 1); ?>% del stock mínimo
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Vista de tabla como alternativa -->
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-muted">Vista de tabla</h5>
                        <div>
                            <button class="btn btn-outline-primary btn-sm" onclick="showCardsView()">
                                <i class="fas fa-th-large"></i> Vista Tarjetas
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="showTableView()">
                                <i class="fas fa-table"></i> Vista Tabla
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="tableView">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Proveedor</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $p): ?>
                                    <tr class="table-warning">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $p['imagen_display']; ?>" 
                                                     class="rounded me-2 product-image" 
                                                     alt="<?php echo htmlspecialchars($p['nombre']); ?>"
                                                     style="width: 40px; height: 40px; object-fit: cover;"
                                                     onerror="handleImageError(this)">
                                                <?php echo htmlspecialchars($p['nombre']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['nombre_proveedor'] ?? 'Sin proveedor'); ?></td>
                                        <td><strong class="text-danger"><?php echo $p['stock']; ?></strong></td>
                                        <td><?php echo $p['stock_minimo']; ?></td>
                                        <td>
                                            <?php if ($p['stock'] == 0): ?>
                                                <span class="badge bg-danger">Agotado</span>
                                            <?php elseif ($p['stock'] <= 3): ?>
                                                <span class="badge bg-danger">Crítico</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Bajo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="text-success">¡Felicidades!</h4>
                    <p class="text-muted">No hay productos con bajo stock en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Función para manejar errores de imágenes sin bucle infinito
function handleImageError(img) {
    // Solo cambiar si no es ya la imagen por defecto
    if (!img.src.includes('default-product.png')) {
        img.src = '<?php echo BASE_URL; ?>assets/img/default-product.png';
        // Remover el onerror para evitar bucles
        img.onerror = null;
    }
}

function showCardsView() {
    document.getElementById('cardsView').classList.remove('d-none');
    document.getElementById('tableView').classList.add('d-none');
}

function showTableView() {
    document.getElementById('cardsView').classList.add('d-none');
    document.getElementById('tableView').classList.remove('d-none');
}

// Inicializar con vista de tarjetas
document.addEventListener('DOMContentLoaded', function() {
    showCardsView();
});
</script>