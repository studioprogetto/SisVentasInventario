<div class="container-fluid">
    <h1 class="mt-4 fw-bold text-primary">Dashboard</h1>
    <p>🌸 Bienvenida <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?></strong>. Desde aquí puedes gestionar las operaciones del sistema.
        Tenga un bonito día y arrase con las ventas!!! 💐</p>

    <!-- Tarjetas de Resumen -->
    <div class="row mb-4">
        <!-- Ventas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-success h-100 shadow-hover">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_ventas; ?></div>
                        <div>Ventas Realizadas</div>
                    </div>
                    <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="<?php echo BASE_URL; ?>venta/historial">
                    <span>Ver Detalles</span> <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Compras -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-danger h-100 shadow-hover">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_compras; ?></div>
                        <div>Compras Realizadas</div>
                    </div>
                    <i class="fas fa-truck fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="<?php echo BASE_URL; ?>compra">
                    <span>Ver Detalles</span> <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Productos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-warning h-100 shadow-hover">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_productos; ?></div>
                        <div>Productos en Inventario</div>
                    </div>
                    <i class="fas fa-box-open fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="<?php echo BASE_URL; ?>producto">
                    <span>Ver Detalles</span> <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Productos bajos de stock -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-info h-100 shadow-hover">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_productos_bajos; ?></div>
                        <div>Productos Bajos de Stock</div>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="<?php echo BASE_URL; ?>reporte/stockBajo">
                    <span>Ver Detalles</span> <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Gráfico de Ventas - Ocupa todo el ancho -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-chart-line me-2"></i>Ventas de los Últimos 5 Días
                    </h5>
                    
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="graficoVentas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Movimientos de Inventario - Ocupa todo el ancho -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-exchange-alt me-2"></i>Movimientos Recientes de Inventario
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($movimientos_inventario)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Almacén</th>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos_inventario as $movimiento): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?php echo htmlspecialchars($movimiento['producto_nombre'] ?? 'N/A'); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $this->getBadgeClass($movimiento['tipo_movimiento']); ?>">
                                                    <?php echo $this->getTipoMovimientoTexto($movimiento['tipo_movimiento']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold <?php echo $movimiento['tipo_movimiento'] === 'venta' ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo $movimiento['cantidad']; ?> unidades
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($movimiento['almacen_nombre']): ?>
                                                    <i class="fas fa-warehouse me-1 text-muted"></i>
                                                    <?php echo htmlspecialchars($movimiento['almacen_nombre']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($movimiento['usuario_nombre']): ?>
                                                    <i class="fas fa-user me-1 text-muted"></i>
                                                    <?php echo htmlspecialchars($movimiento['usuario_nombre']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo $movimiento['fecha_formateada']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay movimientos recientes</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white border-0">
                    <a href="<?php echo BASE_URL; ?>reporte/inventario" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-1"></i>Ver Todos los Movimientos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 🔹 CORREGIDO: Datos del gráfico
    const labelsVentas = <?php echo $labels_json; ?>;
    const dataVentas = <?php echo $data_json; ?>;

    // 🔹 CORREGIDO: Función mejorada para formatear fechas
    const formatearFecha = (fechaStr) => {
        const fecha = new Date(fechaStr + 'T00:00:00'); // 🔹 CORREGIDO: Agregar hora para evitar problemas de zona horaria
        const opciones = { 
            day: '2-digit', 
            month: 'short',
            weekday: 'short'
        };
        return fecha.toLocaleDateString('es-ES', opciones);
    };

    console.log('Datos del gráfico:', { labels: labelsVentas, data: dataVentas });

    const ctx = document.getElementById('graficoVentas').getContext('2d');

    // 🔹 CORREGIDO: Configuración mejorada del gradiente
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(21, 58, 223, 0.8)');
    gradient.addColorStop(0.7, 'rgba(21, 58, 223, 0.4)');
    gradient.addColorStop(1, 'rgba(21, 58, 223, 0.1)');

    // 🔹 CORREGIDO: Configuración del gráfico mejorada
    const config = {
        type: 'bar', // 🔹 CAMBIADO: Volvemos a barras para mejor visualización
        data: {
            labels: labelsVentas.map(fecha => formatearFecha(fecha)),
            datasets: [{
                label: 'Ventas (S/)',
                data: dataVentas,
                backgroundColor: gradient,
                borderColor: '#153adfff',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 20,
                    right: 20,
                    bottom: 10,
                    left: 10
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: value => 'S/ ' + value.toLocaleString('es-PE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }),
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        padding: 10
                    },
                    title: {
                        display: true,
                        text: 'Monto en Soles (S/)',
                        font: {
                            family: "'Inter', sans-serif",
                            weight: 'bold',
                            size: 13
                        },
                        padding: {top: 10, bottom: 20}
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        family: "'Inter', sans-serif",
                        size: 13
                    },
                    bodyFont: {
                        family: "'Inter', sans-serif",
                        size: 13
                    },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const valor = context.raw;
                            if (valor === 0) {
                                return 'Sin ventas este día';
                            }
                            return 'Ventas: S/ ' + valor.toLocaleString('es-PE', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    };

    // 🔹 CORREGIDO: Crear el gráfico con manejo de errores
    try {
        window.graficoVentas = new Chart(ctx, config);
    } catch (error) {
        console.error('Error al crear el gráfico:', error);
        document.getElementById('graficoVentas').innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No se pudo cargar el gráfico de ventas
            </div>
        `;
    }

    // 🔹 NUEVO: Función para actualizar datos del gráfico
    function actualizarGraficoVentas(nuevosLabels, nuevosDatos) {
        if (window.graficoVentas instanceof Chart) {
            window.graficoVentas.data.labels = nuevosLabels.map(fecha => formatearFecha(fecha));
            window.graficoVentas.data.datasets[0].data = nuevosDatos;
            window.graficoVentas.update('active');
        }
    }

    // 🔹 NUEVO: Botón de diagnóstico (para debugging)
    function verDiagnostico() {
        fetch('<?php echo BASE_URL; ?>home/diagnosticoVentas')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Diagnóstico completo:', data);
                    
                    // Mostrar información útil en consola
                    console.log('=== DIAGNÓSTICO VENTAS ===');
                    console.log('Fecha actual:', data.fecha_actual);
                    console.log('Últimos 5 días:', data.ultimos_5_dias);
                    console.log('Detalle por día y estado:');
                    data.diagnostico.forEach(item => {
                        console.log(`- ${item.dia}: ${item.cantidad_ventas} ventas, S/ ${item.total}, estado: ${item.estado}`);
                    });
                    
                    alert('Diagnóstico completo. Revisa la consola del navegador (F12) para ver los detalles.');
                } else {
                    alert('Error en diagnóstico: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error en diagnóstico:', error);
                alert('Error al obtener diagnóstico: ' + error.message);
            });
    }

    // 🔹 CORREGIDO: Efecto hover mejorado en las tarjetas
    document.querySelectorAll('.shadow-hover').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
            this.style.transition = 'all 0.3s ease';
            this.style.boxShadow = '0 12px 35px rgba(0,0,0,0.2) !important';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '';
        });
    });

    // 🔹 NUEVO: Cargar datos actualizados cada 5 minutos
    function cargarDatosActualizados() {
        fetch('<?php echo BASE_URL; ?>home/obtenerDatosActualizados')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarGraficoVentas(data.labels, data.ventas);
                    // Actualizar tarjeta de ventas si es necesario
                    document.querySelector('.card.bg-success .fs-3').textContent = data.totalVentas;
                    
                    console.log('Datos actualizados:', {
                        labels: data.labels,
                        ventas: data.ventas,
                        totalVentas: data.totalVentas
                    });
                }
            })
            .catch(error => console.error('Error al actualizar datos:', error));
    }

    // Actualizar cada 5 minutos (300000 ms)
    setInterval(cargarDatosActualizados, 300000);

    // 🔹 NUEVO: Función para forzar actualización manual
    function forzarActualizacion() {
        console.log('Forzando actualización de datos...');
        cargarDatosActualizados();
    }

    // 🔹 NUEVO: Mostrar información de debug al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== DASHBOARD CARGADO ===');
        console.log('Labels:', labelsVentas);
        console.log('Data:', dataVentas);
        console.log('Total ventas:', <?php echo $total_ventas; ?>);
        
        // Verificar si hay datos válidos
        const tieneVentas = dataVentas.some(valor => valor > 0);
        console.log('¿Tiene ventas en los últimos 5 días?:', tieneVentas);
        
        if (!tieneVentas) {
            console.warn('ADVERTENCIA: No se detectaron ventas en los últimos 5 días');
        }
    });
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

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    border-radius: 12px 12px 0 0 !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-container {
    border-radius: 8px;
    background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
}

.table td {
    vertical-align: middle;
    padding: 12px 8px;
    border-color: #f0f0f0;
}

.table th {
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    padding: 15px 8px;
}

/* 🔹 NUEVO: Estilos para días sin ventas en el gráfico */
.no-data-message {
    text-align: center;
    padding: 40px;
    color: #6c757d;
    font-style: italic;
}

/* Mejoras responsivas */
@media (max-width: 768px) {
    .chart-container {
        height: 300px !important;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .card-header h5 {
        margin-bottom: 0;
    }
}

/* 🔹 NUEVO: Estilos para el botón de diagnóstico */
.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    color: white;
}
</style>