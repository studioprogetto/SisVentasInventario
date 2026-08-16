<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-primary"><i class="fas fa-truck"></i> Gestión de Compras</h2>
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>compra/crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Orden de Compra
            </a>
            <a href="<?php echo BASE_URL; ?>compra/reporte?accion=pdf" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </a>
            <a href="<?php echo BASE_URL; ?>compra/reporte?accion=excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($compras) && $compras->num_rows > 0): ?>
                        <?php while ($compra = $compras->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$compra['id_compra']; ?></td>
                                <td><?php echo htmlspecialchars($compra['nombre_proveedor']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($compra['fecha_compra'])); ?></td>
                                <td><?php echo getMoneda() . number_format((float)$compra['total_compra'], 2); ?></td>
                                <td>
                                    <?php
                                    $estado = strtolower($compra['estado']);
                                    $clase_badge = 'bg-secondary';
                                    $texto_estado = ucfirst($estado);

                                    if ($estado === 'solicitada') {
                                        $clase_badge = 'bg-warning text-dark';
                                        $texto_estado = 'Solicitada';
                                    } elseif ($estado === 'recibida') {
                                        $clase_badge = 'bg-success';
                                        $texto_estado = 'Recibida';
                                    } elseif ($estado === 'pagada') {
                                        $clase_badge = 'bg-info text-dark';
                                        $texto_estado = 'Pagada';
                                    }
                                    ?>
                                    <span class="badge <?php echo $clase_badge; ?>">
                                        <?php echo $texto_estado; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($estado === 'solicitada' && !empty($compra['id_almacen'])): ?>
                                        <a href="<?php echo BASE_URL; ?>compra/recibir/<?php echo (int)$compra['id_compra']; ?>"
                                            class="btn btn-sm btn-success confirm-receive-btn"
                                            title="Marcar como Recibida">
                                            <i class="fas fa-check"></i> 
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="No disponible o ya recibida">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No hay órdenes de compra registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const confirmReceiveButtons = document.querySelectorAll('.confirm-receive-btn');

        confirmReceiveButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const href = this.href;

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción marcará la orden como recibida y actualizará el stock.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, ¡recibir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });
    });
</script>