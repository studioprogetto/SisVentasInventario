<?php
require_once __DIR__ . '/../models/TipoCambio.php';
require_once __DIR__ . '/../models/TipoMoneda.php';

class TipoCambioController {
    private $tipoCambioModel;
    private $tipoMonedaModel;

    public function __construct() {
        global $conexion;
        $this->tipoCambioModel = new TipoCambio($conexion);
        $this->tipoMonedaModel = new TipoMoneda($conexion);
    }

    public function index() {
        if (!isset($_SESSION['id_usuario']) || !tienePermiso('tipocambio_ver_lista')) {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
        $tipos_de_cambio = $this->tipoCambioModel->obtenerTodos();
        $monedas_activas = $this->tipoMonedaModel->obtenerActivos();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/tipo_cambio/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * Procesa el guardado del tipo de cambio.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && tienePermiso('tipocambio_gestionar')) {
            $this->tipoCambioModel->guardar($_POST);
        }
        header('Location: ' . BASE_URL . 'tipocambio');
    }
}