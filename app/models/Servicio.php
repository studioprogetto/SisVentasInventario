<?php
class Servicio
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Obtener todos los servicios activos con búsqueda opcional
     */
    public function obtenerTodos($activo = 1, $buscar = '')
    {
        $buscar = trim($buscar);
        $sql = "SELECT id_servicio, nombre, descripcion, precio_venta, activo 
                FROM servicios WHERE activo = ?";
        $params = [$activo];
        $types = "i";

        if (!empty($buscar)) {
            $palabras = explode(" ", $this->normalizarTexto($buscar));
            foreach ($palabras as $palabra) {
                $sql .= " AND (LOWER(nombre) LIKE ? OR LOWER(descripcion) LIKE ?)";
                $like = "%" . $palabra . "%";
                $params[] = $like;
                $params[] = $like;
                $types .= "ss";
            }
        }

        $sql .= " ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) die("Error en prepare: " . $this->db->error);

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        // 🔹 búsqueda difusa si no hay resultados
        if ($result->num_rows === 0 && !empty($buscar)) {
            $todos = $this->db->query(
                "SELECT id_servicio, nombre, descripcion, precio_venta, activo FROM servicios WHERE activo = $activo"
            )->fetch_all(MYSQLI_ASSOC);

            $filtrados = [];
            $busquedaNorm = $this->normalizarTexto($buscar);

            foreach ($todos as $servicio) {
                $nombreNorm = $this->normalizarTexto($servicio['nombre']);
                $descNorm   = $this->normalizarTexto($servicio['descripcion']);
                $umbral = max(strlen($busquedaNorm), strlen($nombreNorm)) / 3;

                if (
                    levenshtein($busquedaNorm, $nombreNorm) <= $umbral ||
                    levenshtein($busquedaNorm, $descNorm) <= $umbral
                ) {
                    $filtrados[] = $servicio;
                }
            }

            return $filtrados; // devuelve arreglo simple
        }

        return $result; // devuelve mysqli_result
    }

    /**
     * Normaliza un texto (minúsculas, sin acentos, solo alfanuméricos)
     */
    private function normalizarTexto($texto)
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $acentos = [
            'á',
            'é',
            'í',
            'ó',
            'ú',
            'ä',
            'ë',
            'ï',
            'ö',
            'ü',
            'à',
            'è',
            'ì',
            'ò',
            'ù',
            'ñ',
            'Á',
            'É',
            'Í',
            'Ó',
            'Ú',
            'Ä',
            'Ë',
            'Ï',
            'Ö',
            'Ü',
            'À',
            'È',
            'Ì',
            'Ò',
            'Ù',
            'Ñ'
        ];
        $sinAcentos = [
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'n',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'n'
        ];
        $texto = str_replace($acentos, $sinAcentos, $texto);
        $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }

    /**
     * Guardar un servicio (nuevo o actualizar)
     */
    /**
     * Guardar un servicio (nuevo o actualizar)
     */
    public function guardar($data)
    {
        $id = $data['id_servicio'] ?? null;
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        // Tomar el precio desde el input 'precio' y validar
        $precio_venta = isset($data['precio']) && is_numeric($data['precio'])
            ? (float)$data['precio']
            : 0;

        if ($precio_venta <= 0) {
            throw new Exception("El precio del servicio debe ser mayor a 0.");
        }

        if ($id) {
            $stmt = $this->db->prepare(
                "UPDATE servicios SET nombre=?, descripcion=?, precio_venta=?, fecha_actualizacion=NOW() WHERE id_servicio=?"
            );
            $stmt->bind_param("ssdi", $nombre, $descripcion, $precio_venta, $id);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO servicios (nombre, descripcion, precio_venta, activo, fecha_creacion) VALUES (?, ?, ?, 1, NOW())"
            );
            $stmt->bind_param("ssd", $nombre, $descripcion, $precio_venta);
        }

        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $this->db->error);
        }

        return $stmt->execute();
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare(
            "UPDATE servicios SET activo=?, fecha_actualizacion=NOW() WHERE id_servicio=?"
        );
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }

    /**
     * Obtener servicio por ID
     */
    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare(
            "SELECT id_servicio, nombre, descripcion, precio_venta, activo FROM servicios WHERE id_servicio=?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
