<?php
require_once __DIR__ . '/Model.php';

class DetalleCambioModel extends Model
{
    protected $table = 'detalles_cambio';
    
    public function __construct($conexion = null)
    {
        parent::__construct($conexion);
    }
    
    public function obtenerProductosDevueltos($idCambio)
    {
        $sql = "SELECT p.nombre, dc.cantidad 
                FROM detalles_cambio dc
                JOIN productos p ON dc.id_producto = p.id_producto
                WHERE dc.id_cambio = ? AND dc.tipo = 'devolucion'";
        
        return $this->query($sql, [$idCambio]);
    }
    
    public function obtenerProductosNuevos($idCambio)
    {
        $sql = "SELECT p.nombre, dc.cantidad 
                FROM detalles_cambio dc
                JOIN productos p ON dc.id_producto = p.id_producto
                WHERE dc.id_cambio = ? AND dc.tipo = 'nuevo'";
        
        return $this->query($sql, [$idCambio]);
    }

    // 🔹 Registrar detalles del cambio
    public function registrarDetalle($datos)
    {
        $sql = "INSERT INTO detalles_cambio 
                (id_cambio, id_producto, cantidad, precio, tipo) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "iiids", 
            $datos['id_cambio'],
            $datos['id_producto'],
            $datos['cantidad'],
            $datos['precio'],
            $datos['tipo']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    // 🔹 Registrar múltiples detalles
    public function registrarMultiplesDetalles($detalles)
    {
        foreach ($detalles as $detalle) {
            if (!$this->registrarDetalle($detalle)) {
                return false;
            }
        }
        return true;
    }
}