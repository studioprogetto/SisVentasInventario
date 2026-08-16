<div class="container-fluid">
    <!-- 🔹 Encabezado -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4">
            <i class="fas fa-cash-register text-danger"></i> Reporte de Caja Detallado
        </h1>
        <div>
            <button type="button" class="btn btn-warning me-2" onclick="actualizarDatosTurnos()">
                <i class="fas fa-sync-alt me-1"></i> Actualizar Datos
            </button>
            <a href="<?php echo BASE_URL; ?>reporte" class="btn btn-secondary">Volver al Menú</a>
        </div>
    </div>

    <!-- 🔹 Resumen General -->
    <?php if (!empty($turnos)): ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-dark">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Resumen General</h5>
                        <small class="text-light">Total Turnos: <?= count($turnos) ?></small>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title">Total Ventas</h6>
                                        <h4 class="text-primary"><?= array_sum(array_column($turnos, 'num_ventas')) ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title">Ingresos Totales</h6>
                                        <h4 class="text-success"><?= getMoneda() . number_format(array_sum(array_column($turnos, 'total_ingresos')), 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title">Efectivo Total</h6>
                                        <h4 class="text-success"><?= getMoneda() . number_format(array_sum(array_column($turnos, 'efectivo')), 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title">Yape Total</h6>
                                        <h4 class="text-info"><?= getMoneda() . number_format(array_sum(array_column($turnos, 'yape')), 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title">Plin Total</h6>
                                        <h4 class="text-info"><?= getMoneda() . number_format(array_sum(array_column($turnos, 'plin')), 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title">Ganancia Total</h6>
                                        <h4 class="text-success"><?= getMoneda() . number_format(array_sum(array_column($turnos, 'ganancia')), 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 🔹 Tarjeta principal -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-history"></i> Historial de Turnos de Caja</h4>
            <small class="text-light">Datos calculados en tiempo real desde ventas</small>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Usuario</th>
                            <th>Apertura</th>
                            <th>Monto Inicial</th>
                            <th>Cierre</th>
                            <th>Monto Final (Sistema)</th>
                            <th>Monto Final (Real)</th>
                            <th>Diferencia</th>
                            <th>Ventas Realizadas</th>
                            <th>Total Ventas</th>
                            <th>Efectivo</th>
                            <th>Yape</th>
                            <th>Plin</th>
                            <th>Agora</th>
                            <th>Transferencia</th>
                            <!-- 🔹 Nuevas columnas -->
                            <th>Ventas Brutas</th>
                            <th>Costo Neto (Compra)</th>
                            <th>Ganancia Neta</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        <?php if (!empty($turnos)): ?>
                            <?php foreach ($turnos as $t): ?>
                                <?php
                                // 🔹 Asegurar que todos los valores sean numéricos
                                $usuario = htmlspecialchars($t['usuario'] ?? 'Desconocido');
                                $fechaApertura = !empty($t['fecha_apertura']) ? date('d/m/Y H:i', strtotime($t['fecha_apertura'])) : '-';
                                $montoInicial = number_format((float)($t['monto_inicial'] ?? 0), 2);
                                $fechaCierre = !empty($t['fecha_cierre']) ? date('d/m/Y H:i', strtotime($t['fecha_cierre'])) : '-';
                                $montoSistema = number_format((float)($t['monto_final_sistema'] ?? 0), 2);
                                $montoReal = number_format((float)($t['monto_final_real'] ?? 0), 2);
                                $diferencia = number_format((float)($t['diferencia'] ?? 0), 2);
                                $numVentas = (int)($t['num_ventas'] ?? 0);
                                $totalIngresos = number_format((float)($t['total_ingresos'] ?? 0), 2);

                                // 🔹 Métodos de pago - Asegurar valores numéricos
                                $efectivo = number_format((float)($t['efectivo'] ?? 0), 2);
                                $yape = number_format((float)($t['yape'] ?? 0), 2);
                                $plin = number_format((float)($t['plin'] ?? 0), 2);
                                $agora = number_format((float)($t['agora'] ?? 0), 2);
                                $transferencia = number_format((float)($t['transferencia'] ?? 0), 2);

                                // 🔹 Nuevos datos
                                $ventasBrutas = number_format((float)($t['total_bruto'] ?? 0), 2);
                                $costoNeto = number_format((float)($t['total_neto'] ?? 0), 2);
                                $ganancia = number_format((float)($t['ganancia'] ?? 0), 2);

                                // 🔹 Calcular diferencia correctamente
                                $diferenciaCalculada = (float)$t['monto_final_real'] - (float)$t['monto_final_sistema'];
                                $claseDiferencia = $diferenciaCalculada != 0 ? 'text-danger fw-bold' : 'text-success';
                                ?>
                                <tr>
                                    <td><?= $usuario ?></td>
                                    <td><?= $fechaApertura ?></td>
                                    <td><?= getMoneda() . $montoInicial ?></td>
                                    <td><?= $fechaCierre ?></td>
                                    <td><?= getMoneda() . $montoSistema ?></td>
                                    <td><?= getMoneda() . $montoReal ?></td>
                                    <td class="<?= $claseDiferencia ?>">
                                        <?= getMoneda() . number_format($diferenciaCalculada, 2) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $numVentas ?></span>
                                    </td>
                                    <td class="fw-bold text-primary"><?= getMoneda() . $totalIngresos ?></td>
                                    <td>
                                        <span class="badge bg-success"><?= getMoneda() . $efectivo ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= getMoneda() . $yape ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= getMoneda() . $plin ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= getMoneda() . $agora ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= getMoneda() . $transferencia ?></span>
                                    </td>

                                    <!-- 🔹 Nuevas columnas -->
                                    <td class="fw-semibold text-info"><?= getMoneda() . $ventasBrutas ?></td>
                                    <td class="fw-semibold text-warning"><?= getMoneda() . $costoNeto ?></td>
                                    <td class="fw-semibold text-success"><?= getMoneda() . $ganancia ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="17" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle"></i> No hay turnos cerrados para mostrar.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 🔹 Botones de exportación -->
            <div class="d-flex gap-2 justify-content-center mt-4">
                <a href="<?php echo BASE_URL; ?>caja/pdf" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Exportar a PDF
                </a>
                <a href="<?php echo BASE_URL; ?>caja/excel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // 🔹 Función para actualizar datos de turnos
    function actualizarDatosTurnos() {
        if (!confirm('¿Estás seguro de que quieres actualizar todos los datos de los turnos? Esto calculará los datos reales desde las ventas.')) {
            return;
        }

        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';
        btn.disabled = true;

        fetch('<?php echo BASE_URL; ?>caja/actualizarDatosTurnos')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.mensaje);
                    location.reload(); // Recargar para mostrar datos actualizados
                } else {
                    alert('❌ Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    // 🔹 Mostrar información de debug
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== REPORTE CAJA CARGADO ===');
        console.log('Total turnos:', <?= count($turnos) ?>);
        
        <?php if (!empty($turnos)): ?>
            console.log('Datos del primer turno:', <?= json_encode($turnos[0] ?? []) ?>);
            
            // Verificar consistencia de datos
            const totalVentas = <?= array_sum(array_column($turnos, 'num_ventas')) ?>;
            const totalIngresos = <?= array_sum(array_column($turnos, 'total_ingresos')) ?>;
            const totalEfectivo = <?= array_sum(array_column($turnos, 'efectivo')) ?>;
            const totalYape = <?= array_sum(array_column($turnos, 'yape')) ?>;
            const totalPlin = <?= array_sum(array_column($turnos, 'plin')) ?>;
            const totalAgora = <?= array_sum(array_column($turnos, 'agora')) ?>;
            const totalTransferencia = <?= array_sum(array_column($turnos, 'transferencia')) ?>;
            
            const sumaMetodos = totalEfectivo + totalYape + totalPlin + totalAgora + totalTransferencia;
            const diferencia = Math.abs(totalIngresos - sumaMetodos);
            
            console.log('Verificación de datos:');
            console.log('- Total ingresos:', totalIngresos);
            console.log('- Suma métodos pago:', sumaMetodos);
            console.log('- Diferencia:', diferencia);
            
            if (diferencia > 0.01) {
                console.warn('⚠️ ADVERTENCIA: Los totales no coinciden. Diferencia:', diferencia);
            }
        <?php endif; ?>
    });
</script>

<style>
.table th {
    background-color: #343a40;
    color: white;
    font-weight: 600;
    border: none;
}

.table td {
    vertical-align: middle;
    border-color: #dee2e6;
}

.badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.125);
}

.btn {
    border-radius: 0.375rem;
}

/* Responsive */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>