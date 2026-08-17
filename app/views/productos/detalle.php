<?php
// Seleccionar imagen: prioridad imagen_path (subida), luego imagen_url, finalmente placeholder.
$imgSrc = BASE_URL . 'public/img/no-image.png';

if (!empty($producto['imagen_path'])) {
    // ✅ SI imagen_path ES RELATIVA, CONCATENAR CON BASE_URL
    if (strpos($producto['imagen_path'], 'http') === 0) {
        $imgSrc = $producto['imagen_path'];
    } else {
        $imgSrc = BASE_URL . 'public' . $producto['imagen_path'];
    }
} elseif (!empty($producto['imagen_url'])) {
    $imgSrc = $producto['imagen_url'];
}

// Obtener información adicional del producto
$productoCompleto = $this->productoModel->obtenerPorId($producto['id_producto']);
$categorias = $this->productoModel->obtenerCategoriasActivas();
$almacenes = $this->almacenModel->obtenerTodos();

// Buscar nombres de categoría y almacén
$nombreCategoria = 'Sin categoría';
$nombreAlmacen = 'No asignado';

foreach ($categorias as $cat) {
    if ($cat['id_categoria'] == $productoCompleto['id_categoria']) {
        $nombreCategoria = $cat['nombre'];
        break;
    }
}

