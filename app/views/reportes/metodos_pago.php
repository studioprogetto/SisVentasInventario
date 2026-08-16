<div class="container-fluid">
    <h1 class="mt-4 text-primary"><i class="fas fa-credit-card"></i> Reporte de Métodos de Pago</h1>
    
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
                <input type="hidden" name="m" value="metodosPago">
                
                <div class="col-md-4">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                           value="<?php echo $fecha_fin; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total General</h5>
                    <h3 class="card-text"><?php echo getMoneda() . number_format($total_general, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Ventas</h5>
                    <h3 class="card-text">
                        <?php 
                            $total_ventas = array_sum(array_column($distribucion, 'total_ventas'));
                            echo number_format($total_ventas);
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Métodos de Pago</h5>
                    <h3 class="card-text"><?php echo count($distribucion); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de distribución -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Distribución por Método de Pago</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($distribucion)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Método de Pago</th>
                                <th class="text-center">N° Ventas</th>
                                <th class="text-end">Monto Total</th>
                                <th class="text-center">Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distribucion as $metodo): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-credit-card me-2"></i>
                                        <?php echo ucfirst($metodo['metodo_pago']); ?>
                                    </td>
                                    <td class="text-center"><?php echo $metodo['total_ventas']; ?></td>
                                    <td class="text-end"><?php echo getMoneda() . number_format($metodo['monto_total'], 2); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $metodo['porcentaje']; ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay datos de ventas para el período seleccionado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>