<?php
require_once __DIR__ . '/../models/Usuario.php';

class PerfilController {
    private $usuarioModel;

    public function __construct() {
        global $conexion;
        $this->usuarioModel = new Usuario($conexion);
    }

    public function index() {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
        $usuario = $this->usuarioModel->obtenerPorId($_SESSION['id_usuario']);
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/perfil/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario'])) {
            $moneda = $_POST['moneda'];
            $stmt = $GLOBALS['conexion']->prepare("UPDATE usuarios SET moneda = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $moneda, $_SESSION['id_usuario']);
            if ($stmt->execute()) {
                $_SESSION['moneda_usuario'] = $moneda;
                $_SESSION['mensaje'] = 'Perfil actualizado correctamente.';
                $_SESSION['mensaje_tipo'] = 'success';
            }
        }
        header('Location: ' . BASE_URL . 'perfil');
    }

    public function subirLogo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
            $archivo = $_FILES['logo'];

            if ($archivo['error'] === UPLOAD_ERR_OK) {
                $tipoMime = mime_content_type($archivo['tmp_name']);
                if ($tipoMime == 'image/png') {
                    $rutaDestino = __DIR__ . '/../../public/img/logo.png';
                    
                    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                        $_SESSION['mensaje'] = 'Logo actualizado correctamente.';
                        $_SESSION['mensaje_tipo'] = 'success';
                    } else {
                        $_SESSION['mensaje'] = 'Error al guardar el nuevo logo.';
                        $_SESSION['mensaje_tipo'] = 'danger';
                    }
                } else {
                    $_SESSION['mensaje'] = 'Error: El archivo debe ser de formato PNG.';
                    $_SESSION['mensaje_tipo'] = 'danger';
                }
            } else {
                $_SESSION['mensaje'] = 'Error al subir el archivo.';
                $_SESSION['mensaje_tipo'] = 'danger';
            }
        }
        header('Location: ' . BASE_URL . 'perfil');
    }
}