<?php
class Model
{
    protected $db;
    protected $table;

    public function __construct($conexion = null)
    {
        global $conexion;
        $this->db = $conexion;
    }

    protected function query($sql, $params = [])
    {
        try {
            if (empty($params)) {
                $result = $this->db->query($sql);
                return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en preparación: " . $this->db->error);
            }

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();

            return $data;

        } catch (Exception $e) {
            error_log("Error en query: " . $e->getMessage());
            return [];
        }
    }
}