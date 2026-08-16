<?php
require_once '../app/models/Usuario.php';

class UsuarioController {
    private $usuarioModel;

    public function __construct() {
        global $conexion;
        $this->usuarioModel = new Usuario($conexion);
    }

    // Muestra la lista de usuarios
    public function index() {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $usuarios = $this->usuarioModel->obtenerTodos();
        $roles = $this->usuarioModel->obtenerRoles();

        require_once '../app/views/layouts/header.php';
        require_once '../app/views/usuarios/index.php';
        require_once '../app/views/layouts/footer.php';
    }

    // Procesa el guardado (crear/editar)
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['id_usuario'])) {
            $this->usuarioModel->guardar($_POST);
        }
        header('Location: ' . BASE_URL . 'usuario');
    }

    /**
     * Procesa el cambio de estado (activar/desactivar).
     * @param int $id El ID del usuario.
     * @param int $estado El nuevo estado (0 o 1).
     */
    public function cambiarEstado($id, $estado) {
        if (isset($_SESSION['id_usuario'])) {
            // Lógica de seguridad: no permitir que un usuario se desactive a sí mismo
            if ((int)$id === $_SESSION['id_usuario']) {
                // Opcional: podrías guardar un mensaje de error en la sesión
            } else {
                $this->usuarioModel->cambiarEstado((int)$id, (int)$estado);
            }
        }
        header('Location: ' . BASE_URL . 'usuario');
    }
}