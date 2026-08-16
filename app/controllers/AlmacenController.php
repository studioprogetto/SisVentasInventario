<?php
require_once __DIR__ . '/../models/Almacen.php';

class AlmacenController {
    private $model;
    
    public function __construct() {
        global $conexion;
        $this->model = new Almacen($conexion);
    }

    public function index() {
        if (!isset($_SESSION['id_usuario']) || !tienePermiso('almacenes_gestionar')) {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
        
        $almacenes = $this->model->obtenerTodos();
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/almacenes/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && tienePermiso('almacenes_gestionar')) {
            $this->model->guardar($_POST);
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = [
                'tipo' => 'success',
                'texto' => isset($_POST['id']) && !empty($_POST['id']) ? 
                          'Almacén actualizado correctamente' : 
                          'Almacén creado correctamente'
            ];
        }
        header('Location: ' . BASE_URL . 'almacen');
        exit;
    }
    
    public function cambiarEstado($id, $estado) {
        if (tienePermiso('almacenes_gestionar')) {
            $this->model->cambiarEstado((int)$id, (int)$estado);
            
            $_SESSION['mensaje'] = [
                'tipo' => 'success',
                'texto' => $estado == 1 ? 'Almacén activado correctamente' : 'Almacén desactivado correctamente'
            ];
        }
        header('Location: ' . BASE_URL . 'almacen');
        exit;
    }
}