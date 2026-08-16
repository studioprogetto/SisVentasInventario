<div class="container-fluid">
    <h1 class="mt-4 text-primary"><i class="fas fa-chart-line"></i> Módulo de Reportes</h1>
    <div class="d-flex justify-content-end">
        <a href="<?php echo BASE_URL; ?>home" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>

    <p>Selecciona un reporte para visualizar los datos consolidados de tu negocio.</p>

    <div class="row">
        <?php if (tienePermiso('reportes_ver_ventas')): ?>
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100 shadow-hover">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Reporte de Ventas</h5>
                        <p class="card-text">Analiza los ingresos y productos más vendidos.</p>
                        <a href="<?php echo BASE_URL; ?>reporte/ventas" class="btn btn-primary">Ir al Reporte</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (tienePermiso('reportes_ver_inventario')): ?>
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100 shadow-hover">
                    <div class="card-body">
                        <i class="fas fa-boxes fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Reporte de Inventario</h5>
                        <p class="card-text">Consulta el stock actual y el valor de tus activos.</p>
                        <a href="<?php echo BASE_URL; ?>reporte/inventario" class="btn btn-primary">Ir al Reporte</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 🔹 REPORTE DE CAJA MEJORADO -->
        <div class="col-md-4 mb-4">
            <div class="card text-center h-100 shadow-hover border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cash-register"></i> Reporte de Caja</h5>
                </div>
                <div class="card-body">
                    <i class="fas fa-cash-register fa-3x text-danger mb-3"></i>
                    <h5 class="card-title">Reporte de Caja Detallado</h5>
                    <p class="card-text">Consulta el flujo de efectivo, métodos de pago y ganancias por turno.</p>
                    
                    <div class="d-grid gap-2">
                        <!-- 🔹 NUEVO: Botón para vista detallada -->
                        <a href="<?php echo BASE_URL; ?>caja/reporteDetallado" class="btn btn-primary">
                            <i class="fas fa-chart-bar me-1"></i> Ver Reporte Detallado
                        </a>
                        
                        <!-- 🔹 NUEVO: Botón para actualizar datos -->
                        <button type="button" class="btn btn-warning" onclick="actualizarDatosCaja()">
                            <i class="fas fa-sync-alt me-1"></i> Actualizar Datos
                        </button>
                        
                        <div class="btn-group w-100" role="group">
                            <a href="<?php echo BASE_URL; ?>caja/pdf" class="btn btn-danger">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                            <a href="<?php echo BASE_URL; ?>caja/excel" class="btn btn-success">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </a>
                        </div>
                    </div>
                    
                    <!-- 🔹 NUEVO: Información adicional -->
                    <div class="mt-3 p-2 bg-light rounded">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Incluye: Métodos de pago, ganancias, costos y comparativa por turnos
                        </small>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- 🔹 NUEVO: Sección de reportes adicionales -->
    <div class="row mt-4">
        <!-- Reporte de Métodos de Pago -->
        <div class="col-md-4 mb-4">
            <div class="card text-center h-100 shadow-hover">
                <div class="card-body">
                    <i class="fas fa-credit-card fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Métodos de Pago</h5>
                    <p class="card-text">Distribución de ventas por método de pago.</p>
                    <a href="<?php echo BASE_URL; ?>reporte/metodos-pago" class="btn btn-outline-info">Ver Reporte</a>
                </div>
            </div>
        </div>

        <!-- Reporte de Productos -->
        <div class="col-md-4 mb-4">
            <div class="card text-center h-100 shadow-hover">
                <div class="card-body">
                    <i class="fas fa-chart-pie fa-3x text-purple mb-3"></i>
                    <h5 class="card-title">Productos Más Vendidos</h5>
                    <p class="card-text">Top productos por cantidad y revenue.</p>
                    <a href="<?php echo BASE_URL; ?>reporte/top-productos" class="btn btn-outline-purple">Ver Reporte</a>
                </div>
            </div>
        </div>

        <!-- Reporte de Rentabilidad -->
        <div class="col-md-4 mb-4">
            <div class="card text-center h-100 shadow-hover">
                <div class="card-body">
                    <i class="fas fa-trend-up fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Análisis de Rentabilidad</h5>
                    <p class="card-text">Margenes de ganancia y eficiencia operativa.</p>
                    <a href="<?php echo BASE_URL; ?>reporte/rentabilidad" class="btn btn-outline-success">Ver Reporte</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🔹 NUEVO: Modal para actualización de datos -->
