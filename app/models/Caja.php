<?php
class Caja
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    // 🔹 Obtener turno abierto del usuario
    public function getTurnoAbierto($id_usuario)
    {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM turnos_caja 
            WHERE id_usuario = ? AND estado = 'abierto' 
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $turno = $res->fetch_assoc();
        $stmt->close();
        return $turno;
    }

    // 🔹 Abrir un nuevo turno
    public function abrirTurno($id_usuario, $monto_inicial)
    {
        if ($this->getTurnoAbierto($id_usuario)) {
            return false; // Ya tiene un turno abierto
        }

        $stmt = $this->db->prepare("
            INSERT INTO turnos_caja (id_usuario, monto_inicial, estado) 
            VALUES (?, ?, 'abierto')
        ");
        $stmt->bind_param("id", $id_usuario, $monto_inicial);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // 🔹 Registrar movimiento manual
    public function agregarMovimiento($id_turno, $id_usuario, $tipo, $monto, $descripcion)
    {
        $stmt = $this->db->prepare("
            INSERT INTO movimientos_caja (id_turno, id_usuario, tipo_movimiento, monto, descripcion) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisds", $id_turno, $id_usuario, $tipo, $monto, $descripcion);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // 🔹 Cerrar turno - CORREGIDO EL ERROR DE bind_param
    public function cerrarTurno($id_turno, $id_usuario, $monto_final_real)
    {
        $this->db->begin_transaction();
        try {
            // 🔒 Bloquear turno activo
            $stmt = $this->db->prepare("
                SELECT monto_inicial 
                FROM turnos_caja 
                WHERE id_turno = ? AND id_usuario = ? AND estado = 'abierto' 
                FOR UPDATE
            ");
            $stmt->bind_param("ii", $id_turno, $id_usuario);
            $stmt->execute();
            $turno = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$turno) {
                throw new Exception("No se encontró un turno abierto para este usuario.");
            }

            // ✅ CALCULAR MONTO FINAL CORRECTAMENTE
            $monto_final_sistema = (float)$turno['monto_inicial'];

            // 1. Sumar TODAS las ventas del turno (netas, después de descuentos)
            $stmt_ventas = $this->db->prepare("
                SELECT COALESCE(SUM(total_venta), 0) as total_ventas_netas
                FROM ventas 
                WHERE id_turno = ? AND estado = 'completada'
            ");
            $stmt_ventas->bind_param("i", $id_turno);
            $stmt_ventas->execute();
            $ventas_result = $stmt_ventas->get_result()->fetch_assoc();
            $monto_final_sistema += (float)($ventas_result['total_ventas_netas'] ?? 0);
            $stmt_ventas->close();

            // 2. Sumar movimientos manuales
            $stmt_movimientos = $this->db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' THEN monto ELSE 0 END), 0) as ingresos,
                    COALESCE(SUM(CASE WHEN tipo_movimiento = 'egreso' THEN monto ELSE 0 END), 0) as egresos
                FROM movimientos_caja 
                WHERE id_turno = ?
            ");
            $stmt_movimientos->bind_param("i", $id_turno);
            $stmt_movimientos->execute();
            $movimientos_result = $stmt_movimientos->get_result()->fetch_assoc();
            $monto_final_sistema += (float)($movimientos_result['ingresos'] ?? 0);
            $monto_final_sistema -= (float)($movimientos_result['egresos'] ?? 0);
            $stmt_movimientos->close();

            // 3. Restar devoluciones y cambios del día
            $stmt_devoluciones = $this->db->prepare("
                SELECT COALESCE(SUM(monto_saldo), 0) as total_devoluciones
                FROM cambios_ventas cv
                JOIN ventas v ON cv.id_venta_original = v.id_venta
                WHERE v.id_turno = ? 
                AND cv.tipo = 'devolucion' 
                AND DATE(cv.fecha_cambio) = CURDATE()
            ");
            $stmt_devoluciones->bind_param("i", $id_turno);
            $stmt_devoluciones->execute();
            $devoluciones_result = $stmt_devoluciones->get_result()->fetch_assoc();
            $monto_final_sistema -= (float)($devoluciones_result['total_devoluciones'] ?? 0);
            $stmt_devoluciones->close();

            $diferencia = $monto_final_real - $monto_final_sistema;

            // ✅ Obtener resumen para reporte con todos los métodos de pago
            require_once __DIR__ . '/Venta.php';
            $ventaModel = new Venta($this->db);
            $resumen = $ventaModel->getResumenVentasDetalladoPorTurno($id_turno);

            // 🔹 Normalizar valores - ASEGURAR QUE SEAN NUMÉRICOS
            $num_ventas      = (int)($resumen['num_ventas'] ?? 0);
            $total_ingresos  = (float)($resumen['total_ingresos'] ?? 0.0);
            $efectivo        = (float)($resumen['efectivo'] ?? 0.0);
            $yape            = (float)($resumen['yape'] ?? 0.0);
            $plin            = (float)($resumen['plin'] ?? 0.0);
            $agora           = (float)($resumen['agora'] ?? 0.0);
            $transferencia   = (float)($resumen['transferencia'] ?? 0.0);

            // ✅ CORREGIDO: Actualizar solo los campos necesarios
            $stmt = $this->db->prepare("
                UPDATE turnos_caja 
                SET fecha_cierre = NOW(),
                    monto_final_sistema = ?,
                    monto_final_real = ?,
                    diferencia = ?,
                    num_ventas = ?,
                    total_ingresos = ?,
                    efectivo = ?,
                    yape = ?,
                    plin = ?,
                    agora = ?,
                    transferencia = ?,
                    estado = 'cerrado'
                WHERE id_turno = ?
            ");
            
            if (!$stmt) {
                throw new Exception("Error preparando statement: " . $this->db->error);
            }

            // 🔹 CORRECCIÓN: 11 parámetros = 11 caracteres en la cadena de tipos
            $stmt->bind_param(
                "ddddddddddi", // 11 'd' para decimales/double y 1 'i' para integer
                $monto_final_sistema,    // d
                $monto_final_real,       // d
                $diferencia,             // d
                $num_ventas,             // d (se convierte a float para bind_param)
                $total_ingresos,         // d
                $efectivo,               // d
                $yape,                   // d
                $plin,                   // d
                $agora,                  // d
                $transferencia,          // d
                $id_turno                // i
            );
            
            // Convertir num_ventas a float para bind_param (aunque sea entero)
            $num_ventas_float = (float)$num_ventas;
            
            $stmt->bind_param(
                "ddddddddddi",
                $monto_final_sistema,
                $monto_final_real,
                $diferencia,
                $num_ventas_float,
                $total_ingresos,
                $efectivo,
                $yape,
                $plin,
                $agora,
                $transferencia,
                $id_turno
            );
            
            $execute_result = $stmt->execute();
            
            if (!$execute_result) {
                throw new Exception("Error ejecutando update: " . $stmt->error);
            }
            
            $stmt->close();

            $this->db->commit();

            return [
                'sistema' => $monto_final_sistema,
                'real' => $monto_final_real,
                'diferencia' => $diferencia,
                'resumen' => [
                    'num_ventas' => $num_ventas,
                    'total_ingresos' => $total_ingresos,
                    'efectivo' => $efectivo,
                    'yape' => $yape,
                    'plin' => $plin,
                    'agora' => $agora,
                    'transferencia' => $transferencia
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error al cerrar turno: " . $e->getMessage());
            return false;
        }
    }

    // 🔹 Obtener todos los turnos cerrados con detalles
    public function obtenerTurnosDetallados()
    {
        $sql = "
            SELECT 
                t.id_turno,
                u.nombre_completo AS usuario,
                t.fecha_apertura,
                t.monto_inicial,
                t.fecha_cierre,
                t.monto_final_sistema,
                t.monto_final_real,
                t.diferencia,
                t.num_ventas,
                t.total_ingresos,
                t.efectivo,
                t.yape,
                t.plin,
                t.agora,
                t.transferencia,
                t.total_bruto,
                t.total_neto,
                t.ganancia
            FROM turnos_caja t
            INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
            WHERE t.estado = 'cerrado'
            ORDER BY t.fecha_apertura DESC
        ";

        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Error preparando consulta: " . $this->db->error);
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $turnos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $turnos;
    }

    // 🔹 Obtener movimientos de un turno específico
    public function obtenerMovimientosTurno($id_turno)
    {
        $stmt = $this->db->prepare("
            SELECT 
                mc.*,
                u.nombre_completo as usuario
            FROM movimientos_caja mc
            JOIN usuarios u ON mc.id_usuario = u.id_usuario
            WHERE mc.id_turno = ?
            ORDER BY mc.fecha_movimiento DESC
        ");
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $result = $stmt->get_result();
        $movimientos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $movimientos;
    }

    // 🔹 Verificar si existe un turno abierto para cualquier usuario
    public function existeTurnoAbierto()
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM turnos_caja 
            WHERE estado = 'abierto'
        ");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($result['total'] ?? 0) > 0;
    }

    // 🔹 Obtener resumen rápido del turno actual
    public function getResumenTurnoActual($id_turno)
    {
        $sql = "
            SELECT 
                t.id_turno,
                t.monto_inicial,
                t.fecha_apertura,
                u.nombre_completo as usuario,
                COUNT(v.id_venta) as ventas_realizadas,
                COALESCE(SUM(v.total_venta), 0) as total_ventas,
                COALESCE(SUM(CASE WHEN v.metodo_pago = 'efectivo' THEN v.total_venta ELSE 0 END), 0) as efectivo,
                COALESCE(SUM(CASE WHEN v.metodo_pago = 'yape' THEN v.total_venta ELSE 0 END), 0) as yape,
                COALESCE(SUM(CASE WHEN v.metodo_pago = 'plin' THEN v.total_venta ELSE 0 END), 0) as plin,
                COALESCE(SUM(CASE WHEN v.metodo_pago = 'agora' THEN v.total_venta ELSE 0 END), 0) as agora,
                COALESCE(SUM(CASE WHEN v.metodo_pago = 'transferencia' THEN v.total_venta ELSE 0 END), 0) as transferencia
            FROM turnos_caja t
            JOIN usuarios u ON t.id_usuario = u.id_usuario
            LEFT JOIN ventas v ON t.id_turno = v.id_turno AND v.estado = 'completada'
            WHERE t.id_turno = ?
            GROUP BY t.id_turno
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ?: [];
    }
}