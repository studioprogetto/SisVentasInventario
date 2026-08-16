<div class="card shadow-lg rounded-4 border-0">
    <div class="card-header d-flex justify-content-between align-items-center text-primary">
        <h2 class="m-0"><i class="fas fa-users-cog me-2"></i>Gestión de Usuarios</h2>
        <button type="button" class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#usuarioModal">
            <i class="fas fa-plus me-1"></i> Agregar Usuario
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-dark text-uppercase text-white">
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Username</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($usuarios) && $usuarios->num_rows > 0): ?>
                        <?php while($usuario = $usuarios->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td class="fw-bold text-start"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_rol']); ?></td>
                            <td>
                                <span class="badge <?php echo $usuario['activo'] ? 'bg-success' : 'bg-danger'; ?> py-2 px-3">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning fw-bold edit-user-btn" 
                                        data-bs-toggle="modal" data-bs-target="#usuarioModal"
                                        data-id="<?php echo $usuario['id_usuario']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>"
                                        data-username="<?php echo htmlspecialchars($usuario['username']); ?>"
                                        data-rol="<?php echo $usuario['id_rol']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($_SESSION['id_usuario'] != $usuario['id_usuario']): ?>
                                    <?php if ($usuario['activo']): ?>
                                        <a href="<?php echo BASE_URL; ?>usuario/cambiarEstado/<?php echo $usuario['id_usuario']; ?>/0" 
                                           class="btn btn-sm btn-danger fw-bold" title="Desactivar">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>usuario/cambiarEstado/<?php echo $usuario['id_usuario']; ?>/1" 
                                           class="btn btn-sm btn-success fw-bold" title="Activar">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay usuarios registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Usuario -->
<div class="modal fade" id="usuarioModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 shadow-lg">
      <div class="modal-header bg-gradient-primary text-white">
        <h5 class="modal-title" id="usuarioModalLabel"><i class="fas fa-user-plus me-2"></i>Agregar Usuario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>usuario/guardar" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <div class="mb-3">
                <label for="nombre_completo" class="form-label fw-bold">Nombre Completo</label>
                <input type="text" class="form-control" name="nombre_completo" id="nombre_completo" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label fw-bold">Username</label>
                <input type="text" class="form-control" name="username" id="username" required>
            </div>
            <div class="mb-3">
                <label for="id_rol" class="form-label fw-bold">Rol</label>
                <select name="id_rol" id="id_rol" class="form-select" required>
                    <?php if (isset($roles) && $roles->num_rows > 0) { mysqli_data_seek($roles, 0); } ?>
                    <?php while($rol = $roles->fetch_assoc()): ?>
                        <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label fw-bold">Contraseña</label>
                <input type="password" class="form-control" name="password" id="password">
                <small class="form-text text-muted">Dejar en blanco para no cambiar la contraseña al editar.</small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary fw-bold">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
    /* Colores gradiente y sombra en tabla */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #6f42c1, #7952cc);
    }

    .table-hover tbody tr:nth-child(odd) {
        background-color: rgba(111,66,193,0.05);
    }
    .table-hover tbody tr:hover {
        background-color: rgba(111,66,193,0.15);
        transform: scale(1.01);
        transition: all 0.2s ease-in-out;
    }

    .badge {
        font-size: 0.9em;
        padding: 0.45em 0.8em;
    }

    .btn-sm {
        border-width: 2px;
    }
</style>

<script src="<?php echo BASE_URL; ?>js/usuarios-mvc.js"></script>