<?php
class TipoMoneda {
    private $db;
    public function __construct($conexion) { $this->db = $conexion; }

    public function obtenerTodos() {
        return $this->db->query("SELECT * FROM tipo_moneda ORDER BY descripcion ASC");
    }

    public function obtenerActivos() {
        return $this->db->query("SELECT * FROM tipo_moneda WHERE activo = 1 ORDER BY descripcion ASC");
    }
    
    public function guardar($datos) {
        $id = $datos['id'] ?? null;
        $descripcion = trim($datos['descripcion']);
        $simbolo = trim($datos['simbolo']);
        
        if ($id) { // Actualizar
            $stmt = $this->db->prepare("UPDATE tipo_moneda SET descripcion = ?, simbolo = ? WHERE id = ?");
            $stmt->bind_param("ssi", $descripcion, $simbolo, $id);
        } else { // Crear
            $stmt = $this->db->prepare("INSERT INTO tipo_moneda (descripcion, simbolo) VALUES (?, ?)");
            $stmt->bind_param("ss", $descripcion, $simbolo);
        }
        return $stmt->execute();
    }
    
    public function cambiarEstado($id, $estado) {
        $stmt = $this->db->prepare("UPDATE tipo_moneda SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }
}