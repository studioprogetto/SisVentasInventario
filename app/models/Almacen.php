<?php
class Almacen
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    public function obtenerTodos()
    {
        $result = $this->db->query("SELECT * FROM almacenes WHERE activo = 1 ORDER BY nombre ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM almacenes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function guardar($datos)
    {
        $id = $datos['id'] ?? null;
        $nombre = trim($datos['nombre']);
        $ubicacion = trim($datos['ubicacion'] ?? '');

        if ($id) {
            $stmt = $this->db->prepare("UPDATE almacenes SET nombre = ?, ubicacion = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $ubicacion, $id);
        } else {
            $stmt = $this->db->prepare("INSERT INTO almacenes (nombre, ubicacion) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $ubicacion);
        }
        return $stmt->execute();
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare("UPDATE almacenes SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }

    public function obtenerTodosConDebug()
    {
        error_log("Ejecutando obtenerTodos() en modelo Almacen");

        $result = $this->db->query("SELECT * FROM almacenes WHERE activo = 1 ORDER BY nombre ASC");

        if ($result === false) {
            error_log("Error en query: " . $this->db->error);
            return [];
        }

        $almacenes = $result->fetch_all(MYSQLI_ASSOC);
        error_log("Almacenes encontrados: " . count($almacenes));

        return $almacenes;
    }
}
