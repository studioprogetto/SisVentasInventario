<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/ProyectoWeb/mi_sistema_mvc/public/');
}

if (!function_exists('getMoneda')) {
    function getMoneda()
    {
        return 'S/ ';
    }
}

// Procesar imágenes
foreach ($productos as &$p) {

    $img = BASE_URL . 'img/no-image.png';

    // 1. imagen_path (archivo local)
    if (!empty($p['imagen_path'])) {

        $rutaLocal = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . ltrim($p['imagen_path'], '/');

        if (file_exists($rutaLocal)) {
            $img = BASE_URL . ltrim($p['imagen_path'], '/');
        }

        // 2. imagen_url (internet)
    } elseif (!empty($p['imagen_url']) && filter_var($p['imagen_url'], FILTER_VALIDATE_URL)) {

        $img = $p['imagen_url'];
    }

    $p['imagen_src'] = $img;
}
unset($p);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Etiquetas STUDIO & PROGETTO</title>

    <style>
        body {
            margin: 0;
            background: #f5f5f5;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px;
        }

        /* ETIQUETA */
        .label {
            width: 120mm;
            height: 75mm;
            background: #fff;
            border: 3px dashed #000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* MARCA DE AGUA DIAGONAL */
        .label::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 90%;
            height: 90%;
            background-image: url("<?= BASE_URL ?>img/logo.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.06;
            transform: translate(-50%, -50%);
            z-index: 0;
        }

        /* TODO ENCIMA DE LA MARCA DE AGUA */
        .label>* {
            position: relative;
            z-index: 1;
        }

        /* HEADER */
        .header {
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            gap: 10px;
            text-align: center;
        }

        .header img {
            height: 35px;
        }

        .header-title {
            font-size: 26px;
            font-weight: 900;
            text-transform: uppercase;
            color: #1A2A44;
        }

        /* CUERPO */
        .body {
            flex: 1;
            display: flex;
        }

        /* IMAGEN */
        .image {
            width: 40%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
        }

        .image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* INFO */
        .info {
            width: 60%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 10px;
        }

        /* NOMBRE */
        .nombre {
            font-size: 22px;
            font-weight: 900;
            color: #000;
            text-align: center;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .descripcion {
            font-size: 17px;
            color: #222;
            margin-top: 5px;
            line-height: 1.2;
            text-transform: uppercase;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* PRECIO */
        .precio {
            font-size: 48px;
            font-weight: bold;
            color: #000;
            text-align: right;
        }

        /* FOOTER */
        .footer {
            color: #ffc107;
            text-align: center;
            font-size: 12px;
            padding: 3px;
            font-weight: bold;
        }

        /* BOTÓN PRINT */
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }

        .print-btn button {
            padding: 12px 20px;
            font-size: 16px;
            background: #1A2A44;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        @media print {
            .print-btn {
                display: none;
            }

            body {
                background: white;
            }

            .label {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="print-btn">
        <button onclick="window.print()">🖨 Imprimir</button>
    </div>

    <div class="container">

        <?php if (empty($productos)): ?>
            <p>No hay productos</p>
        <?php else: ?>

            <?php foreach ($productos as $producto): ?>

                <div class="label">

                    <!-- HEADER -->
                    <div class="header">
                        <img src="<?= BASE_URL ?>img/logo.png" alt="Logo">
                        <div class="header-title">
                            STUDIO & PROGETTO
                        </div>
                    </div>

                    <!-- CUERPO -->
                    <div class="body">

                        <!-- IMAGEN -->
                        <div class="image">
                            <img src="<?= htmlspecialchars($producto['imagen_src']) ?>"
                                onerror="this.onerror=null; this.src='<?= BASE_URL ?>img/no-image.png';">
                        </div>

                        <!-- INFO -->
                        <div class="info">

                            <div>
                                <div class="nombre">
                                    <?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?>
                                </div>

                                <div class="descripcion">
                                    <?= htmlspecialchars(substr($producto['descripcion'] ?? 'Sin descripción', 0, 80)) ?>
                                </div>
                            </div>

                            <div class="precio">
                                <?= getMoneda() . number_format($producto['precio_venta'] ?? 0, 2) ?>
                            </div>

                        </div>

                    </div>



                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>

</html>