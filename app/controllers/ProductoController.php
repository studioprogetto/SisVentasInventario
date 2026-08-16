<?php
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Proveedor.php';

class ProductoController
{
    private $productoModel;
    private $almacenModel;
    private $proveedorModel;

    public function __construct()
    {
        global $conexion;
        $this->productoModel = new Producto($conexion);
        $this->almacenModel = new Almacen($conexion);
        $this->proveedorModel = new Proveedor($conexion);
        ini_set('display_errors', 1); // CAMBIADO: activar para depurar
        error_reporting(E_ALL);
    }

    public function index($estado = 'activos')
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $filtro_activo = ($estado === 'inactivos') ? 0 : 1;

        // Obtener parámetros de filtros
        $buscar = $_GET['buscar'] ?? '';
        $pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $categorias = isset($_GET['categorias']) ? (array)$_GET['categorias'] : [];
        $precio_min = isset($_GET['precio_min']) && is_numeric($_GET['precio_min']) ? (float)$_GET['precio_min'] : null;
        $precio_max = isset($_GET['precio_max']) && is_numeric($_GET['precio_max']) ? (float)$_GET['precio_max'] : null;
        $orden = $_GET['orden'] ?? 'nombre ASC';

        // Preparar filtros
        $filtros = [
            'activo' => $filtro_activo,
            'buscar' => $buscar,
            'pagina' => $pagina,
            'categorias' => array_map('intval', $categorias),
            'precio_min' => $precio_min,
            'precio_max' => $precio_max,
            'orden' => $orden,
            'productos_por_pagina' => 30
        ];

