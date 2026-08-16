<?php
require_once '../app/models/Proveedor.php';

class ProveedorController {
    private $proveedorModel;

    public function __construct() {
        global $conexion;
        $this->proveedorModel = new Proveedor($conexion);
    }

    // Muestra la lista de proveedores
    public function index($estado = 'activos') {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
        
        $filtro_activo = ($estado == 'inactivos') ? 0 : 1;
        $proveedores = $this->proveedorModel->obtenerTodos($filtro_activo);

        require_once '../app/views/layouts/header.php';
        require_once '../app/views/proveedores/index.php';
        require_once '../app/views/layouts/footer.php';
    }

    // Procesa el guardado (crear/editar)
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['id_usuario'])) {
            $this->proveedorModel->guardar($_POST);
        }
        header('Location: ' . BASE_URL . 'proveedor');
    }

    // Procesa el cambio de estado
    public function cambiarEstado($id, $estado) {
        if (isset($_SESSION['id_usuario'])) {
            $this->proveedorModel->cambiarEstado((int)$id, (int)$estado);
        }
        header('Location: ' . BASE_URL . 'proveedor');
    }
}