<?php
// Verificar si hay mensajes flash
if (isset($_SESSION['flash_success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . $_SESSION['flash_success'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['flash_success']);
}

if (isset($_SESSION['flash_error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            ' . $_SESSION['flash_error'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['flash_error']);
}
?>

<script>
    // Define la variable MONEDA para que el script la pueda usar
    const MONEDA = '<?php echo getMoneda(); ?>';
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Crear Nueva Orden de Compra</h4>
        <a href="<?php echo BASE_URL; ?>compra" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left"></i> Volver a Compras
        </a>
    </div>
    <div class="card-body">
        <form id="form_guardar_compra" action="<?php echo BASE_URL; ?>compra/guardar" method="POST" onsubmit="return validarFormulario()">
            <div class="row">
                <!-- Columna izquierda: Información básica -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Compra</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="id_proveedor" class="form-label">
                                    <i class="fas fa-truck"></i> Proveedor <span class="text-danger">*</span>
                                </label>
                                <select name="id_proveedor" id="id_proveedor" class="form-select form-select-lg" required>
                                    <option value="">Seleccione un proveedor...</option>
                                    <?php if (isset($proveedores) && $proveedores->num_rows > 0): ?>
                                        <?php while ($proveedor = $proveedores->fetch_assoc()): ?>
                                            <option value="<?php echo $proveedor['id_proveedor']; ?>">
                                                <?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>
                                                <?php if (!empty($proveedor['telefono'])): ?>
                                                    - Tel: <?php echo htmlspecialchars($proveedor['telefono']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <option value="">No hay proveedores disponibles</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="id_almacen" class="form-label">
                                    <i class="fas fa-warehouse"></i> Almacén de Destino <span class="text-danger">*</span>
                                </label>
                                <select name="id_almacen" id="id_almacen" class="form-select form-select-lg" required>
                                    <option value="">Seleccione un almacén...</option>
                                    <?php if (isset($almacenes) && is_array($almacenes) && count($almacenes) > 0): ?>
                                        <?php foreach ($almacenes as $almacen): ?>
                                            <option value="<?php echo $almacen['id']; ?>"
                                                data-ubicacion="<?php echo htmlspecialchars($almacen['ubicacion'] ?? ''); ?>"
                                                data-capacidad="<?php echo htmlspecialchars($almacen['capacidad'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($almacen['nombre']); ?>
                                                <?php if (!empty($almacen['ubicacion'])): ?>
                                                    - <?php echo htmlspecialchars($almacen['ubicacion']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No hay almacenes disponibles</option>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">
                                    <small id="info_almacen" class="text-muted"></small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="observaciones" class="form-label">
                                    <i class="fas fa-sticky-note"></i> Observaciones
                                </label>
                                <textarea name="observaciones" id="observaciones" class="form-control"
                                    rows="3" placeholder="Notas adicionales sobre esta orden de compra..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: Información de productos -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-boxes"></i> Productos a Comprar</h5>
                        </div>
                        <div class="card-body">
                            <!-- Búsqueda de productos -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-search"></i> Buscar Productos
                                </label>
                                <div class="input-group">
                                    <input type="text" id="buscar_producto_compra" class="form-control form-control-lg"
                                        placeholder="Escribe el nombre o código del producto..."
                                        onkeyup="buscarProductos()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="buscarProductos()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div id="resultados_busqueda_compra" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; display: none;"></div>

                                <div class="form-text mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Primero seleccione un almacén para buscar productos específicos de ese almacén.
                                    </small>
                                </div>
                            </div>

                            <!-- Lista de productos seleccionados -->
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="40%">Producto</th>
                                            <th width="20%">Costo</th>
                                            <th width="20%">Cantidad</th>
                                            <th width="15%">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="compra_tbody">
                                        <tr id="sin_productos">
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                                No hay productos agregados
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Resumen -->
                            <div class="alert alert-info mt-3">
                                <div class="row">
                                    <div class="col-6">
                                        <small>Total de productos:</small><br>
                                        <strong id="total_productos">0</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small>Total a pagar:</small><br>
                                        <h4 class="mb-0 text-primary" id="total_compra"><?php echo getMoneda(); ?>0.00</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campos ocultos -->
            <input type="hidden" name="productos_compra" id="productos_compra_input">
            <input type="hidden" name="total_compra" id="total_compra_input">
            <input type="hidden" name="id_usuario" value="<?php echo $_SESSION['id_usuario'] ?? ''; ?>">

            <!-- Botones de acción -->
            <div class="card-footer bg-transparent border-top-0">
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>compra" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg" id="btn_guardar">
                        <i class="fas fa-save"></i> Guardar Orden de Compra
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal para confirmación -->
<div class="modal fade" id="confirmarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning"></i> Confirmar Orden</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de crear esta orden de compra?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> Una vez guardada, podrá marcarla como recibida para actualizar el inventario.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarFormulario()">Sí, guardar orden</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales
    let ordenCompra = [];
    let timeoutBusqueda = null;

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        // Actualizar información del almacén seleccionado
        document.getElementById('id_almacen').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const ubicacion = selected.getAttribute('data-ubicacion');
            const capacidad = selected.getAttribute('data-capacidad');
            let info = '';

            if (ubicacion) info += `Ubicación: ${ubicacion}`;
            if (capacidad) info += ` | Capacidad: ${capacidad}`;

            document.getElementById('info_almacen').textContent = info;
        });

        // Confirmar envío del formulario
        document.getElementById('form_guardar_compra').addEventListener('submit', function(e) {
            e.preventDefault();

            if (ordenCompra.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin productos',
                    text: 'Debe agregar al menos un producto a la orden de compra.'
                });
                return;
            }

            $('#confirmarModal').modal('show');
        });
    });

    // Función para buscar productos
    function buscarProductos() {
        const term = document.getElementById('buscar_producto_compra').value.trim();
        const idAlmacen = document.getElementById('id_almacen').value;
        const resultados = document.getElementById('resultados_busqueda_compra');

        if (term.length < 2) {
            resultados.style.display = 'none';
            return;
        }

        // Verificar si hay almacén seleccionado
        if (!idAlmacen) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione almacén',
                text: 'Por favor seleccione un almacén de destino antes de buscar productos.'
            });
            resultados.style.display = 'none';
            return;
        }

        // Limpiar timeout anterior
        if (timeoutBusqueda) {
            clearTimeout(timeoutBusqueda);
        }

        // Esperar 300ms después de la última tecla
        timeoutBusqueda = setTimeout(() => {
            fetch(`${BASE_URL}compra/buscarProductos?term=${encodeURIComponent(term)}&id_almacen=${idAlmacen}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la búsqueda');
                    return response.json();
                })
                .then(data => {
                    resultados.innerHTML = '';

                    if (data.length === 0) {
                        resultados.innerHTML = `
                        <a href="#" class="list-group-item list-group-item-action disabled">
                            <i class="fas fa-search"></i> No se encontraron productos
                        </a>
                    `;
                    } else {
                        data.forEach((producto, index) => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${producto.nombre}</strong><br>
                                    <small class="text-muted">
                                        Costo: ${MONEDA}${parseFloat(producto.precio_compra || 0).toFixed(2)}
                                    </small>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="agregarAOrden(${producto.id_producto}, '${producto.nombre.replace(/'/g, "\\'")}', ${producto.precio_compra || 0})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        `;
                            resultados.appendChild(item);
                        });
                    }

                    resultados.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultados.innerHTML = `
                    <a href="#" class="list-group-item list-group-item-action disabled text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error en la búsqueda
                    </a>
                `;
                    resultados.style.display = 'block';
                });
        }, 300);
    }

    // Función para agregar producto a la orden
    function agregarAOrden(id, nombre, costo) {
        // Ocultar resultados de búsqueda
        document.getElementById('resultados_busqueda_compra').style.display = 'none';
        document.getElementById('buscar_producto_compra').value = '';

        // Verificar si ya existe
        const existente = ordenCompra.find(item => item.id === id);

        if (existente) {
            existente.cantidad++;
            Swal.fire({
                icon: 'info',
                title: 'Producto actualizado',
                text: `Se aumentó la cantidad de "${nombre}" a ${existente.cantidad}`,
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            ordenCompra.push({
                id: id,
                nombre: nombre,
                costo: parseFloat(costo) || 0,
                cantidad: 1
            });

            Swal.fire({
                icon: 'success',
                title: 'Producto agregado',
                text: `"${nombre}" fue agregado a la orden`,
                timer: 1500,
                showConfirmButton: false
            });
        }

        renderizarOrden();
    }

    // Función para renderizar la orden
    function renderizarOrden() {
        const tbody = document.getElementById('compra_tbody');
        const totalCompraSpan = document.getElementById('total_compra');
        const totalProductosSpan = document.getElementById('total_productos');
        const productosCompraInput = document.getElementById('productos_compra_input');
        const totalCompraInput = document.getElementById('total_compra_input');
        const sinProductosRow = document.getElementById('sin_productos');

        // Limpiar tabla
        tbody.innerHTML = '';

        if (ordenCompra.length === 0) {
            tbody.appendChild(sinProductosRow);
            sinProductosRow.style.display = '';
        } else {
            sinProductosRow.style.display = 'none';

            let total = 0;
            let totalProductos = 0;

            ordenCompra.forEach((item, index) => {
                totalProductos += item.cantidad;
                const subtotal = item.costo * item.cantidad;
                total += subtotal;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${index + 1}</td>
                <td>${item.nombre}</td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">${MONEDA}</span>
                        <input type="number" step="0.01" min="0" value="${item.costo.toFixed(2)}" 
                               class="form-control costo-input" data-index="${index}"
                               onchange="actualizarCosto(${index}, this.value)">
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <button class="btn btn-outline-secondary" type="button" onclick="cambiarCantidad(${index}, -1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" min="1" value="${item.cantidad}" 
                               class="form-control text-center cantidad-input" data-index="${index}"
                               onchange="actualizarCantidad(${index}, this.value)">
                        <button class="btn btn-outline-secondary" type="button" onclick="cambiarCantidad(${index}, 1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
                tbody.appendChild(tr);
            });

            // Actualizar resumen
            totalCompraSpan.textContent = `${MONEDA}${total.toFixed(2)}`;
            totalProductosSpan.textContent = totalProductos;

            // Actualizar campos ocultos
            productosCompraInput.value = JSON.stringify(ordenCompra);
            totalCompraInput.value = total.toFixed(2);
        }
    }

    // Funciones auxiliares
    function actualizarCosto(index, valor) {
        if (ordenCompra[index]) {
            ordenCompra[index].costo = parseFloat(valor) || 0;
            renderizarOrden();
        }
    }

    function actualizarCantidad(index, valor) {
        if (ordenCompra[index]) {
            const cantidad = parseInt(valor) || 1;
            if (cantidad < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cantidad inválida',
                    text: 'La cantidad mínima es 1'
                });
                ordenCompra[index].cantidad = 1;
            } else {
                ordenCompra[index].cantidad = cantidad;
            }
            renderizarOrden();
        }
    }

    function cambiarCantidad(index, cambio) {
        if (ordenCompra[index]) {
            const nuevaCantidad = ordenCompra[index].cantidad + cambio;
            if (nuevaCantidad < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cantidad inválida',
                    text: 'La cantidad mínima es 1'
                });
                return;
            }
            ordenCompra[index].cantidad = nuevaCantidad;
            renderizarOrden();
        }
    }

    function eliminarProducto(index) {
        Swal.fire({
            title: '¿Eliminar producto?',
            text: `¿Está seguro de eliminar "${ordenCompra[index].nombre}" de la orden?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const productoEliminado = ordenCompra.splice(index, 1)[0];
                renderizarOrden();

                Swal.fire({
                    icon: 'success',
                    title: 'Producto eliminado',
                    text: `"${productoEliminado.nombre}" fue removido de la orden`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    }

    // Validar formulario
    function validarFormulario() {
        const proveedor = document.getElementById('id_proveedor').value;
        const almacen = document.getElementById('id_almacen').value;

        if (!proveedor) {
            Swal.fire({
                icon: 'error',
                title: 'Proveedor requerido',
                text: 'Por favor seleccione un proveedor.'
            });
            return false;
        }

        if (!almacen) {
            Swal.fire({
                icon: 'error',
                title: 'Almacén requerido',
                text: 'Por favor seleccione un almacén de destino.'
            });
            return false;
        }

        if (ordenCompra.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Productos requeridos',
                text: 'Debe agregar al menos un producto a la orden de compra.'
            });
            return false;
        }

        return true;
    }

    // Enviar formulario
    function enviarFormulario() {
        $('#confirmarModal').modal('hide');

        // Mostrar carga
        const btnGuardar = document.getElementById('btn_guardar');
        const originalText = btnGuardar.innerHTML;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        btnGuardar.disabled = true;

        // Enviar formulario
        document.getElementById('form_guardar_compra').submit();
    }

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        const resultados = document.getElementById('resultados_busqueda_compra');
        const buscarInput = document.getElementById('buscar_producto_compra');

        if (!resultados.contains(e.target) && e.target !== buscarInput) {
            resultados.style.display = 'none';
        }
    });

    // Presionar Enter en búsqueda
    document.getElementById('buscar_producto_compra').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarProductos();
        }
    });
</script>

<style>
    .costo-input,
    .cantidad-input {
        max-width: 100px;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    #resultados_busqueda_compra {
        z-index: 1050;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .input-group-text {
        background-color: #f8f9fa;
    }

    .table th {
        font-weight: 600;
        font-size: 0.875rem;
    }

    .table td {
        vertical-align: middle;
    }
</style>