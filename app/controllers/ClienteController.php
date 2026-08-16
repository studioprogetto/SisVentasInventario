<?php
require_once '../app/models/Cliente.php';

class ClienteController
{
    private $clienteModel;

    public function __construct()
    {
        global $conexion;
        $this->clienteModel = new Cliente($conexion);
    }

    public function index($estado = 'activos')
    {
        $filtro_activo = ($estado === 'inactivos') ? 0 : 1;
        $clientes = $this->clienteModel->obtenerTodos($filtro_activo);

        require_once '../app/views/layouts/header.php';
        require_once '../app/views/clientes/index.php';
        require_once '../app/views/layouts/footer.php';
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = $_POST;
            $datos = array_map('trim', $datos);

            if (empty($datos['nombre_cliente'])) {
                header('Location: ' . BASE_URL . 'cliente?error=nombre');
                exit;
            }

            $this->clienteModel->guardar($datos);
        }
        header('Location: ' . BASE_URL . 'cliente');
        exit;
    }

    public function cambiarEstado($id, $estado)
    {
        $id = (int)$id;
        $estado = (int)$estado;

        if ($id > 0 && ($estado === 0 || $estado === 1)) {
            $this->clienteModel->cambiarEstado($id, $estado);
        }

        header('Location: ' . BASE_URL . 'cliente');
        exit;
    }

    public function sellos($id_cliente)
    {
        header('Content-Type: application/json');

        $id_cliente = (int)$id_cliente;
        if ($id_cliente <= 0) {
            echo json_encode(['sellos' => 0]);
            return;
        }

        $sellos = $this->clienteModel->obtenerSellosCliente($id_cliente);
        echo json_encode(['sellos' => $sellos]);
    }

    public function buscar()
    {
        header('Content-Type: application/json');

        $term = $_GET['term'] ?? '';
        $term = trim($term);

        // Debug
        error_log("DEBUG - Búsqueda cliente término: " . $term);

        if ($term === '') {
            echo json_encode([]);
            return;
        }

        $result = $this->clienteModel->buscar($term);
        $clientes = [];

        while ($row = $result->fetch_assoc()) {
            // Asegurar que el saldo sea numérico
            $row['saldo'] = (float)$row['saldo'];
            $clientes[] = $row;

            // Debug
            error_log("DEBUG - Cliente encontrado: " . $row['nombre_cliente'] . " - Saldo: " . $row['saldo']);
        }

        // Debug
        error_log("DEBUG - Total clientes encontrados: " . count($clientes));
        error_log("DEBUG - Datos enviados: " . json_encode($clientes));

        echo json_encode($clientes);
    }

    // Método de diagnóstico
    public function diagnosticar($id_cliente = 31)
    {
        header('Content-Type: application/json');
        
        $cliente = $this->clienteModel->obtenerClientePorId($id_cliente);
        $saldo = $this->clienteModel->obtenerSaldoCliente($id_cliente);
        
        echo json_encode([
            'cliente' => $cliente,
            'saldo' => $saldo,
            'mensaje' => 'Diagnóstico completado'
        ]);
    }
} 