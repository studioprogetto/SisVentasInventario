<?php
require_once '../app/models/Categoria.php';

class CategoriaController {
    private $categoriaModel;

    public function __construct() {
        global $conexion;
        $this->categoriaModel = new Categoria($conexion);
    }

    // Acción principal: Muestra la lista de categorías
    public function index($estado = 'activos') {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Lógica para filtrar por estado
        $filtro_activo = ($estado == 'inactivos') ? 0 : 1;
        $categorias = $this->categoriaModel->obtenerTodas($filtro_activo);

        // Cargar la Vista
        require_once '../app/views/layouts/header.php';
        require_once '../app/views/categorias/index.php';
        require_once '../app/views/layouts/footer.php';
    }

    // Acción para guardar (crear o editar)
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['id_usuario'])) {
            $this->categoriaModel->guardar($_POST);
        }
        header('Location: ' . BASE_URL . 'categoria');
    }

    // Acción para activar/desactivar
    public function cambiarEstado($id, $estado) {
        if (isset($_SESSION['id_usuario'])) {
            $this->categoriaModel->cambiarEstado((int)$id, (int)$estado);
        }
        header('Location: ' . BASE_URL . 'categoria');
    }
}