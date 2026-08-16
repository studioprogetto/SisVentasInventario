<div class="container-fluid py-3">
    <div class="row g-3">

        <!-- 🛒 Panel de productos/servicios -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-cash-register"></i> Punto de Venta</h4>
                </div>
                <div class="card-body">

                    <!-- 🔍 Buscar productos/servicios -->
                    <div class="mb-4 position-relative">
                        <label for="buscar_producto" class="form-label fw-bold">Buscar Producto o Servicio</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="buscar_producto" class="form-control" placeholder="Escribe nombre o código...">
                        </div>
                        <div id="resultados_busqueda" class="list-group position-absolute w-100" style="z-index: 1050;"></div>
                    </div>

                    <!-- 🛒 Carrito -->
                    <h5 class="mb-3"><i class="fas fa-shopping-cart"></i> Carrito</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto/Servicio</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="carrito_tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🧾 Panel de resumen de venta -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-receipt"></i> Resumen de Venta</h4>
                </div>
                <div class="card-body">

                    <!-- Cliente -->
                    <div class="mb-3">
                        <label for="buscarCliente" class="form-label fw-bold">Buscar Cliente</label>
                        <input type="text" id="buscarCliente" class="form-control" placeholder="Nombre o DNI">
                        <input type="hidden" id="id_cliente">
                    </div>

                    <div id="infoCliente" class="border rounded p-2 mb-3" style="display:none;background:#f9f9f9;">
                        <p><strong>Cliente:</strong> <span id="nombreCliente"></span></p>
                        <p><strong>DNI:</strong> <span id="dniCliente"></span></p>
                        <p><strong>Sellos:</strong> <span id="sellosCliente"></span></p>
                        <p><strong>Saldo Disponible:</strong> <span id="saldoResumenCliente" class="text-success"><?php echo getMoneda(); ?>0.00</span></p>
                    </div>

                    <!-- Tarjeta de fidelidad -->
                    <div class="card mb-3 border-warning" id="card_tarjeta_sellos">
                        <div class="card-header bg-warning text-dark fw-bold">
                            🎁 Tarjeta de fidelidad
                        </div>
                        <div class="card-body text-center">
                            <div id="tarjeta-sellos" class="d-flex justify-content-center flex-wrap gap-2 mb-2"></div>
                            <small class="text-muted">Completa 12 compras y gana un 10% de descuento</small>
                        </div>
                    </div>

                    <!-- Total y descuentos -->
                    <div class="mb-3">
                        <p id="descuento_sellos_span" class="text-end text-small mb-0"></p>
                        <p id="descuento_manual_span" class="text-end text-small mb-0"></p>
                        <div class="d-flex justify-content-between fs-4 fw-bold mb-3">
                            <span>TOTAL:</span>
                            <span id="total_venta"><?php echo getMoneda(); ?>0.00</span>
                        </div>
                    </div>

                    <!-- Botón finalizar venta -->
                    <div class="d-grid mb-3">
                        <button id="btn_finalizar_venta" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#pagoModal" disabled>
                            <i class="fas fa-check-circle"></i> Finalizar Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- 💳 Modal de pago -->
