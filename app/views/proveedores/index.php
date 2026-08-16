<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-primary"><i class="fas fa-truck-loading"></i> Gestión de Proveedores</h2>
        <div>
            <?php
                $url_actual = $_GET['url'] ?? '';
                $viendo_inactivos = strpos($url_actual, 'inactivos') !== false;
            ?>
            <?php if ($viendo_inactivos): ?>
                <a href="<?php echo BASE_URL; ?>proveedor" class="btn btn-info">Ver Activos</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>proveedor/index/inactivos" class="btn btn-secondary">Ver Inactivos</a>
            <?php endif; ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#proveedorModal">
                <i class="fas fa-plus"></i> Agregar Proveedor
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>RUC</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($proveedores && $proveedores->num_rows > 0): ?>
                        <?php while($proveedor = $proveedores->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['ruc']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $proveedor['activo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $proveedor['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning edit-proveedor-btn" 
                                        data-bs-toggle="modal" data-bs-target="#proveedorModal"
                                        data-id="<?php echo $proveedor['id_proveedor']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>"
                                        data-ruc="<?php echo htmlspecialchars($proveedor['ruc']); ?>"
                                        data-telefono="<?php echo htmlspecialchars($proveedor['telefono']); ?>"
                                        data-email="<?php echo htmlspecialchars($proveedor['email']); ?>"
                                        data-direccion="<?php echo htmlspecialchars($proveedor['direccion']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($proveedor['activo']): ?>
                                    <a href="<?php echo BASE_URL; ?>proveedor/cambiarEstado/<?php echo $proveedor['id_proveedor']; ?>/0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>proveedor/cambiarEstado/<?php echo $proveedor['id_proveedor']; ?>/1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No hay proveedores.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedorModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="proveedorModalLabel">Agregar Nuevo Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>proveedor/guardar" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id_proveedor" id="id_proveedor">
            <div class="mb-3"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre_proveedor" id="nombre_proveedor" required></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">RUC</label><input type="text" class="form-control" name="ruc" id="ruc"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="telefono" id="telefono"></div>
            </div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="email"></div>
            <div class="mb-3"><label class="form-label">Dirección</label><textarea class="form-control" name="direccion" id="direccion" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?php echo BASE_URL; ?>js/proveedores-mvc.js"></script>