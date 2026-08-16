<?php
class DetalleServicio
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Obtener todos los detalles de un servicio
     */
    public function obtenerPorServicio($id_servicio)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM detalle_servicio WHERE id_servicio = ? ORDER BY fecha DESC"
        );
        if (!$stmt) {
            die("Error en prepare: " . $this->db->error);
        }

        $stmt->bind_param("i", $id_servicio);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