        // Detectar si es una solicitud AJAX (DataTables)
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Si viene de AJAX, devolvemos solo JSON (no las vistas)
        if ($isAjax) {
            $resultado = $this->productoModel->obtenerProductosFiltrados($filtros);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['data' => $resultado['productos']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si no es AJAX, renderizamos las vistas normalmente
        $resultado = $this->productoModel->obtenerProductosFiltrados($filtros);
        $productos = $resultado['productos'];

        // OBTENER CATEGORÍAS, ALMACENES Y PROVEEDORES PARA EL MODAL
        $categorias = $this->productoModel->obtenerCategoriasActivas();
        $almacenes = $this->almacenModel->obtenerTodos();
        $proveedores = $this->proveedorModel->obtenerProveedoresActivos(); // NUEVO: Obtener proveedores

        // Pasar datos de paginación a la vista
        $datos_paginacion = [
            'total' => $resultado['total'],
            'pagina_actual' => $resultado['pagina_actual'],
            'total_paginas' => $resultado['total_paginas'],
            'productos_por_pagina' => $resultado['productos_por_pagina']
        ];

        // PASAR TODAS LAS VARIABLES A LA VISTA
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/productos/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // NUEVO MÉTODO: Búsqueda en tiempo real para autocompletado
    public function buscarSugerencias()
    {
        if (!isset($_SESSION['id_usuario'])) {
            http_response_code(401);
            exit;
        }

        $termino = $_GET['q'] ?? '';
        $limite = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;

        if (empty($termino) || strlen($termino) < 2) {
            echo json_encode([]);
            exit;
        }

        $sugerencias = $this->productoModel->buscarSugerencias($termino, $limite);

        // Formatear respuesta
        $resultado = [];
        foreach ($sugerencias as $sugerencia) {
            $resultado[] = [
                'id' => $sugerencia['id_producto'],
                'nombre' => $sugerencia['nombre'],
                'descripcion' => $sugerencia['descripcion'] ?? '',
                'precio' => $sugerencia['precio_venta'],
                'stock' => $sugerencia['stock'],
                'categoria' => $sugerencia['categoria'] ?? '',
                'tipo_coincidencia' => $sugerencia['tipo_coincidencia'] ?? 'Coincidencia',
                'imagen' => $this->obtenerImagenProducto($sugerencia)
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // NUEVO MÉTODO: Búsqueda avanzada
    public function buscarAvanzado()
    {
        if (!isset($_SESSION['id_usuario'])) {
            http_response_code(401);
            exit;
        }

        $termino = $_GET['q'] ?? '';
        $limite = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;

        if (empty($termino)) {
            echo json_encode(['success' => false, 'message' => 'Término de búsqueda vacío']);
            exit;
        }

        $resultados = $this->productoModel->buscarAvanzado($termino, $limite);

        // Formatear respuesta
        $productosFormateados = [];
        foreach ($resultados as $producto) {
            $productosFormateados[] = [
                'id' => $producto['id_producto'],
                'nombre' => $producto['nombre'],
                'descripcion' => $producto['descripcion'] ?? '',
                'precio' => $producto['precio_venta'],
                'stock' => $producto['stock'],
                'categoria' => $producto['categoria'] ?? '',
                'puntuacion' => $producto['puntuacion'] ?? 0,
                'imagen' => $this->obtenerImagenProducto($producto)
            ];
        }

        $sugerencia = $this->generarSugerencia($termino, $resultados);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'resultados' => $productosFormateados,
            'termino' => $termino,
            'total' => count($productosFormateados),
            'sugerencia' => $sugerencia
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // NUEVO MÉTODO: Generar sugerencias de corrección
    private function generarSugerencia($termino, $resultados)
    {
        if (empty($resultados)) {
            $palabras = explode(' ', $termino);

            // Sugerir búsqueda con menos palabras si hay muchas
            if (count($palabras) > 2) {
                $sugerencia = implode(' ', array_slice($palabras, 0, 2));
                return "¿Quisiste decir: \"{$sugerencia}\"?";
            }

            // Sugerir verificar ortografía
            return "No se encontraron resultados. Verifica la ortografía o intenta con términos más generales.";
        }

        // Si hay resultados pero pocos, sugerir términos relacionados
        if (count($resultados) < 3) {
            $primerProducto = $resultados[0];
            $palabrasProducto = explode(' ', strtolower($primerProducto['nombre']));
            $palabrasBusqueda = explode(' ', strtolower($termino));

            $palabrasNoCoincidentes = array_diff($palabrasProducto, $palabrasBusqueda);

            if (!empty($palabrasNoCoincidentes)) {
                $sugerencia = implode(' ', array_slice($palabrasNoCoincidentes, 0, 2));
                return "¿Buscabas algo relacionado con: \"{$sugerencia}\"?";
            }
        }

        return null;
    }

    private function obtenerImagenProducto($producto)
    {
        if (!empty($producto['imagen_path'])) {
            if (strpos($producto['imagen_path'], 'http') === 0) {
                return $producto['imagen_path'];
            } else {
                return BASE_URL . 'public' . $producto['imagen_path'];
            }
        } elseif (!empty($producto['imagen_url'])) {
            return $producto['imagen_url'];
        }

        return BASE_URL . 'public/img/no-image.png';
    }

    public function guardar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario'])) {
            $datos = $_POST;

            // ✅ VALIDACIÓN
            if (
                trim($datos['nombre'] ?? '') === '' ||
                !isset($datos['precio_venta']) || $datos['precio_venta'] === '' ||
                !isset($datos['stock']) || $datos['stock'] === ''
            ) {

                $msg = "Faltan datos obligatorios";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                $_SESSION['error'] = $msg;
                header('Location: ' . $this->getRedirectUrl());
                exit;
            }

            // ✅ CONFIGURACIÓN DE DIRECTORIO
            $uploadDir = __DIR__ . '/../public/uploads/productos/';
            $publicPathPrefix = '/uploads/productos/'; // ✅ CAMBIADO: ruta relativa

            // Crear directorio si no existe
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $imagen_path = null;
            $imagen_url = !empty($datos['imagen_url']) ? trim($datos['imagen_url']) : null;

            // ✅ OBTENER PRODUCTO EXISTENTE SI ESTAMOS EDITANDO
            $existingImagenPath = null;
            $existingImagenUrl = null;
            if (!empty($datos['id_producto'])) {
                $productoExistente = $this->productoModel->obtenerPorId((int)$datos['id_producto']);
                if ($productoExistente) {
                    $existingImagenPath = $productoExistente['imagen_path'] ?? null;
                    $existingImagenUrl = $productoExistente['imagen_url'] ?? null;
                    $imagen_path = $existingImagenPath;
                    $imagen_url = $existingImagenUrl;
                }
            }

            // ✅ PRIMERO GUARDAR EL PRODUCTO PARA OBTENER EL ID REAL
            $datosTemporales = $datos;
            $datosTemporales['imagen_path'] = $imagen_path;
            $datosTemporales['imagen_url'] = $imagen_url;
            $datosTemporales['activo'] = $datos['activo'] ?? 1;
            $datosTemporales['codigo_barra'] = null;

            // Guardar producto para obtener ID
            $idProducto = $this->productoModel->guardar($datosTemporales);

            if (!$idProducto) {
                $msg = "Error al guardar el producto en la base de datos";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                $_SESSION['error'] = $msg;
                header('Location: ' . $this->getRedirectUrl());
                exit;
            }

            // ✅ AHORA MANEJAR LA IMAGEN CON EL ID REAL
            $imagenSubida = false;

            // Manejo de eliminación de imagen
            if (!empty($datos['eliminar_imagen']) && $datos['eliminar_imagen'] == '1') {
                if (!empty($imagen_path)) {
                    $rutaFisica = $uploadDir . basename($imagen_path);
                    if (file_exists($rutaFisica)) {
                        @unlink($rutaFisica);
                    }
                }
                $imagen_path = null;
                $imagen_url = null;
                $imagenSubida = true;
            }

            // ✅ MANEJO DE SUBIDA DE ARCHIVO
            if (!$imagenSubida && !empty($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['imagen_file'];

                if ($file['error'] === UPLOAD_ERR_OK) {
                    $maxSize = 2 * 1024 * 1024;
                    if ($file['size'] <= $maxSize) {
                        $tmp = $file['tmp_name'];
                        $origName = basename($file['name']);
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                        if (in_array($ext, $allowed)) {
                            // ✅ USAR ID REAL DEL PRODUCTO
                            $nameSanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
                            $finalName = "producto_{$idProducto}_{$nameSanitized}_" . time() . '.' . $ext;
                            $dest = $uploadDir . $finalName;

                            if (@move_uploaded_file($tmp, $dest)) {
                                // ✅ GUARDAR RUTA RELATIVA
                                $imagen_path = $publicPathPrefix . $finalName;
                                $imagen_url = null;
                                $imagenSubida = true;

                                // Eliminar imagen anterior si existe
                                if (!empty($existingImagenPath)) {
                                    $oldFile = $uploadDir . basename($existingImagenPath);
                                    if (file_exists($oldFile)) {
                                        @unlink($oldFile);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // ✅ MANEJO DE URL DE IMAGEN
            if (!$imagenSubida && !empty($datos['imagen_url'])) {
                $tmpUrl = trim($datos['imagen_url']);
                if (filter_var($tmpUrl, FILTER_VALIDATE_URL)) {
                    // Eliminar imagen local anterior si existe
                    if (!empty($existingImagenPath)) {
                        $oldFile = $uploadDir . basename($existingImagenPath);
                        if (file_exists($oldFile)) {
                            @unlink($oldFile);
                        }
                    }
                    $imagen_url = $tmpUrl;
                    $imagen_path = null;
                    $imagenSubida = true;
                }
            }

            // ✅ ACTUALIZAR EL PRODUCTO CON LA INFORMACIÓN FINAL DE LA IMAGEN
            if ($imagenSubida) {
                $datosFinales = $datos;
                $datosFinales['id_producto'] = $idProducto;
                $datosFinales['imagen_path'] = $imagen_path;
                $datosFinales['imagen_url'] = $imagen_url;
                $datosFinales['activo'] = $datos['activo'] ?? 1;
                $datosFinales['codigo_barra'] = null;

                $this->productoModel->guardar($datosFinales);
            }

            // ✅ RESPUESTA
            if ($isAjax) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => isset($datos['id_producto']) && $datos['id_producto'] !== ''
                        ? 'Producto actualizado correctamente'
                        : 'Producto registrado correctamente'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $_SESSION['success'] = 'Producto guardado correctamente';
            header('Location: ' . $this->getRedirectUrl());
            exit;
        }

        // Solicitud inválida
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
        } else {
            $_SESSION['error'] = 'Solicitud inválida';
            header('Location: ' . $this->getRedirectUrl());
        }
        exit;
    }

    // Cambiar estado de producto (activo/inactivo)
    public function cambiarEstado($id, $estado)
    {
        if (isset($_SESSION['id_usuario'])) {
            $this->productoModel->cambiarEstado((int)$id, (int)$estado);
        }

        header('Location: ' . $this->getRedirectUrl());
        exit;
    }

    // Ver detalle y movimientos de inventario
    public function detalle($id)
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $id_producto = (int)$id;
        $producto = $this->productoModel->obtenerPorId($id_producto);

        // Obtener movimientos como array asociativo
        $movimientos = $this->productoModel->obtenerMovimientos($id_producto);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/productos/detalle.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // URL de redirección respetando si se está viendo inactivos
    private function getRedirectUrl(): string
    {
        $url_actual = $_GET['url'] ?? '';
        return strpos($url_actual, 'inactivos') !== false
            ? BASE_URL . 'producto/index/inactivos'
            : BASE_URL . 'producto';
    }

    public function generarEtiquetas()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
        $todos = isset($_GET['todos']) ? (int)$_GET['todos'] : 0;

        // Depuración: escribir en el log de errores
        error_log("=== generarEtiquetas: ids=" . print_r($ids, true) . " todos=$todos");

        if ($todos == 1) {
            $filtros = [
                'activo' => 1,
                'buscar' => '',
                'pagina' => 1,
                'categorias' => [],
                'precio_min' => null,
                'precio_max' => null,
                'orden' => 'nombre ASC',
                'productos_por_pagina' => 1000
            ];
            $resultado = $this->productoModel->obtenerProductosFiltrados($filtros);
            error_log("Resultado de obtenerProductosFiltrados: " . print_r($resultado, true));
            $productos = $resultado['productos'];
        } elseif (!empty($ids)) {
            $productos = [];
            foreach ($ids as $id) {
                $producto = $this->productoModel->obtenerPorId((int)$id);
                if ($producto) {
                    $productos[] = $producto;
                }
            }
        } else {
            $_SESSION['error'] = 'No se seleccionaron productos para generar etiquetas';
            header('Location: ' . BASE_URL . 'producto');
            exit;
        }

        error_log("Productos obtenidos: " . count($productos));

        if (empty($productos)) {
            $_SESSION['error'] = 'No hay productos para generar etiquetas';
            header('Location: ' . BASE_URL . 'producto');
            exit;
        }

        require_once __DIR__ . '/../views/productos/etiquetas_html.php';
    }

    // NUEVO MÉTODO DE PRUEBA
    public function generarEtiquetasPrueba()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Datos de prueba (2 productos)
        $productos = [
            [
                'id_producto' => 1,
                'nombre' => 'Producto de Prueba 1',
                'descripcion' => 'Esta es una descripción de prueba para verificar que las etiquetas funcionan correctamente.',
                'precio_venta' => 99.99,
                'imagen_path' => '',
                'imagen_url' => ''
            ],
            [
                'id_producto' => 2,
                'nombre' => 'Producto de Prueba 2',
                'descripcion' => 'Segundo producto de prueba con descripción más larga para comprobar el ajuste de texto en las etiquetas.',
                'precio_venta' => 149.99,
                'imagen_path' => '',
                'imagen_url' => ''
            ]
        ];

        require_once __DIR__ . '/../views/productos/etiquetas_html.php';
        exit;
    }
}
