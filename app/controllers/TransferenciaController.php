<?php
require_once __DIR__ . '/../models/Transferencia.php';

class TransferenciaController {
    private $model;
    public function __construct() {
        global $conexion;
        $this->model = new Transferencia($conexion);
    }

    public function index() {
        if (!isset($_SESSION['id_usuario']) || !tienePermiso('transferencias_crear')) {
            header('Location: ' . BASE_URL . 'home'); exit;
        }
        $productos = $this->model->getProductosActivos();
        $almacenes = $this->model->getAlmacenesActivos();
        
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/transferencias/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && tienePermiso('transferencias_crear')) {
            $resultado = $this->model->realizarTransferencia($_POST);
            if ($resultado === true) {
                $_SESSION['mensaje'] = 'Transferencia realizada con éxito.';
                $_SESSION['mensaje_tipo'] = 'success';
            } else {
                $_SESSION['mensaje'] = 'Error: ' . $resultado;
                $_SESSION['mensaje_tipo'] = 'danger';
            }
        }
        header('Location: ' . BASE_URL . 'transferencia');
    }
}