<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm rounded-4 border-0">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <h4 class="m-0"><i class="fas fa-edit me-2"></i>Registrar / Actualizar T.C.</h4>
            </div>
            <div class="card-body">
                <form action="<?php echo BASE_URL; ?>tipocambio/guardar" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="fecha" class="form-label fw-bold">Fecha</label>
                        <input type="date" class="form-control shadow-sm rounded-3" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_moneda" class="form-label fw-bold">Moneda</label>
                        <select name="id_moneda" id="id_moneda" class="form-select shadow-sm rounded-3" required>
                            <option value="">Seleccione moneda...</option>
                            <?php if (isset($monedas_activas) && $monedas_activas->num_rows > 0): ?>
                                <?php mysqli_data_seek($monedas_activas, 0); ?>
                                <?php while($m = $monedas_activas->fetch_assoc()): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['descripcion']); ?> (<?php echo htmlspecialchars($m['simbolo']); ?>)</option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="valor_soles" class="form-label fw-bold">Valor en Soles (<?php echo getMoneda(); ?>)</label>
                        <input type="number" step="0.0001" class="form-control shadow-sm rounded-3" name="valor_soles" placeholder="Ej: 4.5500" required>
                    </div>
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm"><i class="fas fa-save me-1"></i> Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm rounded-4 border-0">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <h4 class="m-0"><i class="fas fa-list-alt me-2"></i>Historial de Tipos de Cambio</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>Moneda</th>
                                <th>Símbolo</th>
                                <th>Valor en Soles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($tipos_de_cambio) && $tipos_de_cambio->num_rows > 0): ?>
                                <?php while($tc = $tipos_de_cambio->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($tc['fecha'])); ?></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($tc['moneda_descripcion']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($tc['moneda_simbolo']); ?></strong></td>
                                    <td><?php echo getMoneda(); ?><?php echo number_format($tc['valor_soles'], 4); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No hay registros.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-select, .form-control {
        transition: all 0.2s ease-in-out;
    }
    .form-select:focus, .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 8px rgba(13, 110, 253, 0.3);
    }
    .btn-primary {
        transition: all 0.2s ease-in-out;
    }
    .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0b5ed7;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.1);
    }
</style>

<script>
    // Validación Bootstrap
    (function () {
      'use strict'
      var forms = document.querySelectorAll('.needs-validation')
      Array.prototype.slice.call(forms)
        .forEach(function (form) {
          form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
              event.preventDefault()
              event.stopPropagation()
            }
            form.classList.add('was-validated')
          }, false)
        })
    })()
</script>