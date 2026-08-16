<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-primary"><i class="fas fa-tags"></i> Gestión de Categorías</h2>
        <div>
            <?php
                $url_actual = $_GET['url'] ?? '';
                $viendo_inactivos = strpos($url_actual, 'inactivos') !== false;
            ?>
            <?php if ($viendo_inactivos): ?>
                <a href="<?php echo BASE_URL; ?>categoria" class="btn btn-info">Ver Activas</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>categoria/index/inactivos" class="btn btn-secondary">Ver Inactivas</a>
            <?php endif; ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoriaModal">
                <i class="fas fa-plus"></i> Agregar Categoría
            </button>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categorias && $categorias->num_rows > 0): ?>
                    <?php while($cat = $categorias->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $cat['activo'] ? 'success' : 'danger'; ?>">
                                <?php echo $cat['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning edit-cat-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#categoriaModal"
                                    data-id="<?php echo $cat['id_categoria']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($cat['nombre']); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <?php if ($cat['activo']): ?>
                                <a href="<?php echo BASE_URL; ?>categoria/cambiarEstado/<?php echo $cat['id_categoria']; ?>/0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>categoria/cambiarEstado/<?php echo $cat['id_categoria']; ?>/1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">No hay categorías registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="categoriaModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="categoriaModalLabel">Agregar Categoría</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>categoria/guardar" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id_categoria" id="id_categoria">
            <div class="mb-3">
                <label class="form-label">Nombre de la Categoría</label>
                <input type="text" class="form-control" name="nombre" id="nombre_categoria" required>
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

<script src="<?php echo BASE_URL; ?>js/categorias-mvc.js"></script>