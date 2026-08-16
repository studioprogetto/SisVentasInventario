<?php
class ReporteModel {
    private $db;

 public function __construct($conexion)
    {
        $this->db = $conexion;
    }


    // 🔹 MÉTODOS DE PAGO
    public function obtenerDistribucionMetodosPago($fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT 
                    metodo_pago,
                    COUNT(*) as total_ventas,
                    SUM(total_venta) as monto_total,
                    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ventas WHERE estado = 'completado')), 2) as porcentaje
                FROM ventas 
                WHERE estado = 'completado'";
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND DATE(fecha_venta) BETWEEN ? AND ?";
            $params = [$fechaInicio, $fechaFin];
        } else {
            $params = [];
        }
        
        $sql .= " GROUP BY metodo_pago 
                  ORDER BY monto_total DESC";

        return $this->db->query($sql, $params);
    }

    public function obtenerVentasPorMetodoPago($rangoDias = 30) {
        $sql = "SELECT 
                    metodo_pago,
                    DATE(fecha_venta) as fecha,
                    SUM(total_venta) as venta_diaria
                FROM ventas 
                WHERE estado = 'completado' 
                    AND fecha_venta >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY metodo_pago, DATE(fecha_venta)
                ORDER BY fecha DESC, metodo_pago";

        return $this->db->query($sql, [$rangoDias]);
    }

    // 🔹 PRODUCTOS MÁS VENDIDOS
    public function obtenerTopProductosPorCantidad($limite = 10, $rangoDias = 30) {
        $sql = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.descripcion,
                    p.precio_venta,
                    SUM(dv.cantidad) as total_vendido,
                    SUM(dv.cantidad * dv.precio_unitario) as revenue_total
                FROM detalle_venta dv
                JOIN productos p ON dv.id_producto = p.id_producto
                JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE v.estado = 'completado'
                    AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY p.id_producto, p.nombre, p.descripcion, p.precio_venta
                ORDER BY total_vendido DESC
                LIMIT ?";

        return $this->db->query($sql, [$rangoDias, $limite]);
    }

    public function obtenerTopProductosPorRevenue($limite = 10, $rangoDias = 30) {
        $sql = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.descripcion,
                    p.precio_venta,
                    p.precio_compra,
                    SUM(dv.cantidad) as total_vendido,
                    SUM(dv.cantidad * dv.precio_unitario) as revenue_total,
                    SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) as ganancia_total
                FROM detalle_venta dv
                JOIN productos p ON dv.id_producto = p.id_producto
                JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE v.estado = 'completado'
                    AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY p.id_producto, p.nombre, p.descripcion, p.precio_venta, p.precio_compra
                ORDER BY revenue_total DESC
                LIMIT ?";

        return $this->db->query($sql, [$rangoDias, $limite]);
    }

    public function obtenerProductosBajaRotacion($limite = 10) {
        $sql = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.stock,
                    p.precio_compra,
                    p.precio_venta,
                    COALESCE(SUM(dv.cantidad), 0) as total_vendido_mes,
                    DATEDIFF(NOW(), MAX(v.fecha_venta)) as dias_ultima_venta
                FROM productos p
                LEFT JOIN detalle_venta dv ON p.id_producto = dv.id_producto
                LEFT JOIN ventas v ON dv.id_venta = v.id_venta AND v.estado = 'completado'
                WHERE p.activo = 1
                GROUP BY p.id_producto, p.nombre, p.stock, p.precio_compra, p.precio_venta
                HAVING total_vendido_mes <= 5 OR dias_ultima_venta > 30 OR dias_ultima_venta IS NULL
                ORDER BY total_vendido_mes ASC, dias_ultima_venta DESC
                LIMIT ?";

        return $this->db->query($sql, [$limite]);
    }

    // 🔹 ANÁLISIS DE RENTABILIDAD
    public function obtenerMargenesProductos() {
        $sql = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.precio_compra,
                    p.precio_venta,
                    ROUND(((p.precio_venta - p.precio_compra) / p.precio_venta * 100), 2) as margen_porcentaje,
                    (p.precio_venta - p.precio_compra) as margen_absoluto,
                    COALESCE(SUM(dv.cantidad), 0) as total_vendido,
                    COALESCE(SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)), 0) as ganancia_total
                FROM productos p
                LEFT JOIN detalle_venta dv ON p.id_producto = dv.id_producto
                LEFT JOIN ventas v ON dv.id_venta = v.id_venta AND v.estado = 'completado'
                WHERE p.activo = 1
                GROUP BY p.id_producto, p.nombre, p.precio_compra, p.precio_venta
                HAVING margen_porcentaje IS NOT NULL
                ORDER BY ganancia_total DESC";

        return $this->db->query($sql);
    }

    public function obtenerRentabilidadGeneral($rangoDias = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(v.total_venta) as ingresos_totales,
                    SUM(dv.cantidad * p.precio_compra) as costos_totales,
                    SUM(v.total_venta) - SUM(dv.cantidad * p.precio_compra) as ganancia_neta,
                    ROUND(((SUM(v.total_venta) - SUM(dv.cantidad * p.precio_compra)) / SUM(v.total_venta) * 100), 2) as margen_global
                FROM ventas v
                JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                JOIN productos p ON dv.id_producto = p.id_producto
                WHERE v.estado = 'completado'
                    AND v.fecha_venta >= DATE_SUB(NOW(), INTERVAL ? DAY)";

        return $this->db->query($sql, [$rangoDias]);
    }

    public function obtenerProductosMasRentables($limite = 10) {
        $sql = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.precio_compra,
                    p.precio_venta,
                    ROUND(((p.precio_venta - p.precio_compra) / p.precio_venta * 100), 2) as margen_porcentaje,
                    COALESCE(SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)), 0) as ganancia_total,
                    COALESCE(SUM(dv.cantidad), 0) as total_vendido
                FROM productos p
                LEFT JOIN detalle_venta dv ON p.id_producto = dv.id_producto
                LEFT JOIN ventas v ON dv.id_venta = v.id_venta AND v.estado = 'completado'
                WHERE p.activo = 1 AND p.precio_compra > 0
                GROUP BY p.id_producto, p.nombre, p.precio_compra, p.precio_venta
                HAVING ganancia_total > 0
                ORDER BY ganancia_total DESC
                LIMIT ?";

        return $this->db->query($sql, [$limite]);
    }

    // 🔹 MÉTODOS AUXILIARES PARA FILTROS
    public function obtenerVentasPorPeriodo($fechaInicio, $fechaFin) {
        $sql = "SELECT 
                    DATE(fecha_venta) as fecha,
                    COUNT(*) as ventas_dia,
                    SUM(total_venta) as ingresos_dia
                FROM ventas 
                WHERE estado = 'completado'
                    AND DATE(fecha_venta) BETWEEN ? AND ?
                GROUP BY DATE(fecha_venta)
                ORDER BY fecha";

        return $this->db->query($sql, [$fechaInicio, $fechaFin]);
    }
}
?>