<div class="modal fade" id="pagoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-credit-card"></i> Procesar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <h3 class="text-center mb-3">Total a Pagar: <span id="modal_total_pagar"><?php echo getMoneda(); ?>0.00</span></h3>

                <div class="mb-3">
                    <label for="metodo_pago" class="form-label fw-bold">Método de Pago</label>
                    <select id="metodo_pago" class="form-select">
                        <option value="efectivo">Efectivo</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="agora">Agora</option>
                        <option value="saldo">Usar Saldo</option>
                        <option value="mixto">Pago Mixto</option>
                    </select>
                </div>

                <!-- Información de saldo del cliente -->
                <div id="infoSaldoCliente" class="border rounded p-2 mb-3" style="display:none;">
                    <p class="mb-1"><strong>💰 Saldo Disponible:</strong> <span id="saldoDisponible"><?php echo getMoneda(); ?>0.00</span></p>
                    <small class="text-muted" id="textoAyudaSaldo">Este saldo puede ser usado para pagos futuros</small>
                </div>

                <!-- Sección para pago mixto con saldo -->
                <div id="pago_mixto_saldo_div" class="border rounded p-3 mb-3" style="display:none;background:#f0f8ff;">
                    <h6 class="fw-bold mb-2">💳 Usar Saldo del Cliente</h6>
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label small">Monto a usar del saldo:</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="monto_usar_saldo" placeholder="Monto a usar">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" id="btn_usar_saldo_max">Máx</button>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block" id="info_saldo_restante"></small>
                </div>

                <!-- Sección para pago con otros métodos -->
                <div id="pago_otros_metodos_div" class="border rounded p-3 mb-3" style="display:none;background:#fff3cd;">
                    <h6 class="fw-bold mb-2">💵 Pago con Otros Métodos</h6>
                    <div class="mb-2">
                        <label class="form-label small">Monto a pagar con <span id="metodo_seleccionado_texto">efectivo</span>:</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="monto_otro_metodo" placeholder="Monto">
                    </div>
                    <small class="text-muted" id="info_pago_restante"></small>
                </div>

                <div id="pago_efectivo_div">
                    <div class="mb-3">
                        <label for="monto_recibido" class="form-label fw-bold">Monto Recibido</label>
                        <input type="number" step="0.01" class="form-control" id="monto_recibido">
                    </div>
                    <h4 class="text-center">Cambio: <span id="cambio_cliente"><?php echo getMoneda(); ?>0.00</span></h4>
                </div>

                <!-- Ajustes de total -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Opciones de Ajuste</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary" id="redondear_bajo">Redondear bajo</button>
                        <button class="btn btn-outline-primary" id="redondear_normal">Redondear normal</button>
                        <input type="number" step="0.01" class="form-control w-auto" id="descuento_manual" placeholder="Restar monto">
                        <button class="btn btn-outline-success" id="aplicar_descuento">Aplicar descuento</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="observacion_venta" class="form-label fw-bold">
                        Observaciones o comentarios
                    </label>
                    <textarea id="observacion_venta" class="form-control" rows="2"
                        placeholder="Ejemplo: Cliente pidió entregar mañana, sin tapa, descuento especial..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="btn_confirmar_venta"><i class="fas fa-check"></i> Confirmar Venta</button>
            </div>
        </div>
    </div>
</div>

