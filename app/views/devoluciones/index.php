<div class="card shadow-sm rounded-4 border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-primary"><i class="fas fa-exchange-alt me-2"></i>Gestión de Cambios y Devoluciones</h2>
        <div>
            <a href="<?php echo BASE_URL; ?>venta/historial" class="btn btn-secondary">Volver al Historial de Ventas</a>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>venta" class="btn btn-primary fw-bold">
                <i class="fas fa-cash-register me-1"></i> Volver al POS
            </a>
        </div>
    </div>

    <div class="card-body">
        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">Resumen de Cambios/Devoluciones</h5>
                        <?php if (!empty($estadisticas)): ?>
                            <?php foreach ($estadisticas as $est): ?>
                                <div class="d-flex justify-content-between">
                                    <span><?php echo ucfirst($est['tipo']); ?>s:</span>
                                    <strong><?php echo $est['total']; ?>
                                        (<?php echo getMoneda() . number_format($est['total_monto'], 2); ?>)</strong>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No hay cambios/devoluciones registrados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Búsqueda Rápida</h5>
                        <div class="input-group">
                            <input type="text" id="buscarVentaCambio" class="form-control"
                                placeholder="Buscar por ID venta, cliente o DNI...">
                            <button class="btn btn-warning" type="button" onclick="buscarVentasParaCambio()">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de ventas recientes -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="m-0">Ventas Recientes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID Venta</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ventas)): ?>
                                <?php foreach (array_slice($ventas, 0, 10) as $venta): ?>
                                    <?php
                                    $tieneCambios = !empty($venta['tiene_cambios']);
                                    $estadoColor = $tieneCambios ? 'warning' : 'success';
                                    $estadoTexto = $tieneCambios ? 'Con cambios' : 'Normal';
                                    ?>
                                    <tr id="venta-<?php echo $venta['id_venta']; ?>"
                                        class="<?php echo $tieneCambios ? 'table-warning' : ''; ?>">
                                        <td class="fw-bold">
                                            <?php echo $venta['id_venta']; ?>
                                            <?php if ($tieneCambios): ?>
                                                <span class="badge bg-warning ms-1" title="Esta venta tiene cambios/devoluciones">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                                        <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
                                        <td><?php echo getMoneda() . number_format($venta['total_venta'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $estadoColor; ?>">
                                                <?php echo $estadoTexto; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($venta['id_cliente'])): ?>
                                                <button class="btn btn-sm btn-warning"
                                                    onclick="mostrarModalCambioDevolucion(<?php echo $venta['id_venta']; ?>, <?php echo $venta['id_cliente']; ?>, <?php echo $tieneCambios ? 'true' : 'false'; ?>)">
                                                    <i class="fas fa-exchange-alt"></i>
                                                    <?php echo $tieneCambios ? 'Ver/Agregar' : 'Gestionar'; ?>
                                                </button>
                                                <?php if ($tieneCambios): ?>
                                                    <button class="btn btn-sm btn-info ms-1"
                                                        onclick="mostrarHistorialVenta(<?php echo $venta['id_venta']; ?>)">
                                                        <i class="fas fa-history"></i> Historial
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Venta genérica</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No hay ventas recientes</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resultados de búsqueda -->
<div id="resultadosBusqueda" class="mt-3" style="display: none;">
</div>

<!-- Modal para Cambio/Devolución -->
<div class="modal fade" id="modalCambioDevolucion" tabindex="-1" aria-labelledby="modalCambioDevolucionLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalCambioDevolucionLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Gestión de Cambio/Devolución - Venta #<span
                        id="numeroVentaModal">0</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Alert para ventas con cambios existentes -->
                <div id="alertCambiosExistentes" class="alert alert-info d-none">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Esta venta ya tiene cambios/devoluciones registrados.</strong>
                    Puede agregar nuevos cambios o ver el historial completo.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="m-0">Productos de la Venta Original</h6>
                                <span class="badge bg-primary" id="totalProductosOriginal">0 productos</span>
                            </div>
                            <div class="card-body">
                                <div id="productosDevolverContainer" style="max-height: 400px; overflow-y: auto;">
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando productos...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="m-0">Productos para Cambio</h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="soloDevolucion"
                                            onchange="toggleSoloDevolucion()">
                                        <label class="form-check-label small" for="soloDevolucion">Solo
                                            Devolución</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <input type="text" id="buscarProductoCambio" class="form-control"
                                        placeholder="Buscar productos disponibles...">
                                </div>
                                <div id="listaProductosCambio" style="max-height: 350px; overflow-y: auto;">
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-search fa-2x mb-2"></i><br>
                                        Busque productos para agregar
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Totales -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="m-0">Resumen del Cambio</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <h6>Total a Devolver</h6>
                                        <h4 id="totalDevolver" class="text-danger">S/. 0.00</h4>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Total Nuevos Productos</h6>
                                        <h4 id="totalNuevos" class="text-success">S/. 0.00</h4>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Saldo</h6>
                                        <h4 id="saldoCalculado" class="text-primary">S/. 0.00</h4>
                                        <small id="mensajeSaldo" class="text-muted">Sin transacciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observación -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="observacionCambio" class="form-label">Observación:</label>
                        <textarea class="form-control" id="observacionCambio" rows="3"
                            placeholder="Ingrese observaciones sobre el cambio o devolución..."></textarea>
                    </div>
                </div>

                <!-- Historial de cambios existentes -->
                <div class="row mt-4" id="historialCambiosContainer" style="display: none;">
                    <div class="col-md-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="m-0"><i class="fas fa-history me-2"></i>Cambios Anteriores</h6>
                            </div>
                            <div class="card-body">
                                <div id="listaCambiosExistentes" style="max-height: 200px; overflow-y: auto;">
                                    <!-- Los cambios existentes se cargarán aquí -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-info" onclick="mostrarHistorialVenta(ventaActual)"
                    id="btnVerHistorialCompleto" style="display: none;">
                    <i class="fas fa-history me-1"></i> Ver Historial Completo
                </button>
                <button type="button" class="btn btn-danger" onclick="procesarDevolucion()" id="btnProcesarDevolucion">
                    <i class="fas fa-undo me-1"></i> Procesar Devolución
                </button>
                <button type="button" class="btn btn-warning" onclick="procesarCambio()" id="btnProcesarCambio">
                    <i class="fas fa-exchange-alt me-1"></i> Procesar Cambio
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Historial de Venta -->
<div class="modal fade" id="modalHistorialVenta" tabindex="-1" aria-labelledby="modalHistorialVentaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-studio text-white">
                <h5 class="modal-title" id="modalHistorialVentaLabel">
                    <i class="fas fa-history me-2"></i>Historial de Cambios - Venta #<span
                        id="numeroVentaHistorial">0</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoHistorialVenta">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin"></i> Cargando historial...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalle de Cambio -->
<div class="modal fade" id="modalDetalleCambio" tabindex="-1" aria-labelledby="modalDetalleCambioLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalDetalleCambioLabel">
                    <i class="fas fa-eye me-2"></i>Detalle del Cambio #<span id="numeroCambioDetalle">0</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoDetalleCambio">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin"></i> Cargando detalle...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales
    let ventaActual = null;
    let clienteActual = null;
    let ventaTieneCambios = false;
    let productosDevolver = [];
    let productosNuevos = [];
    let productosDisponibles = [];

    // Obtener BASE_URL desde PHP
    const BASE_URL = '<?php echo BASE_URL; ?>';

    // Función mejorada para manejar respuestas fetch
    async function fetchJSON(url, options = {}) {
        try {
            const response = await fetch(url, options);
            const text = await response.text();

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${text}`);
            }

            try {
                return JSON.parse(text);
            } catch (parseError) {
                console.error('Error parsing JSON:', parseError, 'Response text:', text);
                throw new Error('Respuesta del servidor no es JSON válido: ' + text.substring(0, 100));
            }
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    }

    // Función para buscar ventas
    async function buscarVentasParaCambio() {
        const termino = document.getElementById('buscarVentaCambio').value;

        if (termino.length < 2) {
            mostrarNotificacion('Ingrese al menos 2 caracteres para buscar', 'warning');
            return;
        }

        try {
            const data = await fetchJSON(`${BASE_URL}devoluciones/buscarVentas?term=${encodeURIComponent(termino)}`);
            const container = document.getElementById('resultadosBusqueda');

            if (data.error) {
                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            } else if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">No se encontraron ventas</div>';
            } else {
                let html = '<div class="card"><div class="card-header"><h6>Resultados de búsqueda</h6></div><div class="card-body"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';

                data.forEach(venta => {
                    const tieneCambios = venta.tiene_cambios || false;
                    html += `
                    <tr class="${tieneCambios ? 'table-warning' : ''}">
                        <td>${venta.id_venta} ${tieneCambios ? '<span class="badge bg-warning ms-1"><i class="fas fa-exchange-alt"></i></span>' : ''}</td>
                        <td>${new Date(venta.fecha_venta).toLocaleDateString()}</td>
                        <td>${venta.nombre_cliente || 'Venta genérica'}</td>
                        <td>${getMoneda()}${parseFloat(venta.total_venta).toFixed(2)}</td>
                        <td><span class="badge bg-${tieneCambios ? 'warning' : 'success'}">${tieneCambios ? 'Con cambios' : 'Normal'}</span></td>
                        <td>
                            ${venta.nombre_cliente ?
                            `<button class="btn btn-sm btn-warning" onclick="mostrarModalCambioDevolucion(${venta.id_venta}, ${venta.id_cliente || 'null'}, ${tieneCambios})">
                                    <i class="fas fa-exchange-alt"></i> ${tieneCambios ? 'Ver/Agregar' : 'Gestionar'}
                                </button>` :
                            '<span class="text-muted">Venta genérica</span>'
                        }
                        </td>
                    </tr>
                `;
                });

                html += '</tbody></table></div></div></div>';
                container.innerHTML = html;
            }

            container.style.display = 'block';
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error en la búsqueda: ' + error.message, 'error');
        }
    }

    // Función para obtener símbolo de moneda
    function getMoneda() {
        return 'S/.';
    }

    // Función para mostrar modal de cambio/devolución
    function mostrarModalCambioDevolucion(idVenta, idCliente, tieneCambios = false) {
        console.log('Abriendo modal para venta:', idVenta, 'cliente:', idCliente, 'tieneCambios:', tieneCambios);

        ventaActual = idVenta;
        clienteActual = idCliente;
        ventaTieneCambios = tieneCambios;

        // Actualizar número de venta en el modal
        document.getElementById('numeroVentaModal').textContent = idVenta;

        // Mostrar/ocultar elementos según si tiene cambios
        const alertCambios = document.getElementById('alertCambiosExistentes');
        const btnHistorial = document.getElementById('btnVerHistorialCompleto');
        const historialContainer = document.getElementById('historialCambiosContainer');

        if (tieneCambios) {
            alertCambios.classList.remove('d-none');
            btnHistorial.style.display = 'inline-block';
            historialContainer.style.display = 'block';
            cargarCambiosExistentes(idVenta);
        } else {
            alertCambios.classList.add('d-none');
            btnHistorial.style.display = 'none';
            historialContainer.style.display = 'none';
        }

        // Resetear estado
        productosDevolver = [];
        productosNuevos = [];

        // Resetear UI
        document.getElementById('soloDevolucion').checked = false;
        document.getElementById('observacionCambio').value = '';
        document.getElementById('buscarProductoCambio').value = '';

        // Resetear totales
        document.getElementById('totalDevolver').textContent = 'S/. 0.00';
        document.getElementById('totalNuevos').textContent = 'S/. 0.00';
        document.getElementById('saldoCalculado').textContent = 'S/. 0.00';
        document.getElementById('mensajeSaldo').textContent = 'Sin transacciones';
        document.getElementById('mensajeSaldo').className = 'text-muted';

        // Cargar productos de la venta
        cargarProductosVenta(idVenta);

        // Limpiar lista de productos disponibles
        document.getElementById('listaProductosCambio').innerHTML = `
        <div class="text-center text-muted py-3">
            <i class="fas fa-search fa-2x mb-2"></i><br>
            Busque productos para agregar
        </div>
    `;

        // Resetear sección de productos nuevos
        const seccionNuevos = document.querySelector('.col-md-6:last-child .card');
        seccionNuevos.style.opacity = '1';
        seccionNuevos.style.pointerEvents = 'auto';

        const modalElement = document.getElementById('modalCambioDevolucion');
        if (!modalElement) {
            console.error('Modal no encontrado en el DOM');
            mostrarNotificacion('Error: Modal no encontrado', 'error');
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Función para cargar productos de la venta original
    async function cargarProductosVenta(idVenta) {
        try {
            const data = await fetchJSON(`${BASE_URL}venta/obtenerDetallesVenta/${idVenta}`);
            const container = document.getElementById('productosDevolverContainer');
            container.innerHTML = '';

            if (data.error || !Array.isArray(data)) {
                container.innerHTML = `<div class="alert alert-danger">${data.error || 'Error en los datos recibidos'}</div>`;
                console.error('Error en datos:', data);
                return;
            }

            productosDevolver = data.map(producto => ({
                ...producto,
                cantidad_seleccionada: 0,
                cantidad_disponible: producto.cantidad - (producto.cantidad_devuelta || 0)
            }));

            // Actualizar contador de productos
            document.getElementById('totalProductosOriginal').textContent = `${productosDevolver.length} productos`;

            if (productosDevolver.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3">No hay productos en esta venta</div>';
                return;
            }

            productosDevolver.forEach((producto, index) => {
                const div = document.createElement('div');
                div.className = 'producto-cambio mb-3 p-3 border rounded';
                div.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input producto-check" type="checkbox" 
                           data-index="${index}" 
                           ${producto.cantidad_disponible <= 0 ? 'disabled' : ''}
                           onchange="toggleProductoDevolucion(${index})">
                    <label class="form-check-label fw-bold">
                        ${producto.nombre || 'Producto sin nombre'}
                        ${producto.cantidad_devuelta > 0 ? `<span class="badge bg-info ms-1">Devuelto: ${producto.cantidad_devuelta}</span>` : ''}
                    </label>
                </div>
                <div class="row mt-2">
                    <div class="col-4">
                        <small class="text-muted">Precio: ${getMoneda()}${parseFloat(producto.precio_venta || 0).toFixed(2)}</small>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Disponible: ${producto.cantidad_disponible}</small>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Vendido: ${producto.cantidad || 0}</small>
                    </div>
                </div>
                <div class="mt-2" id="controles-${index}" style="display: none;">
                    <label class="form-label small">Cantidad a devolver:</label>
                    <input type="number" class="form-control form-control-sm cantidad-control" 
                           min="1" max="${producto.cantidad_disponible}" value="1"
                           onchange="actualizarCantidadDevolucion(${index}, this.value)"
                           ${producto.cantidad_disponible <= 0 ? 'disabled' : ''}>
                    ${producto.cantidad_disponible <= 0 ? '<small class="text-danger">No hay cantidad disponible para devolver</small>' : ''}
                </div>
            `;
                container.appendChild(div);
            });
        } catch (error) {
            console.error('Error al cargar productos:', error);
            const container = document.getElementById('productosDevolverContainer');
            container.innerHTML = `<div class="alert alert-danger">Error al cargar productos: ${error.message}</div>`;
            mostrarNotificacion('Error al cargar productos de la venta', 'error');
        }
    }

    // Función para cargar cambios existentes
    async function cargarCambiosExistentes(idVenta) {
        try {
            const data = await fetchJSON(`${BASE_URL}devoluciones/historialVenta/${idVenta}`);
            const container = document.getElementById('listaCambiosExistentes');

            if (data.error || !Array.isArray(data)) {
                container.innerHTML = '<div class="text-center text-muted py-3">Error al cargar cambios existentes</div>';
                return;
            }

            if (data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3">No hay cambios registrados</div>';
                return;
            }

            let html = '';
            data.forEach(cambio => {
                html += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong class="text-${cambio.tipo === 'devolucion' ? 'danger' : 'warning'}">
                            ${cambio.tipo === 'devolucion' ? 'Devolución' : 'Cambio'}
                        </strong>
                        <small class="text-muted">${new Date(cambio.fecha_cambio).toLocaleDateString()}</small>
                    </div>
                    <div class="small">
                        <span class="badge bg-${cambio.estado === 'completado' ? 'success' : 'warning'}">${cambio.estado}</span>
                        Monto: ${getMoneda()}${parseFloat(cambio.monto_saldo || 0).toFixed(2)}
                    </div>
                    ${cambio.observacion ? `<div class="small text-muted">${cambio.observacion}</div>` : ''}
                </div>
            `;
            });

            container.innerHTML = html;
        } catch (error) {
            console.error('Error al cargar cambios existentes:', error);
            document.getElementById('listaCambiosExistentes').innerHTML = '<div class="text-center text-muted py-3">Error al cargar cambios</div>';
        }
    }

    // Función para mostrar historial de una venta específica
    async function mostrarHistorialVenta(idVenta) {
        document.getElementById('numeroVentaHistorial').textContent = idVenta;

        try {
            const data = await fetchJSON(`${BASE_URL}devoluciones/historialCompletoVenta/${idVenta}`);
            const container = document.getElementById('contenidoHistorialVenta');

            if (data.error) {
                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            } else if (data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4">No hay cambios registrados para esta venta</div>';
            } else {
                let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
                html += '<thead><tr><th>Fecha</th><th>Tipo</th><th>Productos Devueltos</th><th>Productos Nuevos</th><th>Monto Saldo</th><th>Estado</th><th>Observación</th><th>Acciones</th></tr></thead><tbody>';

                data.forEach(cambio => {
                    html += `
                    <tr>
                        <td>${new Date(cambio.fecha_cambio).toLocaleString()}</td>
                        <td><span class="badge ${cambio.tipo === 'cambio' ? 'bg-warning' : 'bg-danger'}">${cambio.tipo}</span></td>
                        <td>${cambio.productos_devueltos || '-'}</td>
                        <td>${cambio.productos_nuevos || '-'}</td>
                        <td>${getMoneda()}${parseFloat(cambio.monto_saldo).toFixed(2)}</td>
                        <td><span class="badge bg-${cambio.estado === 'completado' ? 'success' : 'warning'}">${cambio.estado}</span></td>
                        <td>${cambio.observacion || '-'}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalleCambio(${cambio.id_cambio})">
                                <i class="fas fa-eye"></i> Ver Detalle
                            </button>
                        </td>
                    </tr>
                `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
            }

            // Cerrar modal de gestión si está abierto
            const modalGestion = bootstrap.Modal.getInstance(document.getElementById('modalCambioDevolucion'));
            if (modalGestion) {
                modalGestion.hide();
            }

            const modal = new bootstrap.Modal(document.getElementById('modalHistorialVenta'));
            modal.show();
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al cargar historial: ' + error.message, 'error');
        }
    }

    // Función para ver detalle de un cambio específico
    async function verDetalleCambio(idCambio) {
        try {
            const data = await fetchJSON(`${BASE_URL}devoluciones/detalleCambio/${idCambio}`);
            
            if (data.success) {
                document.getElementById('numeroCambioDetalle').textContent = idCambio;
                const container = document.getElementById('contenidoDetalleCambio');
                
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="m-0">Productos Devueltos</h6>
                                </div>
                                <div class="card-body">
                `;
                
                if (data.detalle.productos_devueltos && data.detalle.productos_devueltos.length > 0) {
                    html += '<ul class="list-group list-group-flush">';
                    data.detalle.productos_devueltos.forEach(producto => {
                        html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${producto.nombre}</strong><br>
                                    <small class="text-muted">Cantidad: ${producto.cantidad}</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">${getMoneda()}${parseFloat(producto.precio).toFixed(2)}</span>
                            </li>
                        `;
                    });
                    html += '</ul>';
                } else {
                    html += '<p class="text-muted">No hay productos devueltos</p>';
                }
                
                html += `
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="m-0">Productos Nuevos</h6>
                                </div>
                                <div class="card-body">
                `;
                
                if (data.detalle.productos_nuevos && data.detalle.productos_nuevos.length > 0) {
                    html += '<ul class="list-group list-group-flush">';
                    data.detalle.productos_nuevos.forEach(producto => {
                        html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${producto.nombre}</strong><br>
                                    <small class="text-muted">Cantidad: ${producto.cantidad}</small>
                                </div>
                                <span class="badge bg-success rounded-pill">${getMoneda()}${parseFloat(producto.precio).toFixed(2)}</span>
                            </li>
                        `;
                    });
                    html += '</ul>';
                } else {
                    html += '<p class="text-muted">No hay productos nuevos</p>';
                }
                
                html += `
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0">Resumen del Cambio</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <h6>Total Devuelto</h6>
                                            <h4 class="text-danger">${getMoneda()}${parseFloat(data.detalle.total_devolucion || 0).toFixed(2)}</h4>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Total Nuevos</h6>
                                            <h4 class="text-success">${getMoneda()}${parseFloat(data.detalle.total_nuevos || 0).toFixed(2)}</h4>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Saldo Final</h6>
                                            <h4 class="text-primary">${getMoneda()}${parseFloat(data.detalle.saldo_final || 0).toFixed(2)}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.innerHTML = html;
                
                const modal = new bootstrap.Modal(document.getElementById('modalDetalleCambio'));
                modal.show();
            } else {
                mostrarNotificacion(data.error || 'Error al cargar detalle del cambio', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al cargar detalle del cambio: ' + error.message, 'error');
        }
    }

    // Función para buscar productos disponibles
    async function buscarProductosDisponibles(termino) {
        if (!termino || termino.length < 2) {
            document.getElementById('listaProductosCambio').innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="fas fa-search fa-2x mb-2"></i><br>
                Ingrese al menos 2 caracteres para buscar
            </div>
        `;
            return;
        }

        try {
            const data = await fetchJSON(`${BASE_URL}venta/buscar?term=${encodeURIComponent(termino)}`);
            const container = document.getElementById('listaProductosCambio');

            if (!Array.isArray(data) || data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3">No se encontraron productos disponibles</div>';
                return;
            }

            // Filtrar solo productos con stock
            productosDisponibles = data.filter(item =>
                item.tipo === 'producto' &&
                (item.stock > 0)
            );

            if (productosDisponibles.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3">No hay productos con stock disponible</div>';
                return;
            }

            container.innerHTML = '';
            productosDisponibles.forEach((producto, index) => {
                const div = document.createElement('div');
                div.className = 'producto-disponible mb-2 p-2 border rounded';
                div.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input producto-nuevo-check" type="checkbox" 
                           data-index="${index}" onchange="toggleProductoNuevo(${index})">
                    <label class="form-check-label fw-bold">${producto.nombre}</label>
                </div>
                <div class="row mt-1">
                    <div class="col-6">
                        <small class="text-muted">Precio: ${getMoneda()}${parseFloat(producto.precio_venta).toFixed(2)}</small>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Stock: ${producto.stock}</small>
                    </div>
                </div>
                <div class="mt-2" id="controles-nuevo-${index}" style="display: none;">
                    <label class="form-label small">Cantidad:</label>
                    <input type="number" class="form-control form-control-sm cantidad-control" 
                           min="1" max="${producto.stock}" value="1"
                           onchange="actualizarCantidadNuevo(${index}, this.value)">
                </div>
            `;
                container.appendChild(div);
            });
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('listaProductosCambio').innerHTML =
                '<div class="alert alert-danger">Error al cargar productos</div>';
        }
    }

    // Funciones auxiliares para productos a devolver
    function toggleProductoDevolucion(index) {
        const checkbox = document.querySelector(`.producto-check[data-index="${index}"]`);
        const controles = document.getElementById(`controles-${index}`);

        if (checkbox.checked) {
            controles.style.display = 'block';
            productosDevolver[index].cantidad_seleccionada = 1;
        } else {
            controles.style.display = 'none';
            productosDevolver[index].cantidad_seleccionada = 0;
        }

        calcularTotales();
    }

    function actualizarCantidadDevolucion(index, cantidad) {
        const maxCantidad = productosDevolver[index].cantidad_disponible;
        const cantidadValida = Math.min(Math.max(1, parseInt(cantidad)), maxCantidad);
        productosDevolver[index].cantidad_seleccionada = cantidadValida;
        calcularTotales();
    }

    // Funciones auxiliares para productos nuevos
    function toggleProductoNuevo(index) {
        const checkbox = document.querySelector(`.producto-nuevo-check[data-index="${index}"]`);
        const controles = document.getElementById(`controles-nuevo-${index}`);

        if (checkbox.checked) {
            controles.style.display = 'block';
            productosNuevos[index] = {
                ...productosDisponibles[index],
                cantidad_seleccionada: 1
            };
        } else {
            controles.style.display = 'none';
            delete productosNuevos[index];
        }

        calcularTotales();
    }

    function actualizarCantidadNuevo(index, cantidad) {
        const maxCantidad = productosDisponibles[index].stock;
        const cantidadValida = Math.min(Math.max(1, parseInt(cantidad)), maxCantidad);
        if (productosNuevos[index]) {
            productosNuevos[index].cantidad_seleccionada = cantidadValida;
        }
        calcularTotales();
    }

    function toggleSoloDevolucion() {
        const soloDevolucion = document.getElementById('soloDevolucion').checked;
        const seccionNuevos = document.querySelector('.col-md-6:last-child .card');

        if (soloDevolucion) {
            seccionNuevos.style.opacity = '0.5';
            seccionNuevos.style.pointerEvents = 'none';
            // Limpiar productos nuevos
            productosNuevos = [];
            document.querySelectorAll('.producto-nuevo-check').forEach(checkbox => {
                checkbox.checked = false;
                const index = checkbox.dataset.index;
                const controles = document.getElementById(`controles-nuevo-${index}`);
                if (controles) controles.style.display = 'none';
            });
        } else {
            seccionNuevos.style.opacity = '1';
            seccionNuevos.style.pointerEvents = 'auto';
        }

        calcularTotales();
    }

    function calcularTotales() {
        let totalDevolver = 0;
        let totalNuevos = 0;

        // Calcular total de productos a devolver
        productosDevolver.forEach(producto => {
            totalDevolver += producto.precio_venta * producto.cantidad_seleccionada;
        });

        // Calcular total de productos nuevos
        Object.values(productosNuevos).forEach(producto => {
            if (producto && producto.cantidad_seleccionada) {
                totalNuevos += producto.precio_venta * producto.cantidad_seleccionada;
            }
        });

        const saldo = totalDevolver - totalNuevos;

        document.getElementById('totalDevolver').textContent = `${getMoneda()}${totalDevolver.toFixed(2)}`;
        document.getElementById('totalNuevos').textContent = `${getMoneda()}${totalNuevos.toFixed(2)}`;

        const saldoElement = document.getElementById('saldoCalculado');
        saldoElement.textContent = `${getMoneda()}${Math.abs(saldo).toFixed(2)}`;

        const mensajeSaldo = document.getElementById('mensajeSaldo');
        if (saldo > 0) {
            saldoElement.className = 'text-success';
            mensajeSaldo.textContent = `El cliente recibirá ${getMoneda()}${saldo.toFixed(2)} en saldo`;
            mensajeSaldo.className = 'text-success';
        } else if (saldo < 0) {
            saldoElement.className = 'text-danger';
            mensajeSaldo.textContent = `El cliente debe pagar ${getMoneda()}${Math.abs(saldo).toFixed(2)} adicional`;
            mensajeSaldo.className = 'text-danger';
        } else {
            saldoElement.className = 'text-primary';
            mensajeSaldo.textContent = 'Cambio equilibrado - sin saldo pendiente';
            mensajeSaldo.className = 'text-primary';
        }
    }

    // Funciones para procesar cambios y devoluciones
    async function procesarCambio() {
        const productosADevolver = productosDevolver
            .filter(p => p.cantidad_seleccionada > 0)
            .map(p => ({
                id_producto: p.id_producto,
                cantidad: p.cantidad_seleccionada,
                precio: p.precio_venta,
                nombre: p.nombre
            }));

        const productosANuevos = Object.values(productosNuevos)
            .filter(p => p && p.cantidad_seleccionada > 0)
            .map(p => ({
                id_producto: p.id,
                cantidad: p.cantidad_seleccionada,
                precio: p.precio_venta,
                nombre: p.nombre
            }));

        if (productosADevolver.length === 0) {
            mostrarNotificacion('Seleccione al menos un producto para devolver', 'error');
            return;
        }

        if (productosANuevos.length === 0) {
            mostrarNotificacion('Para un cambio debe seleccionar productos nuevos', 'error');
            return;
        }

        const datos = {
            id_venta_original: ventaActual,
            id_cliente: clienteActual,
            productos_originales: productosADevolver,
            productos_nuevos: productosANuevos,
            observacion: document.getElementById('observacionCambio').value
        };

        try {
            const data = await fetchJSON(`${BASE_URL}venta/procesarCambio`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });

            if (data.success) {
                mostrarNotificacion(data.mensaje, 'success');
                // Actualizar la interfaz
                actualizarInterfazDespuesCambio(ventaActual, true);
                // Cerrar modal y recargar página después de un tiempo
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalCambioDevolucion')).hide();
                    location.reload();
                }, 2000);
            } else {
                mostrarNotificacion(data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al procesar el cambio: ' + error.message, 'error');
        }
    }

    async function procesarDevolucion() {
        const productosADevolver = productosDevolver
            .filter(p => p.cantidad_seleccionada > 0)
            .map(p => ({
                id_producto: p.id_producto,
                cantidad: p.cantidad_seleccionada,
                precio: p.precio_venta,
                nombre: p.nombre
            }));

        if (productosADevolver.length === 0) {
            mostrarNotificacion('Seleccione al menos un producto para devolver', 'error');
            return;
        }

        const datos = {
            id_venta_original: ventaActual,
            id_cliente: clienteActual,
            productos_devolver: productosADevolver,
            observacion: document.getElementById('observacionCambio').value
        };

        try {
            const data = await fetchJSON(`${BASE_URL}venta/procesarDevolucion`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });

            if (data.success) {
                mostrarNotificacion(data.mensaje, 'success');
                // Actualizar la interfaz
                actualizarInterfazDespuesCambio(ventaActual, true);
                // Cerrar modal y recargar página después de un tiempo
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalCambioDevolucion')).hide();
                    location.reload();
                }, 2000);
            } else {
                mostrarNotificacion(data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al procesar la devolución: ' + error.message, 'error');
        }
    }

    // Función para actualizar la interfaz después de un cambio/devolución
    function actualizarInterfazDespuesCambio(idVenta, tieneCambios) {
        // Actualizar la fila de la venta en la tabla
        const filaVenta = document.getElementById(`venta-${idVenta}`);
        if (filaVenta) {
            if (tieneCambios) {
                filaVenta.classList.add('table-warning');
                // Actualizar el badge de estado
                const badgeEstado = filaVenta.querySelector('.badge');
                if (badgeEstado) {
                    badgeEstado.className = 'badge bg-warning';
                    badgeEstado.textContent = 'Con cambios';
                }
                // Actualizar el botón
                const botonGestionar = filaVenta.querySelector('.btn-warning');
                if (botonGestionar) {
                    botonGestionar.innerHTML = '<i class="fas fa-exchange-alt"></i> Ver/Agregar';
                }
                // Agregar botón de historial si no existe
                if (!filaVenta.querySelector('.btn-info')) {
                    const btnHistorial = document.createElement('button');
                    btnHistorial.className = 'btn btn-sm btn-info ms-1';
                    btnHistorial.innerHTML = '<i class="fas fa-history"></i> Historial';
                    btnHistorial.onclick = function () { mostrarHistorialVenta(idVenta); };
                    filaVenta.querySelector('td:last-child').appendChild(btnHistorial);
                }
            }
        }
    }

    function mostrarNotificacion(mensaje, tipo = 'info') {
        const notificacion = document.createElement('div');
        notificacion.className = `alert alert-${tipo === 'error' ? 'danger' : tipo === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
        notificacion.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;

        notificacion.innerHTML = `
        <strong>${tipo === 'success' ? '✓' : '⚠'} </strong> ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

        document.body.appendChild(notificacion);

        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 5000);
    }

    // Inicializar tooltips de Bootstrap y event listeners
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Event listener para búsqueda de productos
        document.getElementById('buscarProductoCambio').addEventListener('input', function (e) {
            buscarProductosDisponibles(e.target.value);
        });

        // Permitir búsqueda con Enter
        document.getElementById('buscarVentaCambio').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                buscarVentasParaCambio();
            }
        });

        document.getElementById('buscarProductoCambio').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                buscarProductosDisponibles(e.target.value);
            }
        });
    });
</script>