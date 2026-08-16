<div class="container-fluid">
    <h1 class="mt-4 text-primary"><i class="fas fa-chart-line"></i> Análisis de Rentabilidad</h1>
    
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
                <input type="hidden" name="m" value="rentabilidad">
                
                <div class="col-md-6">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="col-md-6">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                           value="<?php echo $fecha_fin; ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs principales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Margen Global</h5>
                    <h3 class="card-text"><?php echo number_format($kpis['margen_global'], 2); ?>%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Productos Rentables</h5>
                    <h3 class="card-text"><?php echo $kpis['productos_rentables']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Margen Promedio</h5>
                    <h3 class="card-text"><?php echo number_format($kpis['margen_promedio'], 2); ?>%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Ingresos Totales</h5>
                    <h3 class="card-text"><?php echo getMoneda() . number_format($rentabilidadGeneral['ingresos_totales'] ?? 0, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos más rentables -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-trophy"></i> Productos Más Rentables</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($productosMasRentables)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Precio Costo</th>
                                <th class="text-end">Precio Venta</th>
                                <th class="text-center">Margen %</th>
                                <th class="text-end">Ganancia Total</th>
                                <th class="text-center">Cantidad Vendida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productosMasRentables as $producto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td class="text-end"><?php echo getMoneda() . number_format($producto['precio_compra'], 2); ?></td>
                                    <td class="text-end"><?php echo getMoneda() . number_format($producto['precio_venta'], 2); ?></td>
                                    <td class="text-center">
                                        <?php 
                                            $color = $producto['margen_porcentaje'] >= 30 ? 'success' : 
                                                    ($producto['margen_porcentaje'] >= 15 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo number_format($producto['margen_porcentaje'], 2); ?>%
                                        </span>
                                    </td>
                                    <td class="text-end"><?php echo getMoneda() . number_format($producto['ganancia_total'], 2); ?></td>
                                    <td class="text-center"><?php echo $producto['total_vendido']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay datos de productos rentables para el período seleccionado.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen general -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Resumen General de Rentabilidad</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td><strong>Total Ventas</strong></td>
                                <td class="text-end"><?php echo $rentabilidadGeneral['total_ventas'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Ingresos Totales</strong></td>
                                <td class="text-end"><?php echo getMoneda() . number_format($rentabilidadGeneral['ingresos_totales'] ?? 0, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Costos Totales</strong></td>
                                <td class="text-end"><?php echo getMoneda() . number_format($rentabilidadGeneral['costos_totales'] ?? 0, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td><strong>Ganancia Neta</strong></td>
                                <td class="text-end"><?php echo getMoneda() . number_format($rentabilidadGeneral['ganancia_neta'] ?? 0, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Margen Global</strong></td>
                                <td class="text-end"><?php echo number_format($rentabilidadGeneral['margen_global'] ?? 0, 2); ?>%</td>
                            </tr>
                            <tr>
                                <td><strong>Productos No Rentables</strong></td>
                                <td class="text-end"><?php echo $kpis['productos_no_rentables']; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>