<!-- 🔗 Scripts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script>
    // Generar un ID único para esta pestaña para evitar conflictos
    const TAB_ID = 'pos_tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    const MONEDA = '<?php echo getMoneda(); ?>';

    document.addEventListener('DOMContentLoaded', function() {
        let carrito = [];
        let totalOriginal = 0;
        let descuentoSellos = 0;
        let descuentoManual = 0;
        let sellosClienteActuales = 0;
        let saldoClienteDisponible = 0;
        let montoUsarSaldo = 0;
        let montoOtroMetodo = 0;
        let modalPago = null;

        // --- Elementos ---
        const buscarInput = document.getElementById('buscar_producto');
        const resultadosBusqueda = document.getElementById('resultados_busqueda');
        const carritoTbody = document.getElementById('carrito_tbody');
        const totalVentaSpan = document.getElementById('total_venta');
        const btnFinalizarVenta = document.getElementById('btn_finalizar_venta');
        const modalTotalPagar = document.getElementById('modal_total_pagar');
        const montoRecibidoInput = document.getElementById('monto_recibido');
        const cambioClienteSpan = document.getElementById('cambio_cliente');
        const btnConfirmarVenta = document.getElementById('btn_confirmar_venta');
        const metodoPagoSelect = document.getElementById('metodo_pago');
        const pagoEfectivoDiv = document.getElementById('pago_efectivo_div');
        const pagoMixtoSaldoDiv = document.getElementById('pago_mixto_saldo_div');
        const pagoOtrosMetodosDiv = document.getElementById('pago_otros_metodos_div');
        const idClienteSelect = document.getElementById('id_cliente');
        const nombreClienteSpan = document.getElementById('nombreCliente');
        const dniClienteSpan = document.getElementById('dniCliente');
        const sellosClienteSpan = document.getElementById('sellosCliente');
        const saldoResumenClienteSpan = document.getElementById('saldoResumenCliente');
        const infoClienteDiv = document.getElementById('infoCliente');
        const tarjetaSellosDiv = document.getElementById('tarjeta-sellos');
        const cardTarjetaSellos = document.getElementById('card_tarjeta_sellos');
        const redondearBajoBtn = document.getElementById('redondear_bajo');
        const redondearNormalBtn = document.getElementById('redondear_normal');
        const descuentoInput = document.getElementById('descuento_manual');
        const aplicarDescuentoBtn = document.getElementById('aplicar_descuento');
        const descuentoSellosSpan = document.getElementById('descuento_sellos_span');
        const descuentoManualSpan = document.getElementById('descuento_manual_span');
        const infoSaldoClienteDiv = document.getElementById('infoSaldoCliente');
        const saldoDisponibleSpan = document.getElementById('saldoDisponible');
        const textoAyudaSaldo = document.getElementById('textoAyudaSaldo');
        const montoUsarSaldoInput = document.getElementById('monto_usar_saldo');
        const btnUsarSaldoMax = document.getElementById('btn_usar_saldo_max');
        const montoOtroMetodoInput = document.getElementById('monto_otro_metodo');
        const metodoSeleccionadoTexto = document.getElementById('metodo_seleccionado_texto');
        const infoSaldoRestante = document.getElementById('info_saldo_restante');
        const infoPagoRestante = document.getElementById('info_pago_restante');

        // Inicializar modal de Bootstrap
        modalPago = new bootstrap.Modal(document.getElementById('pagoModal'));

        // --- Búsqueda productos ---
        buscarInput.addEventListener('keyup', function() {
            const term = this.value.trim();
            if (term.length < 2) {
                resultadosBusqueda.innerHTML = '';
                return;
            }
            fetch(`<?php echo BASE_URL; ?>venta/buscar?term=${encodeURIComponent(term)}`)
                .then(r => r.json())
                .then(data => {
                    resultadosBusqueda.innerHTML = '';
                    if (data.length) {
                        data.forEach(p => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.textContent = `${p.nombre} - Stock: ${p.stock} - ${MONEDA}${parseFloat(p.precio_venta).toFixed(2)}`;
                            item.addEventListener('click', e => {
                                e.preventDefault();
                                agregarAlCarrito(p);
                                buscarInput.value = '';
                                resultadosBusqueda.innerHTML = '';
                            });
                            resultadosBusqueda.appendChild(item);
                        });
                    } else {
                        resultadosBusqueda.innerHTML = '<span class="list-group-item">No se encontraron productos</span>';
                    }
                }).catch(err => console.error(err));
        });

        // --- Carrito ---
        function agregarAlCarrito(item) {
            const existente = carrito.find(p => p.id === item.id && p.tipo === item.tipo);
            if (existente) existente.cantidad++;
            else carrito.push({
                id: item.id,
                nombre: item.nombre,
                precio: parseFloat(item.precio_venta),
                cantidad: 1,
                tipo: item.tipo
            });
            renderizarCarrito();
        }

        function renderizarCarrito() {
            carritoTbody.innerHTML = '';
            carrito.forEach((item, idx) => {
                const subtotal = item.precio * item.cantidad;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${item.nombre} <small class="text-muted">(${item.tipo})</small></td>
                <td>${MONEDA}${item.precio.toFixed(2)}</td>
                <td><input type="number" min="1" class="form-control form-control-sm cantidad-input" data-index="${idx}" value="${item.cantidad}"></td>
                <td>${MONEDA}${subtotal.toFixed(2)}</td>
                <td><button class="btn btn-danger btn-sm eliminar-btn" data-index="${idx}"><i class="fas fa-trash"></i></button></td>
            `;
                carritoTbody.appendChild(tr);
            });
            calcularTotales();
        }

        carritoTbody.addEventListener('change', e => {
            if (e.target.classList.contains('cantidad-input')) {
                const idx = e.target.dataset.index;
                const qty = parseInt(e.target.value);
                if (qty > 0) carrito[idx].cantidad = qty;
                else carrito.splice(idx, 1);
                renderizarCarrito();
            }
        });

        carritoTbody.addEventListener('click', e => {
            const btn = e.target.closest('.eliminar-btn');
            if (btn) {
                carrito.splice(parseInt(btn.dataset.index), 1);
                renderizarCarrito();
            }
        });

        // --- Totales y descuentos ---
        function calcularTotales() {
            totalOriginal = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);

            const generaSello = carrito.some(i => i.tipo === 'producto');

            if (!idClienteSelect.value) {
                cardTarjetaSellos.style.display = 'none';
                descuentoSellos = 0;
                sellosClienteActuales = 0;
                infoSaldoClienteDiv.style.display = 'none';
                saldoResumenClienteSpan.textContent = `${MONEDA}0.00`;
            } else {
                cardTarjetaSellos.style.display = 'block';
                let sellosDespues = sellosClienteActuales;
                if (generaSello) sellosDespues += 1;
                if (sellosDespues > 12) sellosDespues %= 12;

                descuentoSellos = (sellosDespues === 6) ? 0.05 : (sellosDespues === 12 ? 0.10 : 0);

                // Actualizar saldo en resumen
                saldoResumenClienteSpan.textContent = `${MONEDA}${saldoClienteDisponible.toFixed(2)}`;
            }

            let totalConSellos = totalOriginal * (1 - descuentoSellos);
            let totalFinal = Math.max(0, totalConSellos - descuentoManual);

            // Mostrar información de saldo SIEMPRE que el cliente tenga saldo
            if (idClienteSelect.value && saldoClienteDisponible > 0) {
                infoSaldoClienteDiv.style.display = 'block';
                saldoDisponibleSpan.textContent = `${MONEDA}${saldoClienteDisponible.toFixed(2)}`;

                const saldoSuficiente = saldoClienteDisponible >= totalFinal;

                if (metodoPagoSelect.value === 'saldo') {
                    if (!saldoSuficiente) {
                        const falta = totalFinal - saldoClienteDisponible;
                        totalVentaSpan.innerHTML = `${MONEDA}${totalFinal.toFixed(2)} <span class="badge bg-danger">Saldo insuficiente - Faltan ${MONEDA}${falta.toFixed(2)}</span>`;
                        btnFinalizarVenta.disabled = true;
                        textoAyudaSaldo.textContent = `Saldo insuficiente. Faltan ${MONEDA}${falta.toFixed(2)}`;
                        textoAyudaSaldo.className = 'text-danger';
                    } else {
                        totalVentaSpan.textContent = `${MONEDA}${totalFinal.toFixed(2)}`;
                        btnFinalizarVenta.disabled = false;
                        textoAyudaSaldo.textContent = 'Este saldo será utilizado para el pago completo';
                        textoAyudaSaldo.className = 'text-success';
                    }
                } else if (metodoPagoSelect.value === 'mixto') {
                    totalVentaSpan.textContent = `${MONEDA}${totalFinal.toFixed(2)}`;
                    btnFinalizarVenta.disabled = false;
                    textoAyudaSaldo.textContent = 'Puedes usar parte de tu saldo para esta compra';
                    textoAyudaSaldo.className = 'text-muted';
                } else {
                    totalVentaSpan.innerHTML = `${MONEDA}${totalFinal.toFixed(2)} <small class="text-success">(Saldo disponible: ${MONEDA}${saldoClienteDisponible.toFixed(2)})</small>`;
                    btnFinalizarVenta.disabled = carrito.length === 0;
                    textoAyudaSaldo.textContent = 'Este saldo puede ser usado para pagos futuros';
                    textoAyudaSaldo.className = 'text-muted';
                }
            } else {
                totalVentaSpan.textContent = `${MONEDA}${totalFinal.toFixed(2)}`;
                btnFinalizarVenta.disabled = carrito.length === 0;
                infoSaldoClienteDiv.style.display = 'none';
            }

            descuentoSellosSpan.textContent = descuentoSellos ? `Descuento por sellos: ${descuentoSellos*100}%` : '';
            descuentoManualSpan.textContent = descuentoManual ? `Descuento aplicado: ${MONEDA}${descuentoManual.toFixed(2)}` : '';
            modalTotalPagar.textContent = `${MONEDA}${totalFinal.toFixed(2)}`;

            const sellosAAgregar = generaSello ? 1 : 0;
            renderTarjetaSellos(sellosClienteActuales, sellosAAgregar);

            // Actualizar montos en modal de pago
            actualizarMontosPagoMixto();
        }

        function actualizarMontosPagoMixto() {
            const totalFinal = parseFloat(modalTotalPagar.textContent.replace(MONEDA, '')) || 0;

            if (metodoPagoSelect.value === 'mixto') {
                // Limitar monto a usar del saldo al mínimo entre saldo disponible y total final
                const maxSaldoUsar = Math.min(saldoClienteDisponible, totalFinal);

                if (montoUsarSaldoInput.value === '' || parseFloat(montoUsarSaldoInput.value) > maxSaldoUsar) {
                    montoUsarSaldoInput.value = maxSaldoUsar.toFixed(2);
                }

                montoUsarSaldo = parseFloat(montoUsarSaldoInput.value) || 0;
                const saldoRestante = saldoClienteDisponible - montoUsarSaldo;
                const totalRestante = totalFinal - montoUsarSaldo;

                infoSaldoRestante.textContent = `Saldo restante después del pago: ${MONEDA}${saldoRestante.toFixed(2)}`;

                // Actualizar monto del otro método
                if (montoOtroMetodoInput.value === '' || Math.abs(parseFloat(montoOtroMetodoInput.value) - totalRestante) > 0.01) {
                    montoOtroMetodoInput.value = totalRestante.toFixed(2);
                }

                montoOtroMetodo = parseFloat(montoOtroMetodoInput.value) || 0;
                const diferencia = montoUsarSaldo + montoOtroMetodo - totalFinal;

                if (Math.abs(diferencia) > 0.01) {
                    infoPagoRestante.textContent = `⚠ Diferencia: ${MONEDA}${diferencia.toFixed(2)}. Ajuste los montos.`;
                    infoPagoRestante.className = 'text-danger';
                    btnConfirmarVenta.disabled = true;
                } else {
                    infoPagoRestante.textContent = `✅ Pago completo. Restante: ${MONEDA}0.00`;
                    infoPagoRestante.className = 'text-success';
                    btnConfirmarVenta.disabled = false;
                }
            }
        }

        redondearBajoBtn.addEventListener('click', () => {
            const totalConSellos = totalOriginal * (1 - descuentoSellos);
            descuentoManual = Math.max(0, totalConSellos - Math.floor(totalConSellos));
            calcularTotales();
        });

        redondearNormalBtn.addEventListener('click', () => {
            const totalConSellos = totalOriginal * (1 - descuentoSellos);
            descuentoManual = Math.max(0, totalConSellos - Math.round(totalConSellos));
            calcularTotales();
        });

        aplicarDescuentoBtn.addEventListener('click', () => {
            descuentoManual = parseFloat(descuentoInput.value) || 0;
            calcularTotales();
        });

        // --- Modal pago ---
        btnFinalizarVenta.addEventListener('click', () => {
            calcularTotales();
            montoRecibidoInput.value = '';
            cambioClienteSpan.textContent = `${MONEDA}0.00`;
            montoUsarSaldoInput.value = '';
            montoOtroMetodoInput.value = '';
            montoUsarSaldo = 0;
            montoOtroMetodo = 0;
        });

        metodoPagoSelect.addEventListener('change', () => {
            const metodo = metodoPagoSelect.value;

            // Mostrar/ocultar secciones según método de pago
            pagoEfectivoDiv.style.display = metodo === 'efectivo' ? 'block' : 'none';
            pagoMixtoSaldoDiv.style.display = metodo === 'mixto' ? 'block' : 'none';
            pagoOtrosMetodosDiv.style.display = metodo === 'mixto' ? 'block' : 'none';

            // Actualizar texto del método seleccionado
            if (metodo === 'mixto') {
                metodoSeleccionadoTexto.textContent = 'efectivo';
            }

            calcularTotales();
        });

        montoRecibidoInput.addEventListener('input', () => {
            const total = parseFloat(modalTotalPagar.textContent.replace(MONEDA, '')) || 0;
            const recibido = parseFloat(montoRecibidoInput.value) || 0;
            cambioClienteSpan.textContent = `${MONEDA}${(recibido - total).toFixed(2)}`;
        });

        // Eventos para pago mixto
        montoUsarSaldoInput.addEventListener('input', () => {
            montoUsarSaldo = parseFloat(montoUsarSaldoInput.value) || 0;
            const totalFinal = parseFloat(modalTotalPagar.textContent.replace(MONEDA, '')) || 0;
            const maxPermitido = Math.min(saldoClienteDisponible, totalFinal);

            if (montoUsarSaldo > maxPermitido) {
                montoUsarSaldoInput.value = maxPermitido.toFixed(2);
                montoUsarSaldo = maxPermitido;
            }

            actualizarMontosPagoMixto();
        });

        btnUsarSaldoMax.addEventListener('click', () => {
            const totalFinal = parseFloat(modalTotalPagar.textContent.replace(MONEDA, '')) || 0;
            const maxSaldoUsar = Math.min(saldoClienteDisponible, totalFinal);
            montoUsarSaldoInput.value = maxSaldoUsar.toFixed(2);
            montoUsarSaldo = maxSaldoUsar;
            actualizarMontosPagoMixto();
        });

        montoOtroMetodoInput.addEventListener('input', () => {
            montoOtroMetodo = parseFloat(montoOtroMetodoInput.value) || 0;
            actualizarMontosPagoMixto();
        });

        btnConfirmarVenta.addEventListener('click', function() {
            this.disabled = true;

            const totalFinal = parseFloat(modalTotalPagar.textContent.replace(MONEDA, '')) || 0;
            const metodoPago = metodoPagoSelect.value;

            // Validaciones según método de pago
            if (metodoPago === 'saldo' && saldoClienteDisponible < totalFinal) {
                alert('Saldo insuficiente para realizar esta compra');
                this.disabled = false;
                return;
            }

            if (metodoPago === 'mixto') {
                const totalPagado = montoUsarSaldo + montoOtroMetodo;
                if (Math.abs(totalPagado - totalFinal) > 0.01) {
                    alert('El total pagado no coincide con el total de la venta. Por favor ajuste los montos.');
                    this.disabled = false;
                    return;
                }

                if (montoUsarSaldo > saldoClienteDisponible) {
                    alert('No puede usar más saldo del disponible');
                    this.disabled = false;
                    return;
                }
            }

            if (metodoPago === 'efectivo') {
                const recibido = parseFloat(montoRecibidoInput.value) || 0;
                if (recibido < totalFinal) {
                    alert('El monto recibido es menor al total a pagar');
                    this.disabled = false;
                    return;
                }
            }

            const ventaData = {
                id_cliente: idClienteSelect.value || null,
                metodo_pago: metodoPago,
                descuento_manual: descuentoManual,
                monto_usar_saldo: metodoPago === 'mixto' ? montoUsarSaldo : (metodoPago === 'saldo' ? totalFinal : 0),
                monto_otro_metodo: metodoPago === 'mixto' ? montoOtroMetodo : 0,
                observacion: document.getElementById('observacion_venta').value.trim(),
                carrito: carrito.map(i => ({
                    id: i.id,
                    tipo: i.tipo,
                    cantidad: i.cantidad,
                    precio: i.precio
                }))
            };

            fetch('<?php echo BASE_URL; ?>venta/guardar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(ventaData)
                }).then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (idClienteSelect.value) {
                            sellosClienteActuales += carrito.some(i => i.tipo === 'producto') ? 1 : 0;
                            if (sellosClienteActuales > 12) sellosClienteActuales %= 12;
                            sellosClienteSpan.textContent = sellosClienteActuales;

                            // Actualizar saldo visualmente si se usó
                            if (metodoPago === 'saldo' || metodoPago === 'mixto') {
                                saldoClienteDisponible = Math.max(0, saldoClienteDisponible - (metodoPago === 'saldo' ? totalFinal : montoUsarSaldo));
                                saldoDisponibleSpan.textContent = `${MONEDA}${saldoClienteDisponible.toFixed(2)}`;
                                saldoResumenClienteSpan.textContent = `${MONEDA}${saldoClienteDisponible.toFixed(2)}`;

                                if (saldoClienteDisponible === 0) {
                                    infoSaldoClienteDiv.style.display = 'none';
                                }
                            }
                        }
                        
                        // Abrir ticket en nueva pestaña
                        window.open(`<?php echo BASE_URL; ?>venta/ticket/${data.id_venta}`, '_blank');
                        
                        // Cerrar modal y limpiar datos
                        modalPago.hide();
                        
                        // Recargar la página después de un breve retraso
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                        
                    } else {
                        alert('Error: ' + (data.error || 'Ocurrió un error'));
                        this.disabled = false;
                    }
                }).catch(err => {
                    console.error('Error al guardar venta:', err);
                    alert('Error de conexión');
                    this.disabled = false;
                });
        });

        function renderTarjetaSellos(sellosActuales, sellosAAgregar = 0) {
            tarjetaSellosDiv.innerHTML = '';
            if (!idClienteSelect.value) return;
            let totalSellos = sellosActuales + sellosAAgregar;
            if (totalSellos > 12) totalSellos = 12;
            for (let i = 1; i <= 12; i++) {
                const icon = document.createElement('i');
                icon.classList.add('fas', 'fa-star', 'fa-2x', 'me-1');
                if (i <= sellosActuales) icon.classList.add('text-warning');
                else if (i <= totalSellos) icon.classList.add('text-success');
                else icon.classList.add('text-secondary');
                tarjetaSellosDiv.appendChild(icon);
            }
        }

        // --- Autocomplete clientes CORREGIDO ---
        $("#buscarCliente").autocomplete({
            source: function(request, response) {
                console.log("Buscando cliente:", request.term);
                
                $.ajax({
                    url: "<?php echo BASE_URL; ?>cliente/buscar",
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        console.log("Respuesta completa del servidor:", data);
                        
                        const opciones = [{
                            label: "Venta genérica",
                            value: "Venta genérica",
                            id: null,
                            dni: "-",
                            sellos: 0,
                            saldo: 0
                        }].concat(data.map(item => {
                            console.log("Procesando cliente:", item);
                            return {
                                label: item.nombre_cliente + " (" + item.documento_identidad + ") - Saldo: " + MONEDA + (parseFloat(item.saldo) || 0).toFixed(2),
                                value: item.nombre_cliente,
                                id: item.id_cliente,
                                dni: item.documento_identidad,
                                sellos: parseInt(item.sellos) || 0,
                                saldo: parseFloat(item.saldo) || 0
                            };
                        }));
                        response(opciones);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error en búsqueda de clientes:", error, xhr.responseText);
                        response([{
                            label: "Venta genérica",
                            value: "Venta genérica",
                            id: null,
                            dni: "-",
                            sellos: 0,
                            saldo: 0
                        }]);
                    }
                });
            },
            minLength: 0,
            select: function(event, ui) {
                console.log("Cliente seleccionado COMPLETO:", ui.item);

                idClienteSelect.value = ui.item.id;
                nombreClienteSpan.textContent = ui.item.value;
                dniClienteSpan.textContent = ui.item.dni;
                sellosClienteActuales = parseInt(ui.item.sellos) || 0;
                sellosClienteSpan.textContent = sellosClienteActuales;

                // 🔹 CORRECCIÓN: Asegurar que el saldo sea numérico
                saldoClienteDisponible = parseFloat(ui.item.saldo) || 0;
                console.log("Saldo parseado:", saldoClienteDisponible);
                
                saldoResumenClienteSpan.textContent = `${MONEDA}${saldoClienteDisponible.toFixed(2)}`;

                // Mostrar información de saldo SIEMPRE que el cliente tenga saldo
                if (saldoClienteDisponible > 0) {
                    saldoDisponibleSpan.textContent = `${MONEDA}${saldoClienteDisponible.toFixed(2)}`;
                    infoSaldoClienteDiv.style.display = 'block';
                    textoAyudaSaldo.textContent = 'Este saldo puede ser usado para pagos futuros';
                    textoAyudaSaldo.className = 'text-muted';
                } else {
                    infoSaldoClienteDiv.style.display = 'none';
                }

                infoClienteDiv.style.display = 'block';
                descuentoManual = 0;
                calcularTotales();
            },
            response: function(event, ui) {
                console.log("Clientes encontrados en response:", ui.content);
            }
        }).focus(function() {
            $(this).autocomplete("search", "");
        });

        // Inicializar
        calcularTotales();
        metodoPagoSelect.dispatchEvent(new Event('change'));
    });
</script>