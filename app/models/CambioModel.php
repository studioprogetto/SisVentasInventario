<?php
require_once __DIR__ . '/Model.php';

class CambioModel extends Model
{
    protected $table = 'cambios_ventas';
    
    public function __construct($conexion = null)
    {
        parent::__construct($conexion);
    }
    
    // 🔹 Obtener historial por cliente
    public function obtenerHistorialPorCliente($idCliente)
    {
        $sql = "SELECT 
                    cv.id_cambio,
                    cv.id_venta_original,
                    cv.id_venta_nueva,
                    cv.tipo,
                    cv.monto_saldo,
                    cv.estado,
                    cv.observacion,
                    cv.fecha_cambio,
                    vo.id_venta as venta_original,
                    vn.id_venta as venta_nueva
                FROM cambios_ventas cv
                LEFT JOIN ventas vo ON cv.id_venta_original = vo.id_venta
                LEFT JOIN ventas vn ON cv.id_venta_nueva = vn.id_venta
                WHERE cv.id_cliente = ?
                ORDER BY cv.fecha_cambio DESC";

        return $this->query($sql, [$idCliente]);
    }
    
    // 🔹 Obtener historial por venta
    public function obtenerHistorialPorVenta($idVenta)
    {
        $sql = "SELECT * FROM cambios_ventas 
                WHERE id_venta_original = ? OR id_venta_nueva = ?
                ORDER BY fecha_cambio DESC";
                
        return $this->query($sql, [$idVenta, $idVenta]);
    }
    
    // 🔹 Obtener cambios por venta
    public function obtenerCambiosPorVenta($idVenta)
    {
        $sql = "SELECT * FROM cambios_ventas 
                WHERE id_venta_original = ? 
                ORDER BY fecha_cambio DESC";
                
        return $this->query($sql, [$idVenta]);
    }
    
    // 🔹 Crear nuevo cambio
    public function crearCambio($datos)
    {
        $sql = "INSERT INTO cambios_ventas 
                (id_venta_original, id_cliente, tipo, monto_saldo, observacion, estado) 
                VALUES (?, ?, ?, ?, ?, 'completado')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "iisds", 
            $datos['id_venta_original'],
            $datos['id_cliente'],
            $datos['tipo'],
            $datos['monto_saldo'],
            $datos['observacion']
        );
        
        $result = $stmt->execute();
        $id_cambio = $this->db->insert_id;
        $stmt->close();
        
        return $result ? $id_cambio : false;
    }
    
    // 🔹 Actualizar cambio con nueva venta
    public function actualizarConNuevaVenta($idCambio, $idVentaNueva)
    {
        $sql = "UPDATE cambios_ventas SET id_venta_nueva = ? WHERE id_cambio = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $idVentaNueva, $idCambio);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}