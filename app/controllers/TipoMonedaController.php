<?php
require_once __DIR__ . '/../models/TipoMoneda.php';

class TipoMonedaController {
    private $model;
    public function __construct() {
        global $conexion;
        $this->model = new TipoMoneda($conexion);
    }

    public function index() {
        if (!isset($_SESSION['id_usuario']) || !tienePermiso('tipomoneda_gestionar')) {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
        $monedas = $this->model->obtenerTodos();
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/tipo_moneda/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && tienePermiso('tipomoneda_gestionar')) {
            $this->model->guardar($_POST);
        }
        header('Location: ' . BASE_URL . 'tipomoneda');
    }
    
    public function cambiarEstado($id, $estado) {
        if (tienePermiso('tipomoneda_gestionar')) {
            $this->model->cambiarEstado((int)$id, (int)$estado);
        }
        header('Location: ' . BASE_URL . 'tipomoneda');
    }
}