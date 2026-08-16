<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-primary"><i class="fas fa-users"></i> Gestión de Clientes</h2>
        <div>
            <?php
                $url_actual = $_GET['url'] ?? '';
                $viendo_inactivos = strpos($url_actual, 'inactivos') !== false;
            ?>
            <?php if ($viendo_inactivos): ?>
                <a href="<?php echo BASE_URL; ?>cliente" class="btn btn-info">Ver Activos</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>cliente/index/inactivos" class="btn btn-secondary">Ver Inactivos</a>
            <?php endif; ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clienteModal">
                <i class="fas fa-plus"></i> Agregar Cliente
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>Sellos</th>
                        <th>Total Compras</th>
                        <th>Tarjetas Llenas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clientes) && $clientes->num_rows > 0): ?>
                        <?php while($cliente = $clientes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['nombre_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['documento_identidad'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($cliente['telefono'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($cliente['direccion'] ?? '-'); ?></td>
                            <td><?php echo (int)($cliente['sellos'] ?? 0); ?></td>
                            <td><?php echo (int)($cliente['total_compras'] ?? 0); ?></td>
                            <td><?php echo (int)($cliente['tarjetas_llenas'] ?? 0); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $cliente['activo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $cliente['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning edit-cliente-btn" 
                                        data-bs-toggle="modal" data-bs-target="#clienteModal"
                                        data-id="<?php echo $cliente['id_cliente']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($cliente['nombre_cliente']); ?>"
                                        data-documento="<?php echo htmlspecialchars($cliente['documento_identidad'] ?? ''); ?>"
                                        data-telefono="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>"
                                        data-email="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>"
                                        data-direccion="<?php echo htmlspecialchars($cliente['direccion'] ?? ''); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($cliente['activo']): ?>
                                    <a href="<?php echo BASE_URL; ?>cliente/cambiarEstado/<?php echo $cliente['id_cliente']; ?>/0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>cliente/cambiarEstado/<?php echo $cliente['id_cliente']; ?>/1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">No hay clientes registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Cliente -->
<div class="modal fade" id="clienteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalLabel">Agregar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>cliente/guardar" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_cliente" id="id_cliente">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre_cliente" id="nombre_cliente" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Documento</label>
                            <input type="text" class="form-control" name="documento_identidad" id="documento_identidad">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" id="telefono">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea class="form-control" name="direccion" id="direccion" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>js/clientes-mvc.js"></script>