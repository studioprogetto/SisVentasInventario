<?php
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . "/../../logger.php"; 


class VentaController
{
    private $ventaModel;
    private $clienteModel;

    public function __construct()
    {
        global $conexion;
        write_log("Instanciando VentaController", "INFO");
        $this->ventaModel = new Venta($conexion);
        $this->clienteModel = new Cliente($conexion);
    }


    // 🔹 Vista principal de ventas
    public function index()
    {     write_log("Cargando vista principal de ventas", "INFO");

        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $clientes = $this->ventaModel->obtenerClientesActivos();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/ventas/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // 🔹 Historial de ventas
    public function historial()
    {
        write_log("Cargando historial de ventas", "INFO");

        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $ventas = $this->ventaModel->obtenerTodas();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/ventas/listado_ventas.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    // En controllers/VentaController.php - AGREGAR este método
    public function historialDetallado()
    {
        write_log("Mostrando historial detallado de ventas", "INFO");
        write_log("Query historial detallado ejecutado", "DB");

        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Obtener ventas con información completa
        $sql = "SELECT 
        v.id_venta, 
        v.fecha_venta, 
        v.total_venta, 
        v.metodo_pago,
        v.descuento_sellos,
        v.descuento_manual,
        COALESCE(c.nombre_cliente, 'Venta genérica') as cliente,
        u.nombre_completo as cajero,
        COUNT(dv.id_detalle_venta) as items,
        -- Métodos de pago individuales (SOLO LOS QUE TIENES)
        CASE WHEN v.metodo_pago = 'efectivo' THEN v.total_venta ELSE 0 END as monto_efectivo,
        CASE WHEN v.metodo_pago = 'yape' THEN v.total_venta ELSE 0 END as monto_yape,
        CASE WHEN v.metodo_pago = 'plin' THEN v.total_venta ELSE 0 END as monto_plin,
        CASE WHEN v.metodo_pago = 'agora' THEN v.total_venta ELSE 0 END as monto_agora,
        CASE WHEN v.metodo_pago = 'transferencia' THEN v.total_venta ELSE 0 END as monto_transferencia
    FROM ventas v
    LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
    JOIN usuarios u ON v.id_usuario = u.id_usuario
    LEFT JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
    WHERE v.estado = 'completada'
    GROUP BY v.id_venta
    ORDER BY v.fecha_venta DESC";

        // use the global connection that is provided to the controller's models
        global $conexion;
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        $ventas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/ventas/historial_detallado.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }


    public function obtenerDetallesVenta($id_venta)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            return;
        }
        write_log("Obteniendo detalles de venta ID: $id_venta", "INFO");

        try {
            write_log("Obteniendo detalles de venta ID: $id_venta", "INFO");


            // Asegúrate de que el ID sea numérico
            if (!is_numeric($id_venta)) {
                echo json_encode(['error' => 'ID de venta inválido']);
                return;
            }

            $detalles = $this->ventaModel->obtenerDetallesVentaParaCambio($id_venta);

            if (empty($detalles)) {
                echo json_encode(['error' => 'No se encontraron detalles para esta venta']);
                return;
            }

            echo json_encode($detalles);
        } catch (Exception $e) {
            write_log("Error en procesarCambio: " . $e->getMessage(), "ERROR");

            error_log("Error en obtenerDetallesVenta: " . $e->getMessage());
            echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }

    // 🔹 Procesar cambio/devolución (nuevo método)
    public function procesarCambio()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        try {
            $datos = json_decode(file_get_contents('php://input'), true);

            if (empty($datos['id_venta_original']) || empty($datos['id_cliente'])) {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                return;
            }

            $resultado = $this->ventaModel->procesarCambioProducto($datos);
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // 🔹 Procesar devolución (nuevo método)
    public function procesarDevolucion()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }
write_log("Procesando devolución de producto", "INFO");



        try {
            $datos = json_decode(file_get_contents('php://input'), true);
                    write_log($datos, "DEVOLUCION_RECEIVED");

            if (empty($datos['id_venta_original']) || empty($datos['id_cliente'])) {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                return;
            }

            $resultado = $this->ventaModel->procesarDevolucion($datos);
            echo json_encode($resultado);
        } catch (Exception $e) {
            write_log("Error en procesarDevolucion: " . $e->getMessage(), "ERROR");

            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // 🔹 Obtener historial de cambios de cliente (nuevo método)
    public function historialCambiosCliente($id_cliente)
    {
        write_log("Historial de cambios solicitado para cliente $id_cliente", "INFO");

        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        try {
            $historial = $this->ventaModel->obtenerHistorialCambios($id_cliente);
            echo json_encode($historial);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }




    // 🔹 Ticket de venta
    public function ticket($id_venta = null)
    {

        write_log("Generando ticket para venta ID: $id_venta", "INFO");

        if (empty($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        if (empty($id_venta) || (int)$id_venta <= 0) {
            echo "Error: No se proporcionó un ID de venta válido.";
            return;
        }

        $id_venta = (int)$id_venta;
        $venta = $this->ventaModel->obtenerVentaPorId($id_venta);
        $detalles = $this->ventaModel->obtenerDetallesPorIdVenta($id_venta);

        if (!$venta) {
            echo "Error: No se encontró la venta con ID {$id_venta}.";
            write_log("Venta no encontrada ID: $id_venta", "WARNING");

            return;
        }

        // 🔹 Procesar sellos solo si existe cliente válido
        if (!empty($venta['id_cliente'])) {
            $resultadoSellos = $this->clienteModel->procesarSellosVenta($venta['id_cliente'], 0);
            $sellosActualesPostVenta = $resultadoSellos['sellos_restantes'] ?? 0;
            write_log("Procesando sellos para venta $id_venta", "SELLO");

            $sellosNuevos = 0;
            foreach ($detalles as $item) {
                if (($item['tipo_item'] ?? '') === 'producto') {
                    $sellosNuevos = 1;
                    break; // solo un sello por ticket
                }
            }

            $sellosAntes = $sellosActualesPostVenta;
            $totalSellosParaMostrar = $sellosActualesPostVenta;
            $mensajeEspecial = '';

            if ($sellosNuevos > 0) {
                if ($venta['descuento_sellos'] == 0.10) {
                    $sellosAntes = 11;
                    $totalSellosParaMostrar = 12;
                    $mensajeEspecial = '¡Felicidades! Completó 12 sellos y obtuvo 10% de descuento. Tarjeta reiniciada.';
                } elseif ($venta['descuento_sellos'] == 0.05) {
                    $sellosAntes = 5;
                    $totalSellosParaMostrar = 6;
                    $mensajeEspecial = '¡Felicidades! Completó 6 sellos y obtuvo 5% de descuento.';
                } else {
                    $sellosAntes = max(0, $sellosActualesPostVenta - 1);
                    $totalSellosParaMostrar = $sellosActualesPostVenta;
                    if ($totalSellosParaMostrar > 12) $totalSellosParaMostrar = 12;
                }
            }

            $venta['sellos_antes'] = $sellosAntes;
            $venta['sellos_nuevos'] = $sellosNuevos;
            $venta['sellos_post'] = $sellosActualesPostVenta;
            $venta['total_sellos_para_mostrar'] = $totalSellosParaMostrar;
            $venta['mensaje_sellos'] = $mensajeEspecial ?: 'Sigue acumulando: 1 sello por compra. ¡Próximos descuentos en 6 y 12 sellos!';
        } else {
            $venta['sellos_antes'] = 0;
            $venta['sellos_nuevos'] = 0;
            $venta['sellos_post'] = 0;
            $venta['total_sellos_para_mostrar'] = 0;
            $venta['mensaje_sellos'] = '';
        }

        require_once __DIR__ . '/../views/ventas/ticket.php';
    }

    // 🔹 Buscar productos y servicios activos
    public function buscar()
    {
        

        $term = $_GET['term'] ?? '';
        write_log("Buscando items con término: $term", "BUSQUEDA");
        $resultado = $this->ventaModel->buscarItemsActivos($term);

        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }

    public function guardar()
    {

        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }
        write_log("Solicitud para guardar venta recibida", "INFO");

        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            error_log("DEBUG VentaController::guardar datos: " . json_encode($datos));
            write_log($datos, "VENTA_RECEIVED");

            if (!$datos || empty($datos['carrito'])) {
                echo json_encode(['success' => false, 'error' => 'Datos inválidos o carrito vacío']);
                return;
            }

            $carrito = $datos['carrito'];
            $id_cliente = $datos['id_cliente'] ?? null;
            $descuento_manual = isset($datos['descuento_manual']) ? (float)$datos['descuento_manual'] : 0;
            $metodo_pago = $datos['metodo_pago'] ?? 'efectivo';
            $observacion = $datos['observacion'] ?? '';
            $es_generico = empty($id_cliente);

            // Validar estructura de carrito
            foreach ($carrito as $item) {
                if (!isset($item['precio'], $item['cantidad'], $item['id'], $item['tipo'])) {
                    echo json_encode(['success' => false, 'error' => 'Estructura de carrito inválida']);
                    return;
                }
            }
            write_log("Guardando venta en el modelo", "INFO");

            // 🔹 Guardar venta
            $ventaGuardada = $this->ventaModel->guardarVenta([
                'id_cliente' => $id_cliente,
                'metodo_pago' => $metodo_pago,
                'carrito' => $carrito,
                'descuento_manual' => $descuento_manual,
                'observacion' => $observacion
            ], $this->clienteModel);
            write_log("Venta guardada con ID " . $ventaGuardada['id_venta'], "VENTA_OK");

            // 🔹 Incrementar total de compras solo si hay cliente
            if (!$es_generico && !empty($id_cliente)) {
                $this->clienteModel->incrementarTotalCompras($id_cliente);
            }

            echo json_encode([
                'success' => true,
                'id_venta' => $ventaGuardada['id_venta'],
                'venta' => $ventaGuardada,
                'mensaje' => 'Venta registrada exitosamente'
            ]);
        } catch (Exception $e) {
            write_log("Error en VentaController::guardar: " . $e->getMessage(), "ERROR");

            error_log("Error en VentaController::guardar: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
