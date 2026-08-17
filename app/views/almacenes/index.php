<div class="container-fluid">
    <!-- Mensajes de éxito/error -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje']['tipo']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['mensaje']['texto']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="m-0 text-primary">
                <i class="fas fa-warehouse me-2"></i>Gestión de Almacenes
            </h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#almacenModal" onclick="limpiarFormulario()">
                <i class="fas fa-plus me-1"></i> Agregar Almacén
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($almacenes)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No hay almacenes registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($almacenes as $almacen): ?>
                            <tr tabindex="0">
                                <td><?php echo $almacen['id']; ?></td>
                                <td><?php echo htmlspecialchars($almacen['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($almacen['ubicacion']); ?></td>
                                <td>
                                    <span class="badge <?php echo $almacen['activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $almacen['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-warning edit-almacen-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#almacenModal"
                                                data-id="<?php echo $almacen['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($almacen['nombre']); ?>"
                                                data-ubicacion="<?php echo htmlspecialchars($almacen['ubicacion']); ?>"
                                                title="Editar almacén">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($almacen['activo']): ?>
                                            <a href="<?php echo BASE_URL; ?>almacen/cambiarEstado/<?php echo $almacen['id']; ?>/0" 
                                               class="btn btn-danger" 
                                               title="Desactivar almacén"
                                               onclick="return confirm('¿Estás seguro de desactivar este almacén?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo BASE_URL; ?>almacen/cambiarEstado/<?php echo $almacen['id']; ?>/1" 
                                               class="btn btn-success" 
                                               title="Activar almacén"
                                               onclick="return confirm('¿Estás seguro de activar este almacén?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar/editar almacén -->
<div class="modal fade" id="almacenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="almacenModalLabel">Agregar Almacén</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>almacen/guardar" method="POST" id="almacenForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="almacen_id">
                    <div class="mb-3">
                        <label for="almacen_nombre" class="form-label">Nombre del Almacén *</label>
                        <input type="text" class="form-control" name="nombre" id="almacen_nombre" required
                               placeholder="Ingrese el nombre del almacén">
                    </div>
                    <div class="mb-3">
                        <label for="almacen_ubicacion" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" name="ubicacion" id="almacen_ubicacion"
                               placeholder="Ingrese la ubicación del almacén">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal" aria-label="Cancelar">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// JavaScript para manejar el modal
document.addEventListener('DOMContentLoaded', function() {
    // Botones de edición
    const editButtons = document.querySelectorAll('.edit-almacen-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const ubicacion = this.getAttribute('data-ubicacion');
            
            document.getElementById('almacen_id').value = id;
            document.getElementById('almacen_nombre').value = nombre;
            document.getElementById('almacen_ubicacion').value = ubicacion;
            document.getElementById('almacenModalLabel').textContent = 'Editar Almacén';
        });
    });
});

function limpiarFormulario() {
    document.getElementById('almacenForm').reset();
    document.getElementById('almacen_id').value = '';
    document.getElementById('almacenModalLabel').textContent = 'Agregar Almacén';
}

// Cerrar modal después de enviar el formulario
document.getElementById('almacenForm').addEventListener('submit', function() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('almacenModal'));
    modal.hide();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.table tbody tr[tabindex]').forEach(function(row){
        row.addEventListener('keydown', function(e){
            if(e.key === 'Enter' || e.key === ' '){
                e.preventDefault();
                var btn = row.querySelector('button, a');
                if(btn) btn.click();
            }
        });
    });
});
</script>