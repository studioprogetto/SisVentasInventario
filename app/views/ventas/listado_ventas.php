<div class="card shadow-sm rounded-4 border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-primary"><i class="fas fa-history me-2"></i>Historial de Ventas</h2>
        <div>
          
            <a href="<?php echo BASE_URL; ?>devoluciones" class="btn btn-warning fw-bold me-2">
                <i class="fas fa-exchange-alt me-1"></i> Cambios & Devoluciones
            </a>
            <a href="<?php echo BASE_URL; ?>venta" class="btn btn-primary fw-bold">
                <i class="fas fa-cash-register me-1"></i> Volver al POS
            </a>
        </div>
    </div>

    <div class="card-body">
        <!-- Filtros adicionales -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" id="filtroBusqueda" class="form-control border-start-0"
                        placeholder="Buscar por cliente, cajero o ID...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filtroMetodoPago" class="form-select">
                    <option value="">Todos los métodos de pago</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                    <option value="agora">Agora</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filtroEstado" class="form-select">
                    <option value="">Todas las ventas</option>
                    <option value="con_cambio">Con cambios/devoluciones</option>
                    <option value="sin_cambio">Sin cambios</option>
                    <option value="parcialmente_devuelta">Parcialmente devueltas</option>
                    <option value="anulada">Anuladas</option>
                </select>
            </div>
            <div class="col-md-2">
                <button id="btnLimpiarFiltros" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times me-1"></i> Limpiar
                </button>
            </div>
        </div>

        <!-- Filtros de fecha -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">Fecha Inicio:</label>
                <input type="date" id="filtroFechaInicio" class="form-control"
                    value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Fecha Fin:</label>
                <input type="date" id="filtroFechaFin" class="form-control"
                    value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Mostrar:</label>
                <select id="filtroMostrar" class="form-select">
                    <option value="todas">Todas las ventas</option>
                    <option value="hoy">Hoy</option>
                    <option value="ayer">Ayer</option>
                    <option value="semana">Esta semana</option>
                    <option value="mes">Este mes</option>
                </select>
            </div>
          
            <div class="col-md-2 d-flex align-items-end">
                <button id="btnExportarExcel" class="btn btn-primary w-100">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
            </div>
        </div>

        <!-- Resumen de ventas - MEJORADO -->
        <div class="row mb-4" id="resumenVentas">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-chart-pie me-2"></i>Resumen General de Ventas
                        </h6>
                        <div class="row text-center">
                            <div class="col-md-2 mb-2">
                                <div class="border rounded p-2 bg-white shadow-sm">
                                    <small class="text-muted d-block">Total Ventas</small>
                                    <strong class="text-primary fs-5" id="resumenTotalVentas">0</strong>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="border rounded p-2 bg-white shadow-sm">
                                    <small class="text-muted d-block">Monto Total</small>
                                    <strong class="text-success fs-5" id="resumenMontoTotal">S/. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="border rounded p-2 bg-white shadow-sm">
                                    <small class="text-muted d-block">Efectivo</small>
                                    <strong class="text-success fs-6" id="resumenEfectivo">S/. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="border rounded p-2 bg-white shadow-sm">
                                    <small class="text-muted d-block">Yape</small>
                                    <strong class="text-info fs-6" id="resumenYape">S/. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="border rounded p-2 bg-white shadow-sm">
                                    <small class="text-muted d-block">Plin</small>
                                    <strong class="text-info fs-6" id="resumenPlin">S/. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="border rounded p-2 bg-white shadow-sm">
                                    <small class="text-muted d-block">Agora</small>
                                    <strong class="text-info fs-6" id="resumenAgora">S/. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="border rounded p-2 bg-white shadow-sm">
                                    <small class="text-muted d-block">Transferencia</small>
                                    <strong class="text-primary fs-6" id="resumenTransferencia">S/. 0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaVentas">
                <thead class="table-dark text-uppercase text-white">
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cajero</th>
                        <th>Total</th>
                        <th>Método de Pago</th>
                        <th>Estado</th>
                        <th>Observación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Calcular totales generales
                    $totalesGenerales = [
                        'total_ventas' => 0,
                        'efectivo' => 0,
                        'yape' => 0,
                        'plin' => 0,
                        'agora' => 0,
                        'transferencia' => 0
                    ];
                    ?>
                    <?php if (!empty($ventas)): ?>
                        <?php foreach ($ventas as $venta): ?>
                            <?php
                            // Verificar si tiene cambios/devoluciones y estado
                            $tieneCambios = !empty($venta['tiene_cambios']);
                            $estado = $venta['estado'] ?? 'completada';

                            // Acumular totales
                            $totalesGenerales['total_ventas'] += (float)$venta['total_venta'];

                            // Acumular por método de pago
                            switch ($venta['metodo_pago']) {
                                case 'efectivo':
                                    $totalesGenerales['efectivo'] += (float)$venta['total_venta'];
                                    break;
                                case 'yape':
                                    $totalesGenerales['yape'] += (float)$venta['total_venta'];
                                    break;
                                case 'plin':
                                    $totalesGenerales['plin'] += (float)$venta['total_venta'];
                                    break;
                                case 'agora':
                                    $totalesGenerales['agora'] += (float)$venta['total_venta'];
                                    break;
                                case 'transferencia':
                                    $totalesGenerales['transferencia'] += (float)$venta['total_venta'];
                                    break;
                            }

                            $claseFila = '';
                            $badgeEstado = '';

                            switch ($estado) {
                                case 'parcialmente_devuelta':
                                    $claseFila = 'table-warning';
                                    $badgeEstado = '<span class="badge badge-warning">Parc. Devuelta</span>';
                                    break;
                                case 'anulada':
                                    $claseFila = 'table-danger';
                                    $badgeEstado = '<span class="badge badge-danger">Anulada</span>';
                                    break;
                                case 'completada':
                                default:
                                    if ($tieneCambios) {
                                        $claseFila = 'table-warning';
                                        $badgeEstado = '<span class="badge badge-warning">Modificada</span>';
                                    } else {
                                        $badgeEstado = '<span class="badge badge-success">Completada</span>';
                                    }
                                    break;
                            }
                            ?>
                            <tr tabindex="0" class="<?php echo $claseFila; ?>" data-venta-id="<?php echo $venta['id_venta']; ?>"
                                data-estado="<?php echo $estado; ?>" data-metodo-pago="<?php echo $venta['metodo_pago']; ?>"
                                data-total="<?php echo $venta['total_venta']; ?>">
                                <td class="fw-bold text-secondary">
                                    <?php echo $venta['id_venta']; ?>
                                    <?php if ($tieneCambios && $estado === 'completada'): ?>
                                        <span class="badge badge-warning ms-1" title="Tiene cambios/devoluciones">
                                            <i class="fas fa-exchange-alt"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($venta['cliente']); ?>
                                    <?php if (!empty($venta['id_cliente'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-wallet me-1"></i>
                                            Saldo: <?php echo getMoneda(); ?><span class="saldo-cliente"
                                                data-cliente-id="<?php echo $venta['id_cliente']; ?>">
                                                <?php
                                                // Obtener saldo del cliente usando el modelo
                                                $saldoCliente = $this->ventaModel->obtenerSaldoCliente($venta['id_cliente']);
                                                echo number_format($saldoCliente, 2);
                                                ?>
                                            </span>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($venta['cajero']); ?></td>
                                <td>
                                    <span class="badge badge-success fs-6">
                                        <?php echo getMoneda(); ?> <?php echo number_format($venta['total_venta'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $metodo = ucfirst($venta['metodo_pago']);
                                    $color = match ($venta['metodo_pago']) {
                                        'efectivo' => 'badge-success',
                                        'plin' => 'badge-info',
                                        'yape' => 'bg-purple text-white',
                                        'agora' => 'bg-orange text-white',
                                        'transferencia' => 'bg-primary',
                                        default => 'badge-secondary',
                                    };
                                    ?>
                                    <span class="badge <?php echo $color; ?>"><?php echo $metodo; ?></span>
                                </td>
                                <td>
                                    <?php echo $badgeEstado; ?>
                                </td>
                                <td>
                                    <?php if (!empty($venta['observacion'])): ?>
                                        <?php
                                        $observacion = htmlspecialchars($venta['observacion']);
                                        $observacion_corta = (strlen($observacion) > 30) ? substr($observacion, 0, 30) . '...' : $observacion;
                                        ?>
                                        <span class="observacion-text" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="<?php echo $observacion; ?>"
                                            onclick="mostrarObservacionCompleta('<?php echo addslashes($observacion); ?>')">
                                            <?php echo $observacion_corta; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-secondary">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo BASE_URL; ?>venta/ticket/<?php echo $venta['id_venta']; ?>"
                                            class="btn btn-outline-primary fw-bold" title="Ver Ticket" target="_blank">
                                            <i class="fas fa-receipt"></i>
                                        </a>

                                        <?php if (!empty($venta['id_cliente']) && $estado !== 'anulada'): ?>
                                            <button type="button" class="btn btn-outline-warning fw-bold" title="Cambio/Devolución"
                                                onclick="mostrarModalCambioDevolucion(<?php echo $venta['id_venta']; ?>, <?php echo $venta['id_cliente']; ?>)">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>

                                            <button type="button" class="btn btn-outline-info fw-bold" title="Historial de Cambios"
                                                onclick="mostrarHistorialCambios(<?php echo $venta['id_cliente']; ?>)">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No hay ventas registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Cambio/Devolución -->
<div class="modal fade" id="modalCambioDevolucion" tabindex="-1" aria-labelledby="modalCambioDevolucionLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalCambioDevolucionLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Procesar Cambio/Devolución - Venta #<span id="numeroVentaModal">0</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Contenido del modal (igual que antes) -->
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-undo me-1"></i>Productos a Devolver
                        </h6>
                        <div id="productosDevolverContainer" class="border rounded p-3 scroll-auto max-h-400">
                            <!-- Productos de la venta original -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-cart-plus me-1"></i>Nuevos Productos
                        </h6>
                        <div class="border rounded p-3 scroll-auto max-h-400">
                            <div class="input-group mb-3">
                                <input type="text" id="buscarProductoCambio" class="form-control"
                                    placeholder="Buscar producto...">
                                <button class="btn btn-outline-primary" type="button"
                                    onclick="buscarProductosDisponibles(document.getElementById('buscarProductoCambio').value)">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="listaProductosCambio">
                                <!-- Lista de productos disponibles -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Resumen</h6>
                                <div class="d-flex justify-content-between">
                                    <span>Total a Devolver:</span>
                                    <strong id="totalDevolver"><?php echo getMoneda(); ?>0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total Nuevos:</span>
                                    <strong id="totalNuevos"><?php echo getMoneda(); ?>0.00</strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fs-5">
                                    <span>Saldo:</span>
                                    <strong id="saldoCalculado"
                                        class="text-primary"><?php echo getMoneda(); ?>0.00</strong>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted" id="mensajeSaldo">
                                        <!-- Mensaje dinámico sobre el saldo -->
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-bold">Observación:</label>
                            <textarea id="observacionCambio" class="form-control" rows="3"
                                placeholder="Motivo del cambio/devolución..."></textarea>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="soloDevolucion"
                                onchange="toggleSoloDevolucion()">
                            <label class="form-check-label" for="soloDevolucion">
                                Solo devolución (sin productos nuevos)
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal" aria-label="Cancelar">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btnProcesarCambio" onclick="procesarCambio()">
                    <i class="fas fa-exchange-alt me-1"></i> Procesar Cambio
                </button>
                <button type="button" class="btn btn-danger" id="btnProcesarDevolucion" onclick="procesarDevolucion()">
                    <i class="fas fa-undo me-1"></i> Procesar Devolución
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-purple {
        background-color: var(--color-yape) !important;
    }

    .bg-orange {
        background-color: var(--color-agora) !important;
    }

    .table-hover tbody tr:nth-child(odd) {
        background-color: rgba(111, 66, 193, 0.05);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(111, 66, 193, 0.15);
        transition: all 0.2s ease-in-out;
    }

    .producto-cambio {
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .producto-cambio:hover {
        background-color: #f8f9fa;
    }

    .cantidad-control {
        width: 70px;
    }

    .producto-disponible {
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .producto-disponible:hover {
        background-color: #e9ecef;
        border-color: #007bff;
    }

    .producto-seleccionado {
        background-color: #d1ecf1;
        border-color: #17a2b8;
    }
</style>
<script>
    // Variables globales
    let ventaActual = null;
    let clienteActual = null;
    let productosDevolver = [];
    let productosNuevos = [];
    let productosDisponibles = [];

    // Inicializar resumen con datos del servidor
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar resumen inicial con datos del servidor
        actualizarResumenGeneral(<?php echo json_encode($totalesGenerales); ?>);

        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Configurar filtros
        configurarFiltros();
    });

    // 🔹 CORREGIDO: Función fetchJSON mejorada
    async function fetchJSON(url, options = {}) {
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error en fetchJSON:', error);
            throw error;
        }
    }

    // 🔹 CORREGIDO: Función para actualizar el resumen general - MÉTODOS DE PAGO ACTUALIZADOS
    function actualizarResumenGeneral(totales) {
        document.getElementById('resumenTotalVentas').textContent = document.querySelectorAll('#tablaVentas tbody tr:not([style*="display: none"])').length;
        document.getElementById('resumenMontoTotal').textContent = 'S/. ' + (totales.total_ventas || 0).toFixed(2);
        document.getElementById('resumenEfectivo').textContent = 'S/. ' + (totales.efectivo || 0).toFixed(2);
        document.getElementById('resumenYape').textContent = 'S/. ' + (totales.yape || 0).toFixed(2);
        document.getElementById('resumenPlin').textContent = 'S/. ' + (totales.plin || 0).toFixed(2);
        document.getElementById('resumenAgora').textContent = 'S/. ' + (totales.agora || 0).toFixed(2);
        document.getElementById('resumenTransferencia').textContent = 'S/. ' + (totales.transferencia || 0).toFixed(2);
    }

    // Configurar filtros
    function configurarFiltros() {
        const filtroBusqueda = document.getElementById('filtroBusqueda');
        const filtroMetodoPago = document.getElementById('filtroMetodoPago');
        const filtroEstado = document.getElementById('filtroEstado');
        const filtroFechaInicio = document.getElementById('filtroFechaInicio');
        const filtroFechaFin = document.getElementById('filtroFechaFin');
        const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');

        function aplicarFiltros() {
            const texto = filtroBusqueda.value.toLowerCase();
            const metodo = filtroMetodoPago.value;
            const estado = filtroEstado.value;
            const fechaInicio = filtroFechaInicio.value;
            const fechaFin = filtroFechaFin.value;

            let totalesFiltrados = {
                total_ventas: 0,
                efectivo: 0,
                yape: 0,
                plin: 0,
                agora: 0,
                transferencia: 0
            };

            document.querySelectorAll('#tablaVentas tbody tr').forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                const estadoFila = fila.getAttribute('data-estado');
                const metodoFila = fila.getAttribute('data-metodo-pago');
                const totalFila = parseFloat(fila.getAttribute('data-total') || 0);
                const fechaFila = fila.querySelector('td:nth-child(2)').textContent.trim();

                let mostrar = true;

                // Filtro de texto
                if (texto && !textoFila.includes(texto)) mostrar = false;

                // Filtro de método de pago
                if (metodo && metodoFila !== metodo) mostrar = false;

                // Filtro de estado
                if (estado === 'con_cambio' && estadoFila !== 'parcialmente_devuelta' && !textoFila.includes('modificada')) mostrar = false;
                if (estado === 'sin_cambio' && (estadoFila === 'parcialmente_devuelta' || textoFila.includes('modificada'))) mostrar = false;
                if (estado === 'parcialmente_devuelta' && estadoFila !== 'parcialmente_devuelta') mostrar = false;
                if (estado === 'anulada' && estadoFila !== 'anulada') mostrar = false;

                // Filtro de fecha (convertir formato dd/mm/yyyy a yyyy-mm-dd)
                if (fechaInicio && fechaFin) {
                    const partes = fechaFila.split(' ')[0].split('/');
                    if (partes.length === 3) {
                        const fechaFilaISO = `${partes[2]}-${partes[1]}-${partes[0]}`;
                        if (fechaFilaISO < fechaInicio || fechaFilaISO > fechaFin) {
                            mostrar = false;
                        }
                    }
                }

                fila.style.display = mostrar ? '' : 'none';

                // Acumular totales de filas visibles
                if (mostrar) {
                    totalesFiltrados.total_ventas += totalFila;
                    switch (metodoFila) {
                        case 'efectivo':
                            totalesFiltrados.efectivo += totalFila;
                            break;
                        case 'yape':
                            totalesFiltrados.yape += totalFila;
                            break;
                        case 'plin':
                            totalesFiltrados.plin += totalFila;
                            break;
                        case 'agora':
                            totalesFiltrados.agora += totalFila;
                            break;
                        case 'transferencia':
                            totalesFiltrados.transferencia += totalFila;
                            break;
                    }
                }
            });

            // Actualizar resumen con datos filtrados
            actualizarResumenGeneral(totalesFiltrados);

            // Actualizar contador
            const ventasVisibles = document.querySelectorAll('#tablaVentas tbody tr:not([style*="display: none"])').length;
            const ventasTotales = document.querySelectorAll('#tablaVentas tbody tr').length;
            document.getElementById('filtroBusqueda').placeholder = `Buscar... (${ventasVisibles}/${ventasTotales} ventas)`;
        }

        // Event listeners
        filtroBusqueda.addEventListener('input', aplicarFiltros);
        filtroMetodoPago.addEventListener('change', aplicarFiltros);
        filtroEstado.addEventListener('change', aplicarFiltros);
        filtroFechaInicio.addEventListener('change', aplicarFiltros);
        filtroFechaFin.addEventListener('change', aplicarFiltros);

        btnLimpiarFiltros.addEventListener('click', function() {
            filtroBusqueda.value = '';
            filtroMetodoPago.value = '';
            filtroEstado.value = '';
            filtroFechaInicio.value = '<?php echo date('Y-m-01'); ?>';
            filtroFechaFin.value = '<?php echo date('Y-m-d'); ?>';
            aplicarFiltros();
        });

        // Filtro rápido de fechas
        document.getElementById('filtroMostrar').addEventListener('change', function() {
            const hoy = new Date();
            let inicio, fin;

            switch (this.value) {
                case 'hoy':
                    inicio = fin = hoy.toISOString().split('T')[0];
                    break;
                case 'ayer':
                    const ayer = new Date(hoy);
                    ayer.setDate(hoy.getDate() - 1);
                    inicio = fin = ayer.toISOString().split('T')[0];
                    break;
                case 'semana':
                    const inicioSemana = new Date(hoy);
                    inicioSemana.setDate(hoy.getDate() - hoy.getDay());
                    inicio = inicioSemana.toISOString().split('T')[0];
                    fin = hoy.toISOString().split('T')[0];
                    break;
                case 'mes':
                    inicio = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-01';
                    fin = hoy.toISOString().split('T')[0];
                    break;
                default:
                    return;
            }

            document.getElementById('filtroFechaInicio').value = inicio;
            document.getElementById('filtroFechaFin').value = fin;
            aplicarFiltros();
        });

        // Generar reporte
        document.getElementById('btnGenerarReporte').addEventListener('click', function() {
            generarReporte();
        });

        // Exportar a Excel
        document.getElementById('btnExportarExcel').addEventListener('click', function() {
            exportarAExcel();
        });
    }

    // Función para generar reporte
    function generarReporte() {
        const fechaInicio = document.getElementById('filtroFechaInicio').value;
        const fechaFin = document.getElementById('filtroFechaFin').value;

        if (!fechaInicio || !fechaFin) {
            mostrarNotificacion('Seleccione ambas fechas', 'error');
            return;
        }

        // El resumen ya se actualiza automáticamente con los filtros
        mostrarNotificacion('Reporte generado correctamente', 'success');
    }

    // Función para exportar a Excel
    function exportarAExcel() {
        const fechaInicio = document.getElementById('filtroFechaInicio').value;
        const fechaFin = document.getElementById('filtroFechaFin').value;

        if (!fechaInicio || !fechaFin) {
            mostrarNotificacion('Seleccione fechas para exportar', 'error');
            return;
        }

        // Crear datos para exportar
        const datos = [];
        const headers = ['ID Venta', 'Fecha', 'Cliente', 'Cajero', 'Total', 'Método Pago', 'Estado', 'Observación'];

        datos.push(headers);

        document.querySelectorAll('#tablaVentas tbody tr:not([style*="display: none"])').forEach(fila => {
            const celdas = fila.querySelectorAll('td');
            const filaDatos = [
                celdas[0].textContent.trim(),
                celdas[1].textContent.trim(),
                celdas[2].textContent.trim(),
                celdas[3].textContent.trim(),
                celdas[4].textContent.replace('S/.', '').trim(),
                celdas[5].textContent.trim(),
                celdas[6].textContent.trim(),
                celdas[7].textContent.trim()
            ];
            datos.push(filaDatos);
        });

        // Convertir a CSV
        let csvContent = "data:text/csv;charset=utf-8,";
        datos.forEach(fila => {
            csvContent += fila.map(d => `"${d}"`).join(",") + "\r\n";
        });

        // Descargar
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `reporte_ventas_${fechaInicio}_a_${fechaFin}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        mostrarNotificacion('Archivo exportado exitosamente', 'success');
    }

    // Funciones auxiliares
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

    // Función para mostrar modal de cambio/devolución
    function mostrarModalCambioDevolucion(idVenta, idCliente) {
        ventaActual = idVenta;
        clienteActual = idCliente;

        // Actualizar número de venta en el modal
        document.getElementById('numeroVentaModal').textContent = idVenta;

        // Resetear estado
        productosNuevos = [];
        productosDevolver = [];
        document.getElementById('soloDevolucion').checked = false;
        document.getElementById('observacionCambio').value = '';
        document.getElementById('buscarProductoCambio').value = '';

        // Resetear totales
        document.getElementById('totalDevolver').textContent = getMoneda() + '0.00';
        document.getElementById('totalNuevos').textContent = getMoneda() + '0.00';
        document.getElementById('saldoCalculado').textContent = getMoneda() + '0.00';
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

        const modal = new bootstrap.Modal(document.getElementById('modalCambioDevolucion'));
        modal.show();
    }

    // 🔹 CORREGIDO: Función para cargar productos de la venta original
    async function cargarProductosVenta(idVenta) {
        try {
            const data = await fetchJSON(`<?php echo BASE_URL; ?>venta/obtenerDetallesVenta/${idVenta}`);
            const container = document.getElementById('productosDevolverContainer');
            container.innerHTML = '';

            if (data.error || !Array.isArray(data)) {
                container.innerHTML = `<div class="alert alert-danger">${data.error || 'Error al cargar productos'}</div>`;
                return;
            }

            productosDevolver = data.map(producto => ({
                ...producto,
                cantidad_seleccionada: 0,
                cantidad_disponible: producto.cantidad - (producto.cantidad_devuelta || 0)
            }));

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
                            ${producto.cantidad_devuelta > 0 ? 
                                `<span class="badge bg-info ms-1">Devuelto: ${producto.cantidad_devuelta}</span>` : ''}
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
                    <div class="mt-2 d-none" id="controles-${index}">
                        <label class="form-label small">Cantidad a devolver:</label>
                        <input type="number" class="form-control form-control-sm cantidad-control" 
                               min="1" max="${producto.cantidad_disponible}" value="1"
                               onchange="actualizarCantidadDevolucion(${index}, this.value)"
                               ${producto.cantidad_disponible <= 0 ? 'disabled' : ''}>
                        ${producto.cantidad_disponible <= 0 ? 
                            '<small class="text-danger">No hay cantidad disponible para devolver</small>' : ''}
                    </div>
                `;
                container.appendChild(div);
            });
        } catch (error) {
            console.error('Error al cargar productos:', error);
            const container = document.getElementById('productosDevolverContainer');
            container.innerHTML = `<div class="alert alert-danger">Error al cargar productos: ${error.message}</div>`;
        }
    }

    // 🔹 CORREGIDO: Función para buscar productos disponibles
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
            const data = await fetchJSON(`<?php echo BASE_URL; ?>venta/buscar?term=${encodeURIComponent(termino)}`);
            const container = document.getElementById('listaProductosCambio');

            if (!Array.isArray(data) || data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3">No se encontraron productos disponibles</div>';
                return;
            }

            // Filtrar solo productos con stock
            productosDisponibles = data.filter(item =>
                item.tipo === 'producto' && (item.stock > 0)
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
                    <div class="mt-2 d-none" id="controles-nuevo-${index}">
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

    // 🔹 CORREGIDO: Función para mostrar historial de cambios - RUTA CORREGIDA
    async function mostrarHistorialCambios(idCliente) {
        const nombreCliente = document.querySelector(`tr[data-venta-id] .saldo-cliente[data-cliente-id="${idCliente}"]`)?.closest('td')?.querySelector('strong')?.textContent || 'Cliente';
        document.getElementById('nombreClienteHistorial').textContent = nombreCliente;

        try {
            // 🔹 RUTA CORREGIDA: Cambiado de 'devoluciones/historialCliente' a 'venta/historialCambiosCliente'
            const data = await fetchJSON(`<?php echo BASE_URL; ?>venta/historialCambiosCliente/${idCliente}`);
            const container = document.getElementById('contenidoHistorialCambios');

            if (data.error) {
                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            } else if (data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4">No hay cambios registrados para este cliente</div>';
            } else {
                let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
                html += '<thead><tr><th>Fecha</th><th>Tipo</th><th>Venta Original</th><th>Venta Nueva</th><th>Monto Saldo</th><th>Estado</th><th>Observación</th></tr></thead><tbody>';

                data.forEach(cambio => {
                    html += `
                        <tr>
                            <td>${new Date(cambio.fecha_cambio).toLocaleString()}</td>
                            <td><span class="badge ${cambio.tipo === 'cambio' ? 'bg-warning' : 'bg-danger'}">${cambio.tipo}</span></td>
                            <td>#${cambio.venta_original || 'N/A'}</td>
                            <td>${cambio.venta_nueva ? '#' + cambio.venta_nueva : 'N/A'}</td>
                            <td>${getMoneda()}${parseFloat(cambio.monto_saldo || 0).toFixed(2)}</td>
                            <td><span class="badge bg-${cambio.estado === 'completado' ? 'success' : 'warning'}">${cambio.estado}</span></td>
                            <td>${cambio.observacion || '-'}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalHistorialCambios'));
            modal.show();
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al cargar historial: ' + error.message, 'error');
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
        const seccionNuevos = document.querySelector('.col-md-6:last-child');

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

    // 🔹 CORREGIDO: Funciones para procesar cambios y devoluciones - RUTAS CORREGIDAS
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
            // 🔹 RUTA CORREGIDA
            const data = await fetchJSON('<?php echo BASE_URL; ?>venta/procesarCambio', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            });

            if (data.success) {
                let mensaje = data.mensaje;
                if (data.saldo_generado > 0) {
                    mensaje += ` - Saldo generado: ${getMoneda()}${data.saldo_generado.toFixed(2)}`;
                }
                if (data.saldo_utilizado > 0) {
                    mensaje += ` - Saldo utilizado: ${getMoneda()}${data.saldo_utilizado.toFixed(2)}`;
                }

                mostrarNotificacion(mensaje, 'success');
                actualizarInterfazDespuesOperacion(ventaActual, data.nuevo_estado);

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
            // 🔹 RUTA CORREGIDA
            const data = await fetchJSON('<?php echo BASE_URL; ?>venta/procesarDevolucion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            });

            if (data.success) {
                let mensaje = data.mensaje;
                if (data.es_devolucion_total) {
                    mensaje += ' - Esta venta ha sido anulada y removida del historial';
                } else {
                    mensaje += ` - Monto devuelto: ${getMoneda()}${data.monto_devolucion.toFixed(2)}`;
                }

                mostrarNotificacion(mensaje, 'success');
                actualizarInterfazDespuesOperacion(ventaActual, data.nuevo_estado, data.es_devolucion_total);

                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalCambioDevolucion')).hide();
                    if (data.es_devolucion_total) {
                        location.reload();
                    } else {
                        actualizarFilaVenta(ventaActual, data.nuevo_estado);
                    }
                }, 2000);
            } else {
                mostrarNotificacion(data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al procesar la devolución: ' + error.message, 'error');
        }
    }

    function actualizarInterfazDespuesOperacion(idVenta, nuevoEstado, esAnulada = false) {
        if (esAnulada) {
            const fila = document.getElementById(`venta-${idVenta}`);
            if (fila) {
                fila.remove();
            }
        } else {
            actualizarFilaVenta(idVenta, nuevoEstado);
        }
    }

    function actualizarFilaVenta(idVenta, estado) {
        const fila = document.getElementById(`venta-${idVenta}`);
        if (!fila) return;

        const badgeEstado = fila.querySelector('.badge');
        if (badgeEstado) {
            switch (estado) {
                case 'parcialmente_devuelta':
                    badgeEstado.className = 'badge bg-warning';
                    badgeEstado.textContent = 'Parc. Devuelta';
                    break;
                case 'anulada':
                    badgeEstado.className = 'badge bg-danger';
                    badgeEstado.textContent = 'Anulada';
                    break;
                default:
                    badgeEstado.className = 'badge bg-success';
                    badgeEstado.textContent = 'Completada';
            }
        }

        fila.className = '';
        if (estado === 'parcialmente_devuelta') {
            fila.classList.add('table-warning');
        } else if (estado === 'anulada') {
            fila.classList.add('table-danger');
        }
    }

    // Función para obtener símbolo de moneda
    function getMoneda() {
        return 'S/.';
    }

    // Funciones existentes para observaciones
    function mostrarObservacionCompleta(observacion) {
        document.getElementById('observacionCompleta').innerHTML = observacion.replace(/\n/g, '<br>');
        const modal = new bootstrap.Modal(document.getElementById('modalObservacion'));
        modal.show();
    }

    function copiarObservacion() {
        const observacion = document.getElementById('observacionCompleta').innerText;
        navigator.clipboard.writeText(observacion).then(() => {
            mostrarNotificacion('Observación copiada al portapapeles', 'success');
        }).catch(err => {
            console.error('Error al copiar: ', err);
            mostrarNotificacion('Error al copiar la observación', 'error');
        });
    }

    // Inicializar tooltips de Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Filtros de búsqueda
        const filtroBusqueda = document.getElementById('filtroBusqueda');
        const filtroMetodoPago = document.getElementById('filtroMetodoPago');
        const filtroEstado = document.getElementById('filtroEstado');
        const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');

        function aplicarFiltros() {
            const texto = filtroBusqueda.value.toLowerCase();
            const metodo = filtroMetodoPago.value;
            const estado = filtroEstado.value;

            document.querySelectorAll('#tablaVentas tbody tr').forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                const estadoFila = fila.getAttribute('data-estado');

                // Obtener el método de pago de la columna específica (6ta columna)
                const celdas = fila.querySelectorAll('td');
                const metodoFila = celdas[5] ? celdas[5].textContent.toLowerCase().trim() : '';

                let mostrar = true;

                if (texto && !textoFila.includes(texto)) mostrar = false;
                if (metodo && !metodoFila.includes(metodo)) mostrar = false;
                if (estado === 'con_cambio' && estadoFila !== 'parcialmente_devuelta' && !textoFila.includes('modificada')) mostrar = false;
                if (estado === 'sin_cambio' && (estadoFila === 'parcialmente_devuelta' || textoFila.includes('modificada'))) mostrar = false;
                if (estado === 'parcialmente_devuelta' && estadoFila !== 'parcialmente_devuelta') mostrar = false;
                if (estado === 'anulada' && estadoFila !== 'anulada') mostrar = false;

                fila.style.display = mostrar ? '' : 'none';
            });
        }

        filtroBusqueda.addEventListener('input', aplicarFiltros);
        filtroMetodoPago.addEventListener('change', aplicarFiltros);
        filtroEstado.addEventListener('change', aplicarFiltros);

        btnLimpiarFiltros.addEventListener('click', function() {
            filtroBusqueda.value = '';
            filtroMetodoPago.value = '';
            filtroEstado.value = '';
            aplicarFiltros();
        });

        // Event listener para búsqueda de productos
        document.getElementById('buscarProductoCambio').addEventListener('input', function(e) {
            buscarProductosDisponibles(e.target.value);
        });

        // Permitir búsqueda con Enter
        document.getElementById('buscarProductoCambio').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarProductosDisponibles(e.target.value);
            }
        });
    });

    // Funciones para filtros de fecha y reportes
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar fechas
        document.getElementById('filtroFechaInicio').value = '<?php echo date('Y-m-01'); ?>';
        document.getElementById('filtroFechaFin').value = '<?php echo date('Y-m-d'); ?>';

        // Filtro rápido de fechas
        document.getElementById('filtroMostrar').addEventListener('change', function() {
            const hoy = new Date();
            let inicio, fin;

            switch (this.value) {
                case 'hoy':
                    inicio = fin = hoy.toISOString().split('T')[0];
                    break;
                case 'ayer':
                    const ayer = new Date(hoy);
                    ayer.setDate(hoy.getDate() - 1);
                    inicio = fin = ayer.toISOString().split('T')[0];
                    break;
                case 'semana':
                    const inicioSemana = new Date(hoy);
                    inicioSemana.setDate(hoy.getDate() - hoy.getDay());
                    inicio = inicioSemana.toISOString().split('T')[0];
                    fin = hoy.toISOString().split('T')[0];
                    break;
                case 'mes':
                    inicio = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-01';
                    fin = hoy.toISOString().split('T')[0];
                    break;
                default:
                    return;
            }

            document.getElementById('filtroFechaInicio').value = inicio;
            document.getElementById('filtroFechaFin').value = fin;
        });

        // Generar reporte
        document.getElementById('btnGenerarReporte').addEventListener('click', function() {
            generarReporte();
        });

        // Exportar a Excel
        document.getElementById('btnExportarExcel').addEventListener('click', function() {
            exportarAExcel();
        });

        // Permitir Enter en filtros de fecha
        ['filtroFechaInicio', 'filtroFechaFin'].forEach(id => {
            document.getElementById(id).addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    generarReporte();
                }
            });
        });
    });

// Keyboard handlers for table rows: Enter/Space triggers first action
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('#tablaVentas tbody tr[tabindex]').forEach(function(row){
        row.addEventListener('keydown', function(e){
            if(e.key === 'Enter' || e.key === ' '){
                e.preventDefault();
                var btn = row.querySelector('button, a');
                if(btn) btn.click();
            }
        });
    });
});

    // 🔹 CORREGIDO: Función para generar reporte - MÉTODOS DE PAGO ACTUALIZADOS
    async function generarReporte() {
        const fechaInicio = document.getElementById('filtroFechaInicio').value;
        const fechaFin = document.getElementById('filtroFechaFin').value;
        const metodoPago = document.getElementById('filtroMetodoPago').value;
        const estado = document.getElementById('filtroEstado').value;

        if (!fechaInicio || !fechaFin) {
            mostrarNotificacion('Seleccione ambas fechas', 'error');
            return;
        }

        try {
            // En una implementación real, aquí harías una llamada AJAX al servidor
            // Por ahora, calculamos desde los datos visibles en la tabla
            calcularResumenDesdeTabla();

        } catch (error) {
            console.error('Error generando reporte:', error);
            mostrarNotificacion('Error al generar reporte', 'error');
        }
    }

    // 🔹 CORREGIDO: Calcular resumen desde la tabla visible - MÉTODOS DE PAGO ACTUALIZADOS
    function calcularResumenDesdeTabla() {
        let totalVentas = 0;
        let efectivo = 0;
        let yape = 0;
        let plin = 0;
        let agora = 0;
        let transferencia = 0;
        let numVentas = 0;

        document.querySelectorAll('#tablaVentas tbody tr:not([style*="display: none"])').forEach(fila => {
            const celdas = fila.querySelectorAll('td');
            const total = parseFloat(celdas[4].textContent.replace('S/.', '').trim());
            const metodoPago = celdas[5].textContent.trim().toLowerCase();

            totalVentas += total;
            numVentas++;

            switch (metodoPago) {
                case 'efectivo':
                    efectivo += total;
                    break;
                case 'yape':
                    yape += total;
                    break;
                case 'plin':
                    plin += total;
                    break;
                case 'agora':
                    agora += total;
                    break;
                case 'transferencia':
                    transferencia += total;
                    break;
            }
        });

        // Mostrar resumen
        document.getElementById('resumenTotalVentas').textContent = numVentas;
        document.getElementById('resumenEfectivo').textContent = 'S/. ' + efectivo.toFixed(2);
        document.getElementById('resumenYape').textContent = 'S/. ' + yape.toFixed(2);
        document.getElementById('resumenPlin').textContent = 'S/. ' + plin.toFixed(2);
        document.getElementById('resumenAgora').textContent = 'S/. ' + agora.toFixed(2);
        document.getElementById('resumenTransferencia').textContent = 'S/. ' + transferencia.toFixed(2);

        // Mostrar sección de resumen
        document.getElementById('resumenVentas').style.display = 'block';

        mostrarNotificacion(`Reporte generado: ${numVentas} ventas por S/. ${totalVentas.toFixed(2)}`, 'success');
    }

    // Mejorar la función de filtrado existente
    function aplicarFiltros() {
        const texto = document.getElementById('filtroBusqueda').value.toLowerCase();
        const metodo = document.getElementById('filtroMetodoPago').value;
        const estado = document.getElementById('filtroEstado').value;
        const fechaInicio = document.getElementById('filtroFechaInicio').value;
        const fechaFin = document.getElementById('filtroFechaFin').value;

        document.querySelectorAll('#tablaVentas tbody tr').forEach(fila => {
            const textoFila = fila.textContent.toLowerCase();
            const estadoFila = fila.getAttribute('data-estado');
            const fechaFila = fila.querySelector('td:nth-child(2)').textContent.trim();

            // Obtener el método de pago
            const celdas = fila.querySelectorAll('td');
            const metodoFila = celdas[5] ? celdas[5].textContent.toLowerCase().trim() : '';

            let mostrar = true;

            // Filtro de texto
            if (texto && !textoFila.includes(texto)) mostrar = false;

            // Filtro de método de pago
            if (metodo && !metodoFila.includes(metodo)) mostrar = false;

            // Filtro de estado
            if (estado === 'con_cambio' && estadoFila !== 'parcialmente_devuelta' && !textoFila.includes('modificada')) mostrar = false;
            if (estado === 'sin_cambio' && (estadoFila === 'parcialmente_devuelta' || textoFila.includes('modificada'))) mostrar = false;
            if (estado === 'parcialmente_devuelta' && estadoFila !== 'parcialmente_devuelta') mostrar = false;
            if (estado === 'anulada' && estadoFila !== 'anulada') mostrar = false;

            // Filtro de fecha (convertir formato dd/mm/yyyy a yyyy-mm-dd)
            if (fechaInicio && fechaFin) {
                const partes = fechaFila.split(' ')[0].split('/');
                if (partes.length === 3) {
                    const fechaFilaISO = `${partes[2]}-${partes[1]}-${partes[0]}`;
                    if (fechaFilaISO < fechaInicio || fechaFilaISO > fechaFin) {
                        mostrar = false;
                    }
                }
            }

            fila.style.display = mostrar ? '' : 'none';
        });

        // Actualizar contadores
        const ventasVisibles = document.querySelectorAll('#tablaVentas tbody tr:not([style*="display: none"])').length;
        const ventasTotales = document.querySelectorAll('#tablaVentas tbody tr').length;

        document.getElementById('filtroBusqueda').placeholder = `Buscar... (${ventasVisibles}/${ventasTotales} ventas)`;
    }

    // Actualizar event listeners existentes para incluir fechas
    document.getElementById('filtroFechaInicio').addEventListener('change', aplicarFiltros);
    document.getElementById('filtroFechaFin').addEventListener('change', aplicarFiltros);
</script>