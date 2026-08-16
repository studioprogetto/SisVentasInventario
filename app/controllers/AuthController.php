<?php
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $usuarioModel;

    public function __construct() {
        global $conexion;
        $this->usuarioModel = new Usuario($conexion);
    }

    public function login() {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $usuario = $this->usuarioModel->buscarPorUsername($username);

            if ($usuario && password_verify($password, $usuario['password'])) {
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre_completo'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                $_SESSION['moneda_usuario'] = $usuario['moneda'];
                
                $stmt_permisos = $this->usuarioModel->obtenerPermisosPorRol($usuario['id_rol']);
                $permisos = [];
                if ($stmt_permisos) {
                    while ($fila = $stmt_permisos->fetch_assoc()) {
                        $permisos[] = $fila['nombre_permiso'];
                    }
                }
                $_SESSION['permisos'] = $permisos;

                header('Location: ' . BASE_URL . 'caja/index');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login');
        exit;
    }
}