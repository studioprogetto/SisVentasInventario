<div class="container-fluid">
    <h1 class="mt-4 text-primary"><i class="fas fa-chart-line"></i> Productos Más Vendidos</h1>
    
    <div class="d-flex justify-content-between mb-4">
        <a href="<?php echo BASE_URL; ?>reporte" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Reportes
        </a>
        <div>

        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="c" value="reporte">
                <input type="hidden" name="m" value="topProductos">
                
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                           value="<?php echo $fecha_fin; ?>">
                </div>
                <div class="col-md-3">
                    <label for="tipo_ranking" class="form-label">Tipo de Ranking</label>
                    <select class="form-select" id="tipo_ranking" name="tipo_ranking">
                        <option value="cantidad" <?php echo $tipo_ranking === 'cantidad' ? 'selected' : ''; ?>>Por Cantidad</option>
                        <option value="revenue" <?php echo $tipo_ranking === 'revenue' ? 'selected' : ''; ?>>Por Revenue</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="limite" class="form-label">Límite</label>
                    <select class="form-select" id="limite" name="limite">
                        <option value="5" <?php echo $limite == 5 ? 'selected' : ''; ?>>Top 5</option>
                        <option value="10" <?php echo $limite == 10 ? 'selected' : ''; ?>>Top 10</option>
                        <option value="20" <?php echo $limite == 20 ? 'selected' : ''; ?>>Top 20</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Cantidad</h5>
                    <h3 class="card-text"><?php echo number_format($totales['total_cantidad']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h3 class="card-text"><?php echo getMoneda() . number_format($totales['total_revenue'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Ganancia</h5>
                    <h3 class="card-text"><?php echo getMoneda() . number_format($totales['total_ganancia'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Margen Promedio</h5>
                    <h3 class="card-text"><?php echo number_format($totales['promedio_margen'], 2); ?>%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-trophy"></i> 
                <?php echo $tipo_ranking === 'revenue' ? 'Top Productos por Revenue' : 'Top Productos por Cantidad Vendida'; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($topProductos)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th class="text-center">Cantidad Vendida</th>
                                <th class="text-end">Precio Venta</th>
                                <th class="text-end">Revenue Total</th>
                                <th class="text-end">Ganancia Total</th>
                                <th class="text-center">Margen %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProductos as $index => $producto): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td class="text-center"><?php echo $producto['total_vendido']; ?></td>
                                    <td class="text-end"><?php echo getMoneda() . number_format($producto['precio_venta'] ?? 0, 2); ?></td>
                                    <td class="text-end"><?php echo getMoneda() . number_format($producto['revenue_total'] ?? 0, 2); ?></td>
                                    <td class="text-end"><?php echo getMoneda() . number_format($producto['ganancia_total'] ?? 0, 2); ?></td>
                                    <td class="text-center">
                                        <?php 
                                            $margen = 0;
                                            if (($producto['revenue_total'] ?? 0) > 0) {
                                                $margen = (($producto['ganancia_total'] ?? 0) / ($producto['revenue_total'] ?? 1)) * 100;
                                            }
                                            $color = $margen >= 30 ? 'success' : ($margen >= 15 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo number_format($margen, 2); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay datos de productos vendidos para el período seleccionado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>