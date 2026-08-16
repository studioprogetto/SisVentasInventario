<?php
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/CambioModel.php';
require_once __DIR__ . '/../models/DetalleCambioModel.php';

class DevolucionesController
{
    private $ventaModel;
    private $clienteModel;
    private $cambioModel;
    private $detalleCambioModel;

    public function __construct()
    {
        global $conexion;
        $this->ventaModel = new Venta($conexion);
        $this->clienteModel = new Cliente($conexion);
        $this->cambioModel = new CambioModel($conexion);
        $this->detalleCambioModel = new DetalleCambioModel($conexion);
    }

    // 🔹 Vista principal de cambios y devoluciones
    public function index()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Obtener ventas recientes para facilitar la búsqueda
        $ventas = $this->ventaModel->obtenerTodasConCambios();

        // Obtener estadísticas de cambios/devoluciones
        $estadisticas = $this->obtenerEstadisticasCambios();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/devoluciones/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }


    // 🔹 Historial completo de una venta específica
    public function historialCompletoVenta($idVenta)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        try {
            // Validar que la venta existe
            $venta = $this->ventaModel->obtenerVentaPorId($idVenta);

            if (!$venta) {
                echo json_encode(['error' => 'Venta no encontrada']);
                return;
            }

            // Obtener cambios/devoluciones de esta venta
            $historial = $this->cambioModel->obtenerHistorialPorVenta($idVenta);

            // Formatear los datos para la respuesta
            $historialFormateado = [];
            foreach ($historial as $cambio) {
                $historialFormateado[] = [
                    'id_cambio' => $cambio['id_cambio'],
                    'fecha_cambio' => $cambio['fecha_cambio'],
                    'tipo' => $cambio['tipo'],
                    'monto_saldo' => $cambio['monto_saldo'],
                    'estado' => $cambio['estado'],
                    'observacion' => $cambio['observacion'],
                    'productos_devueltos' => $this->formatearProductosDevueltos($cambio['id_cambio']),
                    'productos_nuevos' => $this->formatearProductosNuevos($cambio['id_cambio']),
                    'venta_nueva' => $cambio['id_venta_nueva']
                ];
            }

            echo json_encode($historialFormateado);
        } catch (Exception $e) {
            error_log("Error en historialCompletoVenta: " . $e->getMessage());
            echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }

    // En DevolucionesController.php - AGREGAR este método
    public function historialCliente($idCliente)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        try {
            // Usar el método del modelo de ventas que ya tienes
            $historial = $this->ventaModel->obtenerHistorialCambios($idCliente);

            if (empty($historial)) {
                echo json_encode([]);
                return;
            }

            echo json_encode($historial);
        } catch (Exception $e) {
            error_log("Error en historialCliente: " . $e->getMessage());
            echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }
    
    // 🔹 Historial simplificado para el modal
    public function historialVenta($idVenta)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        try {
            $cambios = $this->cambioModel->obtenerCambiosPorVenta($idVenta);
            echo json_encode($cambios);
        } catch (Exception $e) {
            error_log("Error en historialVenta: " . $e->getMessage());
            echo json_encode(['error' => 'Error interno del servidor']);
        }
    }



    // 🔹 Buscar ventas para cambios/devoluciones
    public function buscarVentas()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $termino = $_GET['term'] ?? '';

        try {
            global $conexion;

            // Consulta mejorada que incluye información de cambios
            $sql = "SELECT v.id_venta, v.fecha_venta, v.total_venta, 
                           v.id_cliente,
                           c.nombre_cliente, c.documento_identidad,
                           u.nombre_completo as cajero,
                           EXISTS(
                               SELECT 1 FROM cambios_ventas cv 
                               WHERE cv.id_venta_original = v.id_venta 
                               AND cv.estado = 'completado'
                           ) as tiene_cambios
                    FROM ventas v
                    LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                    JOIN usuarios u ON v.id_usuario = u.id_usuario
                    WHERE v.estado = 'completada'
                    AND (v.id_venta LIKE ? OR c.nombre_cliente LIKE ? OR c.documento_identidad LIKE ?)
                    ORDER BY v.fecha_venta DESC
                    LIMIT 10";

            $stmt = $conexion->prepare($sql);
            $terminoLike = "%$termino%";
            $stmt->bind_param("sss", $terminoLike, $terminoLike, $terminoLike);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // 🔹 Función auxiliar para respuestas JSON
    private function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function obtenerEstadisticasCambios()
    {
        global $conexion;
        $sql = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    SUM(monto_saldo) as total_monto
                FROM cambios_ventas 
                WHERE estado = 'completado'
                GROUP BY tipo";

        $res = $conexion->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function formatearProductosDevueltos($idCambio)
    {
        try {
            $productos = $this->detalleCambioModel->obtenerProductosDevueltos($idCambio);

            $productosFormateados = [];
            foreach ($productos as $producto) {
                $productosFormateados[] = $producto['nombre'] . ' (x' . $producto['cantidad'] . ')';
            }

            return empty($productosFormateados) ? 'No especificado' : implode(', ', $productosFormateados);
        } catch (Exception $e) {
            error_log("Error en formatearProductosDevueltos: " . $e->getMessage());
            return 'Error al cargar productos';
        }
    }

    private function formatearProductosNuevos($idCambio)
    {
        try {
            $productos = $this->detalleCambioModel->obtenerProductosNuevos($idCambio);

            $productosFormateados = [];
            foreach ($productos as $producto) {
                $productosFormateados[] = $producto['nombre'] . ' (x' . $producto['cantidad'] . ')';
            }

            return empty($productosFormateados) ? 'No aplica' : implode(', ', $productosFormateados);
        } catch (Exception $e) {
            error_log("Error en formatearProductosNuevos: " . $e->getMessage());
            return 'Error al cargar productos';
        }
    }
}