<div class="modal fade" id="modalActualizacion" tabindex="-1" aria-labelledby="modalActualizacionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalActualizacionLabel">
                    <i class="fas fa-sync-alt me-2"></i>Actualizando Datos de Caja
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-warning mb-3" role="status">
                    <span class="visually-hidden">Actualizando...</span>
                </div>
                <p id="mensajeActualizacion">Calculando datos reales desde las ventas...</p>
                <div class="progress mb-3">
                    <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 🔹 Función para actualizar datos de caja
function actualizarDatosCaja() {
    if (!confirm('¿Estás seguro de que quieres actualizar todos los datos de caja?\n\nEsto recalculará los datos desde las ventas reales y puede tomar unos momentos.')) {
        return;
    }

    // Mostrar modal de progreso
    const modal = new bootstrap.Modal(document.getElementById('modalActualizacion'));
    modal.show();

    const mensaje = document.getElementById('mensajeActualizacion');
    const barraProgreso = document.getElementById('barraProgreso');

    // Simular progreso inicial
    let progreso = 0;
    const intervalo = setInterval(() => {
        progreso += 5;
        barraProgreso.style.width = progreso + '%';
        if (progreso >= 90) clearInterval(intervalo);
    }, 200);

    fetch('<?php echo BASE_URL; ?>caja/actualizarDatosTurnos')
        .then(response => response.json())
        .then(data => {
            clearInterval(intervalo);
            barraProgreso.style.width = '100%';
            
            if (data.success) {
                mensaje.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>¡Actualización completada!</strong><br>
                        ${data.mensaje}
                    </div>
                `;
                
                setTimeout(() => {
                    modal.hide();
                    // Recargar la página para mostrar datos actualizados
                    location.reload();
                }, 2000);
            } else {
                mensaje.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error en la actualización:</strong><br>
                        ${data.error}
                    </div>
                `;
                
                setTimeout(() => {
                    modal.hide();
                }, 3000);
            }
        })
        .catch(error => {
            clearInterval(intervalo);
            mensaje.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error de conexión:</strong><br>
                    ${error.message}
                </div>
            `;
            
            setTimeout(() => {
                modal.hide();
            }, 3000);
        });
}

// 🔹 Efectos hover en las tarjetas
document.addEventListener('DOMContentLoaded', function() {
    // Efecto hover en tarjetas
    document.querySelectorAll('.shadow-hover').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'all 0.3s ease';
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Mostrar información de debug
    console.log('=== MÓDULO DE REPORTES CARGADO ===');
    console.log('Base URL:', '<?php echo BASE_URL; ?>');
    console.log('Rutas disponibles:');
    console.log('- Reporte detallado caja:', '<?php echo BASE_URL; ?>caja/reporteDetallado');
    console.log('- Actualizar datos:', '<?php echo BASE_URL; ?>caja/actualizarDatosTurnos');
    console.log('- Exportar PDF:', '<?php echo BASE_URL; ?>caja/pdf');
    console.log('- Exportar Excel:', '<?php echo BASE_URL; ?>caja/excel');
});

// 🔹 Función para mostrar notificaciones
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
</script>

<style>
.shadow-hover {
    transition: all 0.3s ease;
    border: none;
    border-radius: 12px;
}

.shadow-hover:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.btn-group .btn {
    border-radius: 0.375rem;
}

/* Colores personalizados */
.text-purple {
    color: #6f42c1 !important;
}

.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
}

.btn-outline-purple:hover {
    background-color: #6f42c1;
    color: white;
}

/* Progress bar animation */
.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    0% { background-position: 1rem 0; }
    100% { background-position: 0 0; }
}

/* Responsive */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.375rem;
        margin-bottom: 5px; 
    }
    
    .card-body {
        padding: 1.25rem;
    }
}

/* Mejoras visuales */
.card-title {
    font-weight: 600;
    color: #343a40;
}

.card-text {
    color: #6c757d;
    min-height: 48px;
}

.d-grid .btn {
    margin-bottom: 8px;
}
</style>