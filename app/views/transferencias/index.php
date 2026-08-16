<div class="card shadow-lg rounded-4 border-0">
    <div class="card-header  text-primary d-flex align-items-center">
        <h2 class="m-0"><i class="fas fa-exchange-alt me-2"></i>Realizar Transferencia de Stock</h2>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?> alert-dismissible fade show shadow-sm rounded-3">
                <?php echo $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>transferencia/guardar" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="id_producto" class="form-label fw-bold">Producto a Transferir</label>
                <select name="id_producto" class="form-select shadow-sm rounded-3" required>
                    <option value="">Seleccione un producto...</option>
                    <?php while($p = $productos->fetch_assoc()): ?>
                        <option value="<?php echo $p['id_producto']; ?>">
                            <?php echo htmlspecialchars($p['nombre']) . " (Stock: {$p['stock']})"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="id_almacen_origen" class="form-label fw-bold">Almacén de Origen</label>
                    <select name="id_almacen_origen" class="form-select shadow-sm rounded-3" required>
                        <option value="">Seleccione origen...</option>
                        <?php mysqli_data_seek($almacenes, 0); ?>
                        <?php while($a = $almacenes->fetch_assoc()): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="id_almacen_destino" class="form-label fw-bold">Almacén de Destino</label>
                    <select name="id_almacen_destino" class="form-select shadow-sm rounded-3" required>
                        <option value="">Seleccione destino...</option>
                        <?php mysqli_data_seek($almacenes, 0); ?>
                        <?php while($a = $almacenes->fetch_assoc()): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="cantidad" class="form-label fw-bold">Cantidad a Transferir</label>
                <input type="number" name="cantidad" class="form-control shadow-sm rounded-3" min="1" required>
            </div>

            <button type="submit" class="btn btn-primary fw-bold shadow-sm text-white">
                <i class="fas fa-paper-plane me-1"></i> Realizar Transferencia
            </button>
        </form>
    </div>
</div>

<style>
   

    .form-select, .form-control {
        transition: all 0.2s ease-in-out;
    }

    .form-select:focus, .form-control:focus {
        border-color: #3b8ed0;
        box-shadow: 0 0 8px rgba(59, 142, 208, 0.3);
    }
    .alert {
        font-weight: 500;
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