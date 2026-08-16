<?php
class TurnoCaja
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    // 🔹 Obtener turno activo del usuario
    public function obtenerTurnoActivo($id_usuario)
    {
        $stmt = $this->db->prepare("
            SELECT id_turno 
            FROM turnos_caja 
            WHERE id_usuario = ? AND estado = 'abierto' 
            ORDER BY fecha_apertura DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ? $result['id_turno'] : null;
    }

    // 🔹 Cerrar turno con datos reales
    public function cerrarTurno($id_turno, $datos_cierre)
    {
        $this->db->begin_transaction();
        try {
            // Obtener datos REALES de ventas para este turno
            $datos_reales = $this->obtenerDatosRealesVentasPorTurno($id_turno);

            $sql = "
                UPDATE turnos_caja SET 
                    fecha_cierre = NOW(),
                    monto_final_sistema = ?,
                    monto_final_real = ?,
                    diferencia = ?,
                    estado = 'cerrado',
                    num_ventas = ?,
                    total_ingresos = ?,
                    efectivo = ?,
                    yape = ?,
                    plin = ?,
                    agora = ?,
                    transferencia = ?,
                    total_bruto = ?,
                    total_neto = ?,
                    ganancia = ?
                WHERE id_turno = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "ddiddddddddddi",
                $datos_cierre['monto_final_sistema'],
                $datos_cierre['monto_final_real'],
                $datos_cierre['diferencia'],
                $datos_reales['num_ventas'],
                $datos_reales['total_ingresos'],
                $datos_reales['efectivo'],
                $datos_reales['yape'],
                $datos_reales['plin'],
                $datos_reales['agora'],
                $datos_reales['transferencia'],
                $datos_reales['total_bruto'],
                $datos_reales['total_neto'],
                $datos_reales['ganancia'],
                $id_turno
            );

            $result = $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // 🔹 Método para obtener datos reales de ventas por turno
    private function obtenerDatosRealesVentasPorTurno($id_turno)
    {
        // Misma lógica que en CajaController
        $sql_metodos_pago = "
            SELECT 
                COUNT(*) as num_ventas,
                COALESCE(SUM(total_venta), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total_venta ELSE 0 END), 0) as efectivo,
                COALESCE(SUM(CASE WHEN metodo_pago = 'yape' THEN total_venta ELSE 0 END), 0) as yape,
                COALESCE(SUM(CASE WHEN metodo_pago = 'plin' THEN total_venta ELSE 0 END), 0) as plin,
                COALESCE(SUM(CASE WHEN metodo_pago = 'agora' THEN total_venta ELSE 0 END), 0) as agora,
                COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN total_venta ELSE 0 END), 0) as transferencia
            FROM ventas 
            WHERE estado = 'completada' 
            AND id_turno = ?
        ";

        $stmt = $this->db->prepare($sql_metodos_pago);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $metodos_pago = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $sql_costos = "
            SELECT 
                COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) as total_bruto,
                COALESCE(SUM(dv.cantidad * COALESCE(p.precio_compra, 0)), 0) as total_neto,
                COALESCE(SUM((dv.cantidad * dv.precio_unitario) - (dv.cantidad * COALESCE(p.precio_compra, 0))), 0) as ganancia
            FROM ventas v
            INNER JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
            LEFT JOIN productos p ON dv.id_producto = p.id_producto
            WHERE v.estado = 'completada' 
            AND v.id_turno = ?
        ";

        $stmt = $this->db->prepare($sql_costos);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $costos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'num_ventas'      => (int)($metodos_pago['num_ventas'] ?? 0),
            'total_ingresos'  => (float)($metodos_pago['total_ingresos'] ?? 0),
            'efectivo'        => (float)($metodos_pago['efectivo'] ?? 0),
            'yape'            => (float)($metodos_pago['yape'] ?? 0),
            'plin'            => (float)($metodos_pago['plin'] ?? 0),
            'agora'           => (float)($metodos_pago['agora'] ?? 0),
            'transferencia'   => (float)($metodos_pago['transferencia'] ?? 0),
            'total_bruto'     => (float)($costos['total_bruto'] ?? 0),
            'total_neto'      => (float)($costos['total_neto'] ?? 0),
            'ganancia'        => (float)($costos['ganancia'] ?? 0)
        ];
    }
}