foreach ($almacenes as $alm) {
    if ($alm['id'] == $productoCompleto['id_almacen']) {
        $nombreAlmacen = $alm['nombre'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle de Producto - Gestión de Inventario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        .product-detail-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .product-detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            transition: all 0.18s ease;
        }

        .product-image {
            height: 280px;
            object-fit: contain;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
        }

        .stock-badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
        }

        .price-display {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .badge-movement {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        .movements-table {
            font-size: 0.9rem;
        }
        
        .movements-table td {
            vertical-align: middle;
        }
        
        .action-buttons {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <!-- Tarjeta de información del producto -->
        <div class="card shadow-sm product-detail-card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="m-0"><i class="fas fa-box"></i> Detalle del Producto</h2>
                </div>
                <a href="<?php echo BASE_URL; ?>producto" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <!-- Columna de imagen -->
                    <div class="col-md-4 mb-4">
                        <div class="text-center">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                 class="product-image w-100" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                 onerror="this.src='<?php echo BASE_URL; ?>public/img/no-image.png'">
                            
                            <div class="mt-3">
                                <span class="stock-badge badge bg-<?php echo ($producto['stock'] > 0) ? 'success' : 'danger'; ?>">
                                    <i class="fas fa-boxes"></i> Stock: <?php echo (int)($producto['stock'] ?? 0); ?>
                                </span>
                                
                                <?php if ($producto['stock_minimo'] > 0): ?>
                                    <span class="stock-badge badge bg-<?php echo ($producto['stock'] <= $producto['stock_minimo']) ? 'warning' : 'info'; ?> ms-1">
                                        <i class="fas fa-exclamation-triangle"></i> Mínimo: <?php echo (int)($producto['stock_minimo'] ?? 0); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna de información -->
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h3 class="text-primary"><?php echo htmlspecialchars($producto['nombre'] ?? 'Sin nombre'); ?></h3>
                                <p class="price-display text-danger">
                                    <?php echo getMoneda() . number_format($producto['precio_venta'] ?? 0, 2); ?>
                                </p>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><span class="info-label">Descripción:</span></p>
                                <p class="info-value"><?php echo !empty($producto['descripcion']) ? htmlspecialchars($producto['descripcion']) : '<em class="text-muted">Sin descripción</em>'; ?></p>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><span class="info-label">Categoría:</span></p>
                                <p class="info-value"><?php echo htmlspecialchars($nombreCategoria); ?></p>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><span class="info-label">Almacén:</span></p>
                                <p class="info-value"><?php echo htmlspecialchars($nombreAlmacen); ?></p>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><span class="info-label">Precio de compra:</span></p>
                                <p class="info-value"><?php echo getMoneda() . number_format($producto['precio_compra'] ?? 0, 2); ?></p>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><span class="info-label">Stock mínimo:</span></p>
                                <p class="info-value"><?php echo (int)($producto['stock_minimo'] ?? 0); ?></p>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><span class="info-label">Estado:</span></p>
                                <p class="info-value">
                                    <span class="badge bg-<?php echo (!empty($producto['activo'])) ? 'success' : 'danger'; ?>">
                                        <?php echo (!empty($producto['activo'])) ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="col-12 action-buttons">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="<?php echo BASE_URL; ?>producto" class="btn btn-outline-secondary">
                                        <i class="fas fa-list"></i> Volver al Listado
                                    </a>
                                    <button class="btn btn-warning" onclick="abrirModalEdicion()">
                                        <i class="fas fa-edit"></i> Editar Producto
                                    </button>
                                    <?php if (!empty($producto['activo'])): ?>
                                        <a href="<?php echo BASE_URL; ?>producto/cambiarEstado/<?php echo $producto['id_producto'] ?? 0; ?>/0" 
                                           class="btn btn-danger action-btn-confirm">
                                            <i class="fas fa-ban"></i> Desactivar
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>producto/cambiarEstado/<?php echo $producto['id_producto'] ?? 0; ?>/1" 
                                           class="btn btn-success action-btn-confirm">
                                            <i class="fas fa-check"></i> Activar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de movimientos (Kardex) -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0 text-primary"><i class="fas fa-history"></i> Kardex del Producto</h3>
                <span class="badge bg-primary"><?php echo isset($movimientos) ? count($movimientos) : 0; ?> movimientos</span>
            </div>
            <div class="card-body">
                <?php if (isset($movimientos) && !empty($movimientos)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover movements-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo de Movimiento</th>
                                    <th>Almacén</th>
                                    <th>Cantidad</th>
                                    <th>Stock Anterior</th>
                                    <th>Stock Nuevo</th>
                                    <th>Usuario</th>
                                    <th>Referencia ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($movimientos as $mov): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($mov['fecha'])); ?></td>
                                    <td>
                                        <?php 
                                        $tipoMovimiento = ucfirst(str_replace('_', ' ', $mov['tipo_movimiento']));
                                        $badgeClass = 'bg-info';
                                        if (strpos(strtolower($mov['tipo_movimiento']), 'entrada') !== false) {
                                            $badgeClass = 'bg-success';
                                        } elseif (strpos(strtolower($mov['tipo_movimiento']), 'salida') !== false) {
                                            $badgeClass = 'bg-danger';
                                        } elseif (strpos(strtolower($mov['tipo_movimiento']), 'ajuste') !== false) {
                                            $badgeClass = 'bg-warning text-dark';
                                        }
                                        ?>
                                        <span class="badge badge-movement <?php echo $badgeClass; ?>">
                                            <?php echo $tipoMovimiento; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['nombre_almacen'] ?? 'N/A'); ?></td>
                                    <td>
                                        <strong class="<?php echo ($mov['cantidad'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($mov['cantidad'] > 0) ? '+' : ''; ?><?php echo $mov['cantidad']; ?>
                                        </strong>
                                    </td>
                                    <td><?php echo $mov['stock_anterior']; ?></td>
                                    <td><strong><?php echo $mov['stock_nuevo']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($mov['nombre_completo']); ?></td>
                                    <td><?php echo $mov['referencia_id']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle text-muted fa-2x mb-2"></i><br>
                        <p class="text-muted">No hay movimientos registrados para este producto.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Producto para edición -->
    <div class="modal fade" id="productoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formProducto" enctype="multipart/form-data" method="post">
                        <input type="hidden" name="id_producto" id="id_producto" value="<?php echo $producto['id_producto']; ?>">
                        <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="descripcion" rows="2"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="id_categoria" id="id_categoria">
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>" <?php echo ($categoria['id_categoria'] == $productoCompleto['id_categoria']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Almacén <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_almacen" id="id_almacen" required>
                                <option value="">Seleccione un almacén</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?php echo $almacen['id']; ?>" <?php echo ($almacen['id'] == $productoCompleto['id_almacen']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($almacen['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-image"></i> Imagen del Producto</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Subir imagen</label>
                                    <input type="file" class="form-control" name="imagen_file" id="imagen_file" accept="image/*">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Pegar URL de imagen</label>
                                    <input type="url" class="form-control" name="imagen_url" id="imagen_url" 
                                           placeholder="https://ejemplo.com/imagen.jpg" 
                                           value="<?php echo !empty($producto['imagen_url']) ? htmlspecialchars($producto['imagen_url']) : ''; ?>">
                                </div>

                                <?php if (!empty($producto['imagen_path']) || !empty($producto['imagen_url'])): ?>
                                <div class="mb-3" id="eliminarImagenContainer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="checkEliminarImagen">
                                        <label class="form-check-label text-danger" for="checkEliminarImagen">
                                            <i class="fas fa-trash"></i> Eliminar imagen actual
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="text-center">
                                    <img id="previewImagen" src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                         alt="Vista previa de imagen"
                                         class="product-image <?php echo (empty($producto['imagen_path']) && empty($producto['imagen_url'])) ? 'd-none' : ''; ?>">
                                    <div id="noImagenText" class="text-muted mt-2 <?php echo (!empty($producto['imagen_path']) || !empty($producto['imagen_url'])) ? 'd-none' : ''; ?>">
                                        <i class="fas fa-image fa-2x mb-2"></i><br>
                                        Vista previa de imagen
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Venta <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control" name="precio_venta" id="precio_venta" 
                                       value="<?php echo $producto['precio_venta']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Compra</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="precio_compra" id="precio_compra"
                                       value="<?php echo $producto['precio_compra']; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Actual <span class="text-danger">*</span></label>
                                <input type="number" min="0" class="form-control" name="stock" id="stock" 
                                       value="<?php echo $producto['stock']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" min="0" class="form-control" name="stock_minimo" id="stock_minimo"
                                       value="<?php echo $producto['stock_minimo']; ?>">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="guardarProductoBtn">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        
        function abrirModalEdicion() {
            $('#productoModal').modal('show');
        }
        
        $(document).ready(function() {
            // Preview al seleccionar archivo
            $('#imagen_file').on('change', function() {
                const file = this.files[0];
                const preview = $('#previewImagen');
                const noImagenText = $('#noImagenText');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.attr('src', e.target.result).show();
                        noImagenText.hide();
                    };
                    reader.readAsDataURL(file);
                    $('#imagen_url').val('');
                    $('#eliminar_imagen').val('0');
                    $('#checkEliminarImagen').prop('checked', false);
                }
            });

            // Preview cuando se pega una URL
            $('#imagen_url').on('input', function() {
                const url = $(this).val().trim();
                const preview = $('#previewImagen');
                const noImagenText = $('#noImagenText');

                if (url) {
                    preview.attr('src', url).show();
                    noImagenText.hide();
                    $('#imagen_file').val('');
                    $('#eliminar_imagen').val('0');
                    $('#checkEliminarImagen').prop('checked', false);
                } else {
                    preview.hide().attr('src', '');
                    noImagenText.show();
                }
            });

            // Manejar checkbox de eliminar imagen
            $('#checkEliminarImagen').on('change', function() {
                $('#eliminar_imagen').val(this.checked ? '1' : '0');
                if (this.checked) {
                    $('#previewImagen').hide();
                    $('#noImagenText').show();
                }
            });

            // Confirmación para acciones de estado
            $('.action-btn-confirm').on('click', function(e) {
                e.preventDefault();
                const url = this.href;
                const action = this.textContent.trim();
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `¿Quieres ${action.toLowerCase()} este producto?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });

            // Envío del formulario
            $('#formProducto').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                $.ajax({
                    url: BASE_URL + 'producto/guardar',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                $('#productoModal').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al guardar el producto'
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>