<?php
class Proveedor {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function obtenerTodos($activo = 1) {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE activo = ? ORDER BY nombre_proveedor ASC");
        $stmt->bind_param("i", $activo);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function guardar($datos) {
        $id = $datos['id_proveedor'] ?? null;
        $nombre = trim($datos['nombre_proveedor']);
        $ruc = trim($datos['ruc']);
        $telefono = trim($datos['telefono']);
        $email = trim($datos['email']);
        $direccion = trim($datos['direccion']);

        if ($id) { // Actualizar
            $stmt = $this->db->prepare("UPDATE proveedores SET nombre_proveedor = ?, ruc = ?, telefono = ?, email = ?, direccion = ? WHERE id_proveedor = ?");
            $stmt->bind_param("sssssi", $nombre, $ruc, $telefono, $email, $direccion, $id);
        } else { // Crear
            $stmt = $this->db->prepare("INSERT INTO proveedores (nombre_proveedor, ruc, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $ruc, $telefono, $email, $direccion);
        }
        return $stmt->execute();
    }

    public function cambiarEstado($id, $estado) {
        $stmt = $this->db->prepare("UPDATE proveedores SET activo = ? WHERE id_proveedor = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }

      /**
     * Obtener todos los proveedores activos
     */
    public function obtenerProveedoresActivos()
    {
        $sql = "SELECT id_proveedor, nombre_proveedor, ruc, telefono, email, direccion 
                FROM proveedores 
                WHERE activo = 1 
                ORDER BY nombre_proveedor ASC";
        
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener proveedor por ID
     */
    public function obtenerPorId($id_proveedor)
    {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
        $stmt->bind_param("i", $id_proveedor);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    
}