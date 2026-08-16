<!DOCTYPE html> 
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Servicios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
</head>
<body>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="m-0 text-primary"><i class="fas fa-concierge-bell"></i> Gestión de Servicios</h2>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#servicioModal">
                    <i class="fas fa-plus"></i> Agregar Servicio
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Formulario búsqueda -->
            <form method="GET" action="<?php echo BASE_URL; ?>servicio" class="mb-3">
                <div class="input-group">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o descripción..." value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                    <?php if (!empty($_GET['buscar'])): ?>
                        <a href="<?php echo BASE_URL; ?>servicio" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Tabla servicios -->
            <div class="table-responsive">
                <table id="tablaServicios" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($servicios) && $servicios->num_rows > 0): ?>
                            <?php while ($s = $servicios->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($s['descripcion']); ?></td>
                                    <td><?php echo number_format($s['precio_venta'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $s['activo'] ? 'success' : 'danger'; ?>">
                                            <?php echo $s['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>servicio/detalle/<?php echo $s['id_servicio']; ?>" class="btn btn-sm btn-info" title="Ver Detalle"><i class="fas fa-eye"></i></a>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#servicioModal"
                                            data-id="<?php echo $s['id_servicio']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($s['nombre']); ?>"
                                            data-descripcion="<?php echo htmlspecialchars($s['descripcion']); ?>"
                                            data-precio="<?php echo $s['precio_venta']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($s['activo']): ?>
                                            <a href="<?php echo BASE_URL; ?>servicio/cambiarEstado/<?php echo $s['id_servicio']; ?>/0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                                        <?php else: ?>
                                            <a href="<?php echo BASE_URL; ?>servicio/cambiarEstado/<?php echo $s['id_servicio']; ?>/1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No hay servicios.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal servicio -->
<div class="modal fade" id="servicioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>servicio/guardar" method="POST">
                    <input type="hidden" name="id_servicio" id="id_servicio">
                    <div class="mb-3"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre" id="nombre" required></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" id="descripcion" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Precio</label><input type="number" step="0.01" class="form-control" name="precio" id="precio" required></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function () {
    $('#tablaServicios').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 20, 50],
        language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success' },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger',
              orientation: 'landscape', pageSize: 'A4',
              customize: function(doc){ var table = doc.content[1].table; table.widths = Array(table.body[0].length).fill('*'); }
            }
        ]
    });

    // Modal limpiar y rellenar
    $('#servicioModal').on('show.bs.modal', function(event){
        const button = $(event.relatedTarget);
        const modal = $(this);
        modal.find('input, textarea').val('');
        if(button.hasClass('edit-btn')){
            modal.find('#id_servicio').val(button.data('id'));
            modal.find('#nombre').val(button.data('nombre'));
            modal.find('#descripcion').val(button.data('descripcion'));
            modal.find('#precio').val(button.data('precio'));
            modal.find('.modal-title').text('Editar Servicio');
        } else {
            modal.find('.modal-title').text('Agregar Nuevo Servicio');
        }
    });
});
</script>

</body>
</html>