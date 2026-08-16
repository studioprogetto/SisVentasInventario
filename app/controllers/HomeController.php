<?php
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/../models/Producto.php';

class HomeController
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    public function index()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // --- Paneles principales ---
        $total_ventas = $this->db
            ->query("SELECT COUNT(id_venta) as total FROM ventas WHERE estado = 'completada'")
            ->fetch_assoc()['total'] ?? 0;

        $total_compras = $this->db
            ->query("SELECT COUNT(id_compra) as total FROM compras WHERE estado = 'recibida'")
            ->fetch_assoc()['total'] ?? 0;

        $total_productos = $this->db
            ->query("SELECT COUNT(id_producto) as total FROM productos WHERE activo = 1")
            ->fetch_assoc()['total'] ?? 0;

        $productos_bajos_res = $this->db
            ->query("SELECT COUNT(id_producto) as total FROM productos WHERE activo = 1 AND stock <= stock_minimo");
        $total_productos_bajos = $productos_bajos_res ? $productos_bajos_res->fetch_assoc()['total'] : 0;

        // --- 🔹 CORREGIDO: Ventas de los últimos 5 días ---
        $ventas_ultimos_5_dias = $this->obtenerVentasUltimos5Dias();

        $labels_json = json_encode(array_keys($ventas_ultimos_5_dias));
        $data_json = json_encode(array_values($ventas_ultimos_5_dias));

        // --- Movimientos de inventario recientes ---
        $movimientos_inventario = $this->obtenerMovimientosRecientes();

        // --- Cargar vistas ---
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/home/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // 🔹 CORREGIDO: Método completamente revisado
    private function obtenerVentasUltimos5Dias()
    {
        // 🔹 CORREGIDO: Consulta que busca ventas de cualquier fecha (sin restricción de año)
        $sql_ventas_dias = "
            SELECT 
                DATE(fecha_venta) as dia,
                COALESCE(SUM(total_venta), 0) as total
            FROM ventas 
            WHERE estado = 'completada'
            GROUP BY DATE(fecha_venta)
            ORDER BY dia DESC
            LIMIT 5
        ";

        error_log("Consulta SQL Ventas: " . $sql_ventas_dias);

        $resultado_ventas = $this->db->query($sql_ventas_dias);
        
        // 🔹 INICIAL: Obtener las últimas 5 fechas con ventas
        $ventas_por_dia = [];
        
        if ($resultado_ventas && $resultado_ventas->num_rows > 0) {
            while ($fila = $resultado_ventas->fetch_assoc()) {
                $fecha = $fila['dia'];
                $total = (float)$fila['total'];
                $ventas_por_dia[$fecha] = $total;
                
                error_log("Venta encontrada - Fecha: $fecha, Total: $total");
            }
        } else {
            error_log("No se encontraron ventas en la consulta");
        }

        // 🔹 CORREGIDO: Ordenar por fecha más reciente y limitar a 5 días
        krsort($ventas_por_dia); // Ordenar de más reciente a más antigua
        $ventas_por_dia = array_slice($ventas_por_dia, 0, 5, true); // Tomar solo las 5 más recientes
        ksort($ventas_por_dia); // Ordenar de más antigua a más reciente para el gráfico

        error_log("Resultado final ordenado: " . json_encode($ventas_por_dia));

        return $ventas_por_dia;
    }

    // 🔹 NUEVO: Método alternativo que muestra los últimos 5 días con ventas (sin importar fechas específicas)
    private function obtenerUltimos5DiasConVentas()
    {
        $sql = "
            SELECT 
                DATE(fecha_venta) as dia,
                SUM(total_venta) as total,
                COUNT(*) as cantidad_ventas
            FROM ventas 
            WHERE estado = 'completada'
            GROUP BY DATE(fecha_venta)
            ORDER BY dia DESC
            LIMIT 5
        ";

        $resultado = $this->db->query($sql);
        $ventas_por_dia = [];

        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $ventas_por_dia[$fila['dia']] = [
                    'total' => (float)$fila['total'],
                    'cantidad' => (int)$fila['cantidad_ventas']
                ];
            }
        }

        // Ordenar por fecha (más antigua primero)
        ksort($ventas_por_dia);

        return $ventas_por_dia;
    }

    // 🔹 NUEVO: Método para diagnóstico completo
    public function diagnosticoVentas()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        try {
            // Obtener las últimas 10 ventas para diagnóstico
            $sql_ultimas_ventas = "
                SELECT 
                    id_venta,
                    DATE(fecha_venta) as dia,
                    fecha_venta,
                    total_venta,
                    estado,
                    metodo_pago
                FROM ventas 
                ORDER BY fecha_venta DESC
                LIMIT 10
            ";

            $resultado_ventas = $this->db->query($sql_ultimas_ventas);
            $ultimas_ventas = [];

            if ($resultado_ventas && $resultado_ventas->num_rows > 0) {
                while ($fila = $resultado_ventas->fetch_assoc()) {
                    $ultimas_ventas[] = $fila;
                }
            }

            // Obtener ventas agrupadas por día de los últimos 7 días
            $sql_agrupadas = "
                SELECT 
                    DATE(fecha_venta) as dia,
                    COUNT(*) as cantidad,
                    SUM(total_venta) as total,
                    estado
                FROM ventas 
                WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(fecha_venta), estado
                ORDER BY dia DESC
            ";

            $resultado_agrupadas = $this->db->query($sql_agrupadas);
            $ventas_agrupadas = [];

            if ($resultado_agrupadas && $resultado_agrupadas->num_rows > 0) {
                while ($fila = $resultado_agrupadas->fetch_assoc()) {
                    $ventas_agrupadas[] = $fila;
                }
            }

            echo json_encode([
                'success' => true,
                'fecha_actual' => date('Y-m-d H:i:s'),
                'fecha_actual_simple' => date('Y-m-d'),
                'ultimas_ventas' => $ultimas_ventas,
                'ventas_agrupadas' => $ventas_agrupadas,
                'ventas_ultimos_5_dias' => $this->obtenerVentasUltimos5Dias(),
                'consulta_5_dias' => "SELECT DATE(fecha_venta) as dia, SUM(total_venta) as total FROM ventas WHERE estado = 'completada' GROUP BY DATE(fecha_venta) ORDER BY dia DESC LIMIT 5"
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // 🔹 NUEVO: Método para obtener datos actualizados del dashboard
    public function obtenerDatosActualizados()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        try {
            $ventas_ultimos_5_dias = $this->obtenerVentasUltimos5Dias();
            
            // Total de ventas actual
            $total_ventas = $this->db
                ->query("SELECT COUNT(id_venta) as total FROM ventas WHERE estado = 'completada'")
                ->fetch_assoc()['total'] ?? 0;

            echo json_encode([
                'success' => true,
                'labels' => array_keys($ventas_ultimos_5_dias),
                'ventas' => array_values($ventas_ultimos_5_dias),
                'totalVentas' => $total_ventas,
                'fecha_actualizacion' => date('H:i:s')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function obtenerMovimientosRecientes()
    {
        $sql = "
            SELECT 
                mi.*,
                p.nombre as producto_nombre,
                a.nombre as almacen_nombre,
                u.nombre_completo as usuario_nombre,
                DATE_FORMAT(mi.fecha, '%d/%m/%Y %H:%i') as fecha_formateada
            FROM movimientos_inventario mi
            LEFT JOIN productos p ON mi.id_producto = p.id_producto
            LEFT JOIN almacenes a ON mi.id_almacen = a.id
            LEFT JOIN usuarios u ON mi.id_usuario = u.id_usuario
            ORDER BY mi.fecha DESC
            LIMIT 10
        ";

        $result = $this->db->query($sql);
        $movimientos = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $movimientos[] = $row;
            }
        }

        return $movimientos;
    }

    private function getBadgeClass($tipoMovimiento)
    {
        $classes = [
            'compra' => 'bg-success',
            'venta' => 'bg-primary',
            'devolucion' => 'bg-warning',
            'ajuste_positivo' => 'bg-info',
            'ajuste_negativo' => 'bg-danger',
            'transferencia_salida' => 'bg-secondary',
            'transferencia_entrada' => 'bg-dark'
        ];
        
        return $classes[$tipoMovimiento] ?? 'bg-secondary';
    }

    private function getTipoMovimientoTexto($tipoMovimiento)
    {
        $textos = [
            'compra' => 'Compra',
            'venta' => 'Venta',
            'devolucion' => 'Devolución',
            'ajuste_positivo' => 'Ajuste +',
            'ajuste_negativo' => 'Ajuste -',
            'transferencia_salida' => 'Transferencia',
            'transferencia_entrada' => 'Transferencia'
        ];
        
        return $textos[$tipoMovimiento] ?? $tipoMovimiento;
    }
}