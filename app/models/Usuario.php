<?php
class Usuario {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function obtenerTodos() {
        $sql = "SELECT u.id_usuario, u.nombre_completo, u.username, u.activo, u.id_rol, r.nombre_rol 
                FROM usuarios u 
                JOIN roles r ON u.id_rol = r.id_rol 
                ORDER BY u.nombre_completo ASC";
        return $this->db->query($sql);
    }

    public function obtenerRoles() {
        return $this->db->query("SELECT * FROM roles ORDER BY nombre_rol ASC");
    }

    public function buscarPorUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT nombre_completo, username, moneda FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function guardar($datos) {
        $id = $datos['id_usuario'] ?? null;
        $nombre = trim($datos['nombre_completo']);
        $username = trim($datos['username']);
        $id_rol = (int)$datos['id_rol'];
        $password = $datos['password'];

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($id) { // Actualizar con contraseña
                $stmt = $this->db->prepare("UPDATE usuarios SET nombre_completo = ?, username = ?, id_rol = ?, password = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssisi", $nombre, $username, $id_rol, $hash, $id);
            } else { // Crear usuario nuevo
                $stmt = $this->db->prepare("INSERT INTO usuarios (nombre_completo, username, id_rol, password) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $nombre, $username, $id_rol, $hash);
            }
        } else { // Actualizar sin cambiar contraseña
            if ($id) {
                $stmt = $this->db->prepare("UPDATE usuarios SET nombre_completo = ?, username = ?, id_rol = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssii", $nombre, $username, $id_rol, $id);
            }
        }
        
        return isset($stmt) ? $stmt->execute() : false;
    }

    public function cambiarEstado($id, $estado) {
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = ? WHERE id_usuario = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }

    public function obtenerPermisosPorRol($id_rol) {
        $stmt = $this->db->prepare("SELECT p.nombre_permiso 
                                    FROM rol_permiso rp
                                    JOIN permisos p ON rp.id_permiso = p.id_permiso
                                    WHERE rp.id_rol = ?");
        $stmt->bind_param("i", $id_rol);
        $stmt->execute();
        return $stmt->get_result();
    }

    

    
}