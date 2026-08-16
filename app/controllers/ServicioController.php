<?php
require_once __DIR__ . '/../models/Servicio.php';
require_once __DIR__ . '/../models/DetalleServicio.php';

class ServicioController
{
    private $servicioModel;
    private $detalleServicioModel;

    public function __construct()
    {
        global $conexion;
        $this->servicioModel = new Servicio($conexion);
        $this->detalleServicioModel = new DetalleServicio($conexion);
    }

    // Listado de servicios
    public function index($estado = 'activos')
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $filtro_activo = ($estado == 'inactivos') ? 0 : 1;
        $buscar = $_GET['buscar'] ?? '';

        $servicios = $this->servicioModel->obtenerTodos($filtro_activo, $buscar);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/servicios/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // Guardar o actualizar servicio
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['id_usuario'])) {
            $this->servicioModel->guardar($_POST);
        }
        header('Location: ' . BASE_URL . 'servicio');
    }

    // Cambiar estado activo/inactivo
    public function cambiarEstado($id, $estado)
    {
        if (isset($_SESSION['id_usuario'])) {
            $this->servicioModel->cambiarEstado((int)$id, (int)$estado);
        }
        header('Location: ' . BASE_URL . 'servicio');
    }

    // Ver detalle del servicio y movimientos
    public function detalle($id)
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $id_servicio = (int)$id;
        $servicio = $this->servicioModel->obtenerPorId($id_servicio);
        $detalle = $this->detalleServicioModel->obtenerPorServicio($id_servicio);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/servicios/detalle.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}