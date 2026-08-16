<?php
class TipoCambio {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function obtenerTodos() {
        $sql = "SELECT 
                    tc.fecha, 
                    tc.valor_soles, 
                    tm.descripcion as moneda_descripcion, 
                    tm.simbolo as moneda_simbolo
                FROM tipo_cambio tc
                JOIN tipo_moneda tm ON tc.id_moneda = tm.id
                ORDER BY tc.fecha DESC, tm.descripcion ASC";
        return $this->db->query($sql);
    }

    public function guardar($datos) {
        $fecha = $datos['fecha'];
        $id_moneda = (int)$datos['id_moneda'];
        $valor = (float)$datos['valor_soles'];

        // Se usa INSERT ... ON DUPLICATE KEY UPDATE para manejar la creación y edición a la vez.
        // Esto funciona gracias a la clave primaria compuesta (fecha, id_moneda).
        $stmt = $this->db->prepare("INSERT INTO tipo_cambio (fecha, id_moneda, valor_soles) VALUES (?, ?, ?)
                                    ON DUPLICATE KEY UPDATE valor_soles = VALUES(valor_soles)");
        $stmt->bind_param("sid", $fecha, $id_moneda, $valor);
        return $stmt->execute();
    }
}