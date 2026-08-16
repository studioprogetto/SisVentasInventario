<?php
class Categoria {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function obtenerTodas($activo = 1) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE activo = ? ORDER BY nombre ASC");
        $stmt->bind_param("i", $activo);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function guardar($datos) {
        $nombre = $datos['nombre'];
        $id = $datos['id_categoria'] ?? null;

        if ($id) { // Actualizar
            $stmt = $this->db->prepare("UPDATE categorias SET nombre = ? WHERE id_categoria = ?");
            $stmt->bind_param("si", $nombre, $id);
        } else { // Crear
            $stmt = $this->db->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre);
        }
        return $stmt->execute();
    }

    public function cambiarEstado($id, $estado) {
        $stmt = $this->db->prepare("UPDATE categorias SET activo = ? WHERE id_categoria = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }
}