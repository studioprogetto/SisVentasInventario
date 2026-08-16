
<style>
   
    h1 {
        font-weight: 700;
        color: #0d6efd;
    }

    .card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background: linear-gradient(90deg, #0d6efd, #6610f2);
        color: #fff;
        font-weight: 600;
        border-top-left-radius: 15px !important;
        border-top-right-radius: 15px !important;
    }

    .btn {
        border-radius: 8px;
        font-weight: 600;
        transition: transform 0.2s;
    }

    .btn:hover {
        transform: scale(1.05);
    }

    .table th {
        background-color: #198754 !important;
        color: #fff;
        text-align: center;
        vertical-align: middle;
    }

    .list-group-item {
        background-color: #fafafa;
    }

    .badge {
        font-size: 0.9rem;
    }

    .highlight {
        font-size: 1.3rem;
        font-weight: 700;
    }

    .icon-box {
        font-size: 2rem;
        color: #0d6efd;
        background-color: #e7f1ff;
        border-radius: 50%;
        padding: 10px;
        margin-right: 10px;
    }

    .shadow-sm {
        box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4"><i class="fas fa-chart-line"></i> Reporte de Ventas</h1>
        <a href="<?php echo BASE_URL; ?>reporte" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Menú
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="fecha_inicio" class="form-label fw-bold">📅 Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" 
                           value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_fin" class="form-label fw-bold">📅 Fecha de Fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" 
                           value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" name="accion" value="ver" class="btn btn-primary flex-fill">
                        <i class="fas fa-eye"></i> Ver Reporte
                    </button>
                    <button type="submit" name="accion" value="excel" class="btn btn-success flex-fill">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button type="submit" name="accion" value="pdf" class="btn btn-danger flex-fill">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($resumen)): ?>
        <div class="row">
            <!-- Tarjeta Resumen General -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="fas fa-coins"></i> Resumen General</div>
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-box"><i class="fas fa-cash-register"></i></div>
                        <div>
                            <p class="mb-1">Total de Ingresos</p>
                            <p class="highlight text-success">
                                <?php echo getMoneda(); ?><?php echo number_format($resumen['total_ingresos'] ?? 0, 2); ?>
                            </p>
                            <p class="mb-0">Número de Ventas: 
                                <strong><?php echo $resumen['num_ventas'] ?? 0; ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjeta Top Productos -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="fas fa-star"></i> Top 5 Productos Más Vendidos</div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if (isset($top_productos) && !empty($top_productos)): ?>
                                <?php foreach ($top_productos as $producto): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div><i class="fas fa-box-open text-primary"></i> <?php echo htmlspecialchars($producto['nombre']); ?></div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $producto['total_cantidad']; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted text-center">No hay datos para este período.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabla Detalle de Ventas -->
    <div class="card shadow-sm mb-5">
        <div class="card-header"><i class="fas fa-list"></i> Detalle de Ventas</div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total Venta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ventas)): ?>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td><i class="far fa-calendar-alt text-primary"></i> <?php echo htmlspecialchars($v['fecha_venta']); ?></td>
                                <td><i class="fas fa-user text-secondary"></i> <?php echo htmlspecialchars($v['cliente']); ?></td>
                                <td class="text-end text-success fw-bold"><?php echo getMoneda(); ?><?php echo number_format($v['total_venta'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No hay ventas registradas en este período.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

