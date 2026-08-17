<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ticket de Venta #<?php echo htmlspecialchars($venta['id_venta'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #fafafa;
        }

        .text-small {
            font-size: 0.75rem;
        }

        .ticket {
            max-width: 350px;
            margin: 20px auto;
            padding: 15px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 8px;
        }

        .border-top-dotted {
            border-top: 1px dotted #000;
        }

        .devolucion-info {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 6px;
            border: 1px dashed #1a2a44;
            margin: 10px 0;
            font-size: 0.8rem;
            text-align: center;
        }

        .devolucion-info strong {
            color: #1a2a44;
        }

        /* Sellos dorados */
        .sellos-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        @media print {
            @page {
                size: auto;
                margin: 5mm;
            }

            body * {
                visibility: hidden;
            }

            .ticket,
            .ticket * {
                visibility: visible;
            }

            .ticket {
                margin: 0 auto;
                border: none;
                width: 80mm;
                max-width: 100%;
                text-align: center;
            }

            .ticket table {
                margin: 0 auto;
                width: 100%;
            }

            .no-print {
                display: none;
            }

            .devolucion-info {
                border: 1px dashed #999;
            }
        }
    </style>
</head>

<body>

    <?php
    require_once __DIR__ . '/../../../config/database.php';
    require_once __DIR__ . '/../../../app/models/Cliente.php';

    if (empty($venta)) {
        echo '<div class="alert alert-danger text-center mt-5">Venta no encontrada o inválida.</div>';
        exit;
    }

    global $conexion;

    $descuentoSellos = isset($venta['descuento_sellos']) ? (float)$venta['descuento_sellos'] : 0;
    $descuentoManual = isset($venta['descuento_manual']) ? (float)$venta['descuento_manual'] : 0;

    $detallesArray = [];
    $totalOriginal = 0;

    if (!empty($detalles) && is_array($detalles)) {
        foreach ($detalles as $item) {
            $item['tipo'] = $item['tipo_item'] ?? 'producto';
            $item['nombre_item'] = $item['nombre_item'] ?? ($item['producto_nombre'] ?? 'Item');
            $item['cantidad'] = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
            $item['precio_unitario'] = isset($item['precio_unitario']) ? (float)$item['precio_unitario'] : 0;
            $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];
            $totalOriginal += $item['subtotal'];
            $detallesArray[] = $item;
        }
    }

    $totalDescuentoSellos = $totalOriginal * $descuentoSellos;
    $totalFinal = max(0, $totalOriginal - $totalDescuentoSellos - $descuentoManual);

    // -----------------------------------------------------
    // 🔒 CÁLCULO CORRECTO DE SELLOS
    // -----------------------------------------------------
    $sellosNuevos = intval($venta['sellos_nuevos'] ?? 0);
    $totalSellos = 0;
    $mensajeSellos = "Sigue acumulando hasta el 6º y 12º sello.";

    if (!empty($venta['id_cliente'])) {
        $clienteModel = new Cliente($conexion);
        $totalSellos = $clienteModel->obtenerSellosCliente($venta['id_cliente']);

        if ($totalSellos === 6) {
            $mensajeSellos = "¡Obtuvo 5% de descuento por tener su 6to sello!";
        } elseif ($totalSellos === 12) {
            $mensajeSellos = "¡Obtuvo 10% de descuento y se reiniciaron sus sellos!";
        } elseif ($totalSellos === 0 && $sellosNuevos > 0) {
            $mensajeSellos = "¡Obtuvo 10% de descuento y se reiniciaron sus sellos!";
        }
    }

    // 🔹 Función para renderizar sellos con colores inline
    function renderSellos($cantidad)
    {
        $html = '<div class="sellos-container">';
        for ($i = 1; $i <= 12; $i++) {
            $activo = $i <= $cantidad;
            $color = $activo ? '#d4af37' : '#ccc';
            $stroke = $activo ? '#b8860b' : '#999';
                $html .= '<div class="ticket-square">
            <svg viewBox="0 0 24 24" width="24" height="24">
                <circle cx="12" cy="12" r="10" fill="' . $color . '" stroke="' . $stroke . '" stroke-width="1"></circle>
                <text x="12" y="12" font-size="10" font-weight="bold" fill="#fff" text-anchor="middle" dominant-baseline="middle">' . $i . '</text>
            </svg>
        </div>';
        }
        $html .= '</div>';
        return $html;
    }

    // ========== CÁLCULO DE FECHA DE DEVOLUCIÓN (4 DÍAS) ==========
    $fechaCompra = new DateTime($venta['fecha_venta']);
    $fechaLimite = clone $fechaCompra;
    $fechaLimite->modify('+4 days');
    $fechaCompraStr = $fechaCompra->format('d/m/Y');
    $fechaLimiteStr = $fechaLimite->format('d/m/Y');
    ?>

    <div class="ticket" id="ticket">
        <div class="text-center mb-2">
            <img src="<?php echo BASE_URL; ?>img/logo.png" alt="Logo" class="ticket-logo">
            <h5 class="mt-2 mb-0"><?php echo htmlspecialchars(getConfig('nombre_tienda')); ?></h5>
            <small>
                RUC: <?php echo htmlspecialchars(getConfig('ruc')); ?><br>
                <?php echo nl2br(htmlspecialchars(getConfig('direccion'))); ?><br>
                Tel: <?php echo htmlspecialchars(getConfig('telefono')); ?>
            </small>
        </div>

        <hr class="border-top-dotted">

        <p class="text-center mb-1">
            <strong>Ticket #<?php echo htmlspecialchars($venta['id_venta'] ?? ''); ?></strong><br>
            Cliente: <?php echo htmlspecialchars($venta['nombre_cliente'] ?? 'Venta Genérica'); ?><br>
            Fecha: <?php echo htmlspecialchars($venta['fecha_venta'] ?? ''); ?>
        </p>



        <hr class="border-top-dotted">

        <table class="table table-sm mb-2">
            <thead>
                <tr>
                    <th>Cant.</th>
                    <th>Producto/Servicio</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detallesArray as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['cantidad']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['nombre_item']); ?>
                            <small class="text-muted">(<?php echo $item['tipo'] === 'servicio' ? 'S' : 'P'; ?>)</small>
                        </td>
                        <td class="text-end"><?php echo getMoneda() . number_format($item['subtotal'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <hr class="border-top-dotted">

        <p class="text-end text-small mb-0">
            Subtotal: <?php echo getMoneda() . number_format($totalOriginal, 2); ?>
        </p>

        <?php if ($descuentoSellos > 0 && !empty($venta['id_cliente'])): ?>
            <p class="text-end text-small mb-0 text-success fw-bold">
                Descuento por sellos: -<?php echo getMoneda() . number_format($totalDescuentoSellos, 2); ?>
                (<?php echo ($descuentoSellos * 100); ?>%)
            </p>
        <?php endif; ?>

        <?php if ($descuentoManual > 0): ?>
            <p class="text-end text-small mb-0 text-warning">
                Descuento manual: -<?php echo getMoneda() . number_format($descuentoManual, 2); ?>
            </p>
        <?php endif; ?>

        <h5 class="text-end mt-2">TOTAL A PAGAR: <?php echo getMoneda() . number_format($totalFinal, 2); ?></h5>

        <p class="text-end text-small mb-0">
            Método de pago: <?php echo !empty($venta['metodo_pago']) ? ucfirst(str_replace('_', ' ', $venta['metodo_pago'])) : 'No especificado'; ?>
        </p>

        <hr>

        <?php if (!empty($venta['id_cliente'])): ?>
            <div class="text-center">
                <strong>Sellos del cliente:</strong><br>
                <?php echo renderSellos($totalSellos); ?>
                <small class="text-muted">
                    <?php echo $mensajeSellos; ?>
                </small>
                <?php if ($sellosNuevos > 0): ?>
                    <br><small class="text-success">
                        +<?php echo $sellosNuevos; ?> sello(s) agregado(s) en esta compra
                    </small>
                <?php endif; ?>
            </div>
            <hr>
        <?php endif; ?>

        <p class="text-center text-small mt-2">
            <strong>Horario de atención:</strong><br>
            <?php echo nl2br(htmlspecialchars(getConfig('horario_atencion'))); ?>
        </p>

        <p class="text-center text-small">
            Email: <?php echo htmlspecialchars(getConfig('email')); ?><br>
        </p>

        <p class="text-center mt-3"><strong>¡Gracias por su compra!</strong></p>
        <hr class="border-top-dotted">
    <!-- POLÍTICA DE DEVOLUCIÓN -->
    <div class="devolucion-info">
        <strong>Política de devolución:</strong><br>
        Los productos comprados tienen un plazo de <strong>4 días</strong> para devolución.<br>
        <small>
            Comprado el: <strong><?php echo $fechaCompraStr; ?></strong><br>
            Límite de devolución: <strong><?php echo $fechaLimiteStr; ?></strong><br>
            <em>Después de esta fecha no se aceptarán cambios ni devoluciones.</em>
        </small>
    </div>

    </div>
    
    <div class="text-center no-print mt-3">
        <button onclick="window.print();" class="btn btn-primary">Imprimir Ticket</button>
        <button onclick="guardarPDF()" class="btn btn-danger">Guardar en PDF</button>
        <a href="<?php echo BASE_URL; ?>venta" class="btn btn-secondary">Volver al POS</a>
        <a href="<?php echo BASE_URL; ?>venta/historial" class="btn btn-info text-white">Ver Historial</a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
    <script>
        function guardarPDF() {
            const elemento = document.getElementById("ticket");
            const opciones = {
                margin: 10,
                filename: 'ticket_<?php echo $venta['id_venta'] ?? '0'; ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 3,
                    useCORS: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            html2pdf().set(opciones).from(elemento).save();
        }
    </script>

</body>

</html>