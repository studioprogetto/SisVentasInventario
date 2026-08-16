<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-primary"><i class="fas fa-coins"></i> Gestión de Tipos de Moneda</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#monedaModal">
            <i class="fas fa-plus"></i> Agregar Moneda
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Descripción</th>
                        <th>Símbolo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($m = $monedas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['descripcion']); ?></td>
                        <td><strong><?php echo htmlspecialchars($m['simbolo']); ?></strong></td>
                        <td><span class="badge bg-<?php echo $m['activo'] ? 'success' : 'danger'; ?>"><?php echo $m['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning edit-moneda-btn"
                                    data-bs-toggle="modal" data-bs-target="#monedaModal"
                                    data-id="<?php echo $m['id']; ?>"
                                    data-descripcion="<?php echo htmlspecialchars($m['descripcion']); ?>"
                                    data-simbolo="<?php echo htmlspecialchars($m['simbolo']); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($m['activo']): ?>
                                <a href="<?php echo BASE_URL; ?>tipomoneda/cambiarEstado/<?php echo $m['id']; ?>/0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>tipomoneda/cambiarEstado/<?php echo $m['id']; ?>/1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="monedaModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="monedaModalLabel">Agregar Moneda</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>tipomoneda/guardar" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id" id="moneda_id">
            <div class="mb-3"><label for="descripcion" class="form-label">Descripción</label><input type="text" class="form-control" name="descripcion" id="moneda_descripcion" required></div>
            <div class="mb-3"><label for="simbolo" class="form-label">Símbolo</label><input type="text" class="form-control" name="simbolo" id="moneda_simbolo" required></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?php echo BASE_URL; ?>js/tipomoneda-mvc.js"></script>