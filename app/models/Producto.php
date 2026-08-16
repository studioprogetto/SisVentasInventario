<?php
class Producto
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    // Método para obtener productos con filtros y paginación - ACTUALIZADO
    public function obtenerProductosFiltrados($filtros = [])
    {
        $activo = $filtros['activo'] ?? 1;
        $buscar = $filtros['buscar'] ?? '';
        $categorias = $filtros['categorias'] ?? [];
        $precio_min = $filtros['precio_min'] ?? null;
        $precio_max = $filtros['precio_max'] ?? null;
        $orden = $filtros['orden'] ?? 'nombre ASC';
        $pagina = $filtros['pagina'] ?? 1;
        $productos_por_pagina = $filtros['productos_por_pagina'] ?? 30;
        $inicio = ($pagina - 1) * $productos_por_pagina;

        // Si hay búsqueda, usar el nuevo método de búsqueda avanzada
        if (!empty($buscar)) {
            return $this->buscarProductosAvanzado($filtros, $inicio, $productos_por_pagina);
        }

        // Consulta base para cuando no hay búsqueda
        $sql = "SELECT p.*, c.nombre AS nombre_categoria, a.nombre AS nombre_almacen
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN almacenes a ON p.id_almacen = a.id
            WHERE p.activo = ?";

        $params = [$activo];
        $types = "i";

        // Filtro por categorías
        if (!empty($categorias) && is_array($categorias)) {
            $placeholders = implode(',', array_fill(0, count($categorias), '?'));
            $sql .= " AND p.id_categoria IN ($placeholders)";
            $params = array_merge($params, $categorias);
            $types .= str_repeat("i", count($categorias));
        }

        // Filtro por precio
        if ($precio_min !== null && is_numeric($precio_min)) {
            $sql .= " AND p.precio_venta >= ?";
            $params[] = $precio_min;
            $types .= "d";
        }

        if ($precio_max !== null && is_numeric($precio_max)) {
            $sql .= " AND p.precio_venta <= ?";
            $params[] = $precio_max;
            $types .= "d";
        }

        // Orden
        $ordenes_validos = [
            'nombre ASC' => 'p.nombre ASC',
            'nombre DESC' => 'p.nombre DESC',
            'precio ASC' => 'p.precio_venta ASC',
            'precio DESC' => 'p.precio_venta DESC'
        ];

        $orden_sql = $ordenes_validos[$orden] ?? 'p.nombre ASC';
        $sql .= " ORDER BY $orden_sql";

        // Paginación
        $sql .= " LIMIT ?, ?";
        $params[] = $inicio;
        $params[] = $productos_por_pagina;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            error_log("Error en la preparación: " . $this->db->error);
            return ['productos' => [], 'total' => 0];
        }

        // Bind parameters
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $productos = [];

        while ($row = $result->fetch_assoc()) {
            $productos[] = [
                'id_producto'      => $row['id_producto'] ?? 0,
                'nombre'           => $row['nombre'] ?? 'Sin nombre',
                'descripcion'      => $row['descripcion'] ?? '',
                'id_categoria'     => $row['id_categoria'] ?? null,
                'nombre_categoria' => $row['nombre_categoria'] ?? 'Sin categoría',
                'id_almacen'       => $row['id_almacen'] ?? null,
                'nombre_almacen'   => $row['nombre_almacen'] ?? 'No asignado',
                'id_proveedor_preferido' => $row['id_proveedor_preferido'] ?? null, 
                'precio_venta'     => $row['precio_venta'] ?? 0,
                'precio_compra'    => $row['precio_compra'] ?? 0,
                'stock'            => $row['stock'] ?? 0,
                'stock_minimo'     => $row['stock_minimo'] ?? 0,
                'activo'           => $row['activo'] ?? 0,
                'imagen_path'      => $row['imagen_path'] ?? null,
                'imagen_url'       => $row['imagen_url'] ?? null
            ];
        }


        // Obtener total para paginación
        $total = $this->obtenerTotalProductosFiltrados($filtros);

        return [
            'productos' => $productos,
            'total' => $total,
            'pagina_actual' => $pagina,
            'total_paginas' => ceil($total / $productos_por_pagina),
            'productos_por_pagina' => $productos_por_pagina
        ];
    }

    // NUEVO MÉTODO: Búsqueda avanzada con tolerancia a errores
    private function buscarProductosAvanzado($filtros, $inicio, $productos_por_pagina)
    {
        $activo = $filtros['activo'] ?? 1;
        $buscar = $filtros['buscar'] ?? '';
        $categorias = $filtros['categorias'] ?? [];
        $precio_min = $filtros['precio_min'] ?? null;
        $precio_max = $filtros['precio_max'] ?? null;
        $orden = $filtros['orden'] ?? 'relevancia DESC';
        $pagina = $filtros['pagina'] ?? 1;

        $termino = strtolower(trim($buscar));
        $palabras = explode(' ', $termino);

        // Construir condiciones para búsqueda por nombre (prioridad alta)
        $condicionesNombre = [];
        $condicionesDescripcion = [];

        foreach ($palabras as $palabra) {
            if (strlen($palabra) > 2) {
                $condicionesNombre[] = "LOWER(p.nombre) LIKE '%" . $this->db->real_escape_string($palabra) . "%'";
                $condicionesDescripcion[] = "LOWER(p.descripcion) LIKE '%" . $this->db->real_escape_string($palabra) . "%'";
            }
        }

        // Si no hay palabras válidas, buscar el término completo
        if (empty($condicionesNombre)) {
            $condicionesNombre[] = "LOWER(p.nombre) LIKE '%" . $this->db->real_escape_string($termino) . "%'";
            $condicionesDescripcion[] = "LOWER(p.descripcion) LIKE '%" . $this->db->real_escape_string($termino) . "%'";
        }

        $whereNombre = implode(' AND ', $condicionesNombre);
        $whereDescripcion = implode(' AND ', $condicionesDescripcion);

        // Consulta base con relevancia
        $sql = "SELECT 
                    p.*, 
                    c.nombre AS nombre_categoria, 
                    a.nombre AS nombre_almacen,
                    -- Calcular relevancia
                    CASE 
                        WHEN ($whereNombre) THEN 100 -- Máxima prioridad para coincidencia exacta en nombre
                        WHEN (LOWER(p.nombre) LIKE '" . $this->db->real_escape_string($termino) . "%') THEN 90 -- Prioridad alta para inicio del nombre
                        WHEN (LOWER(p.nombre) LIKE '%" . $this->db->real_escape_string($termino) . "%') THEN 80 -- Prioridad media para nombre que contiene
                        WHEN ($whereDescripcion) THEN 70 -- Prioridad media para descripción
                        WHEN (LOWER(p.descripcion) LIKE '%" . $this->db->real_escape_string($termino) . "%') THEN 60 -- Prioridad baja para descripción
                        ELSE 0
                    END as relevancia
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN almacenes a ON p.id_almacen = a.id
                WHERE p.activo = ? 
                AND (
                    $whereNombre 
                    OR $whereDescripcion
                    OR LOWER(p.nombre) LIKE '%" . $this->db->real_escape_string($termino) . "%'
                    OR LOWER(p.descripcion) LIKE '%" . $this->db->real_escape_string($termino) . "%'
                    OR p.codigo_barra = '" . $this->db->real_escape_string($termino) . "'
                )";

        $params = [$activo];
        $types = "i";

        // Filtro por categorías
        if (!empty($categorias) && is_array($categorias)) {
            $placeholders = implode(',', array_fill(0, count($categorias), '?'));
            $sql .= " AND p.id_categoria IN ($placeholders)";
            $params = array_merge($params, $categorias);
            $types .= str_repeat("i", count($categorias));
        }

        // Filtro por precio
        if ($precio_min !== null && is_numeric($precio_min)) {
            $sql .= " AND p.precio_venta >= ?";
            $params[] = $precio_min;
            $types .= "d";
        }

        if ($precio_max !== null && is_numeric($precio_max)) {
            $sql .= " AND p.precio_venta <= ?";
            $params[] = $precio_max;
            $types .= "d";
        }

        // Ordenamiento por relevancia
        $ordenes_validos = [
            'nombre ASC' => 'p.nombre ASC',
            'nombre DESC' => 'p.nombre DESC',
            'precio ASC' => 'p.precio_venta ASC',
            'precio DESC' => 'p.precio_venta DESC',
            'relevancia DESC' => 'relevancia DESC, p.nombre ASC'
        ];

        $orden_sql = $ordenes_validos[$orden] ?? 'relevancia DESC, p.nombre ASC';
        $sql .= " ORDER BY $orden_sql";

        // Paginación
        $sql .= " LIMIT ?, ?";
        $params[] = $inicio;
        $params[] = $productos_por_pagina;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            error_log("Error en la preparación: " . $this->db->error);
            return ['productos' => [], 'total' => 0];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $productos = [];

        while ($row = $result->fetch_assoc()) {
            $productos[] = [
                'id_producto'      => $row['id_producto'] ?? 0,
                'nombre'           => $row['nombre'] ?? 'Sin nombre',
                'descripcion'      => $row['descripcion'] ?? '',
                'id_categoria'     => $row['id_categoria'] ?? null,
                'nombre_categoria' => $row['nombre_categoria'] ?? 'Sin categoría',
                'id_almacen'       => $row['id_almacen'] ?? null,
                'nombre_almacen'   => $row['nombre_almacen'] ?? 'No asignado',
                'precio_venta'     => $row['precio_venta'] ?? 0,
                'precio_compra'    => $row['precio_compra'] ?? 0,
                'stock'            => $row['stock'] ?? 0,
                'stock_minimo'     => $row['stock_minimo'] ?? 0,
                'activo'           => $row['activo'] ?? 0,
                'imagen_path'      => $row['imagen_path'] ?? null,
                'imagen_url'       => $row['imagen_url'] ?? null,
                'relevancia'       => $row['relevancia'] ?? 0
            ];
        }

        // Obtener total para paginación
        $total = $this->obtenerTotalProductosFiltrados($filtros);

        return [
            'productos' => $productos,
            'total' => $total,
            'pagina_actual' => $pagina,
            'total_paginas' => ceil($total / $productos_por_pagina),
            'productos_por_pagina' => $productos_por_pagina
        ];
    }

    // Método para obtener el total de productos filtrados - ACTUALIZADO
    private function obtenerTotalProductosFiltrados($filtros)
    {
        $activo = $filtros['activo'] ?? 1;
        $buscar = $filtros['buscar'] ?? '';
        $categorias = $filtros['categorias'] ?? [];
        $precio_min = $filtros['precio_min'] ?? null;
        $precio_max = $filtros['precio_max'] ?? null;

        // Si hay búsqueda, usar lógica de búsqueda avanzada
        if (!empty($buscar)) {
            $termino = strtolower(trim($buscar));
            $palabras = explode(' ', $termino);

            $condicionesNombre = [];
            $condicionesDescripcion = [];

            foreach ($palabras as $palabra) {
                if (strlen($palabra) > 2) {
                    $condicionesNombre[] = "LOWER(nombre) LIKE '%" . $this->db->real_escape_string($palabra) . "%'";
                    $condicionesDescripcion[] = "LOWER(descripcion) LIKE '%" . $this->db->real_escape_string($palabra) . "%'";
                }
            }

            if (empty($condicionesNombre)) {
                $condicionesNombre[] = "LOWER(nombre) LIKE '%" . $this->db->real_escape_string($termino) . "%'";
                $condicionesDescripcion[] = "LOWER(descripcion) LIKE '%" . $this->db->real_escape_string($termino) . "%'";
            }

            $whereNombre = implode(' AND ', $condicionesNombre);
            $whereDescripcion = implode(' AND ', $condicionesDescripcion);

            $sql = "SELECT COUNT(*) as total
                    FROM productos p
                    WHERE p.activo = ?
                    AND (
                        $whereNombre 
                        OR $whereDescripcion
                        OR LOWER(p.nombre) LIKE '%" . $this->db->real_escape_string($termino) . "%'
                        OR LOWER(p.descripcion) LIKE '%" . $this->db->real_escape_string($termino) . "%'
                        OR p.codigo_barra = '" . $this->db->real_escape_string($termino) . "'
                    )";
        } else {
            $sql = "SELECT COUNT(*) as total
                    FROM productos p
                    WHERE p.activo = ?";
        }

        $params = [$activo];
        $types = "i";

        // Filtro por categorías
        if (!empty($categorias) && is_array($categorias)) {
            $placeholders = implode(',', array_fill(0, count($categorias), '?'));
            $sql .= " AND p.id_categoria IN ($placeholders)";
            $params = array_merge($params, $categorias);
            $types .= str_repeat("i", count($categorias));
        }

        // Filtro por precio
        if ($precio_min !== null && is_numeric($precio_min)) {
            $sql .= " AND p.precio_venta >= ?";
            $params[] = $precio_min;
            $types .= "d";
        }

        if ($precio_max !== null && is_numeric($precio_max)) {
            $sql .= " AND p.precio_venta <= ?";
            $params[] = $precio_max;
            $types .= "d";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['total'] ?? 0;
    }

    // NUEVO MÉTODO: Búsqueda en tiempo real con sugerencias
    public function buscarSugerencias($termino, $limite = 8)
    {
        if (strlen($termino) < 2) {
            return [];
        }

        $termino = strtolower(trim($termino));
        $terminoEscapado = $this->db->real_escape_string($termino);

        $sql = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.descripcion,
                    p.precio_venta,
                    p.stock,
                    p.imagen_url,
                    p.imagen_path,
                    c.nombre as categoria,
                    CASE 
                        WHEN LOWER(p.nombre) LIKE '{$terminoEscapado}%' THEN 'Coincidencia exacta'
                        WHEN LOWER(p.nombre) LIKE '%{$terminoEscapado}%' THEN 'Coincidencia parcial'
                        WHEN LOWER(p.descripcion) LIKE '%{$terminoEscapado}%' THEN 'En descripción'
                        ELSE 'Otra coincidencia'
                    END as tipo_coincidencia
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                WHERE p.activo = 1 
                AND (
                    LOWER(p.nombre) LIKE '{$terminoEscapado}%'
                    OR LOWER(p.nombre) LIKE '%{$terminoEscapado}%'
                    OR LOWER(p.descripcion) LIKE '%{$terminoEscapado}%'
                    OR p.codigo_barra LIKE '{$terminoEscapado}%'
                )
                ORDER BY 
                    CASE 
                        WHEN LOWER(p.nombre) LIKE '{$terminoEscapado}%' THEN 1
                        WHEN LOWER(p.nombre) LIKE '%{$terminoEscapado}%' THEN 2
                        WHEN LOWER(p.descripcion) LIKE '%{$terminoEscapado}%' THEN 3
                        ELSE 4
                    END,
                    p.nombre ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        $result = $stmt->get_result();

        $sugerencias = [];
        while ($row = $result->fetch_assoc()) {
            $sugerencias[] = $row;
        }

        return $sugerencias;
    }

    // NUEVO MÉTODO: Búsqueda avanzada con corrección de errores
    public function buscarAvanzado($termino, $limite = 15)
    {
        if (empty($termino)) {
            return [];
        }

        $termino = strtolower(trim($termino));
        $terminoEscapado = $this->db->real_escape_string($termino);

        // Generar variaciones para corrección de errores
        $variaciones = $this->generarVariaciones($termino);
        $condicionesVariaciones = [];

        foreach ($variaciones as $variacion) {
            if ($variacion !== $termino) {
                $variacionEscapado = $this->db->real_escape_string($variacion);
                $condicionesVariaciones[] = "LOWER(p.nombre) LIKE '%{$variacionEscapado}%'";
            }
        }

        $condicionesVariacionesSQL = !empty($condicionesVariaciones) ? "OR " . implode(" OR ", $condicionesVariaciones) : "";

        $sql = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.descripcion,
                    p.precio_venta,
                    p.stock,
                    p.imagen_url,
                    p.imagen_path,
                    c.nombre as categoria,
                    (
                        CASE 
                            WHEN LOWER(p.nombre) = '{$terminoEscapado}' THEN 100
                            WHEN LOWER(p.nombre) LIKE '{$terminoEscapado}%' THEN 90
                            WHEN LOWER(p.nombre) LIKE '%{$terminoEscapado}%' THEN 80
                            ELSE 50
                        END +
                        CASE 
                            WHEN p.codigo_barra = '{$terminoEscapado}' THEN 20
                            ELSE 0
                        END
                    ) as puntuacion
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                WHERE p.activo = 1 
                AND (
                    LOWER(p.nombre) LIKE '%{$terminoEscapado}%'
                    OR LOWER(p.descripcion) LIKE '%{$terminoEscapado}%'
                    OR p.codigo_barra = '{$terminoEscapado}'
                    {$condicionesVariacionesSQL}
                )
                ORDER BY puntuacion DESC, p.nombre ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultados = [];
        while ($row = $result->fetch_assoc()) {
            $resultados[] = $row;
        }

        return $resultados;
    }

    // NUEVO MÉTODO: Generar variaciones para corrección de errores
    private function generarVariaciones($termino)
    {
        $variaciones = [];
        $length = strlen($termino);

        // Solo generar variaciones para términos de al menos 4 caracteres
        if ($length >= 4) {
            // Intercambiar caracteres adyacentes (errores comunes de tecleo)
            for ($i = 0; $i < $length - 1; $i++) {
                $variacion = $termino;
                $temp = $variacion[$i];
                $variacion[$i] = $variacion[$i + 1];
                $variacion[$i + 1] = $temp;
                $variaciones[] = $variacion;
            }

            // Eliminar caracteres duplicados
            for ($i = 0; $i < $length - 1; $i++) {
                if ($termino[$i] === $termino[$i + 1]) {
                    $variacion = substr($termino, 0, $i) . substr($termino, $i + 1);
                    $variaciones[] = $variacion;
                }
            }
        }

        return array_unique($variaciones);
    }

    // Método original para compatibilidad
    public function obtenerTodos($activo = 1, $buscar = '')
    {
        $filtros = [
            'activo' => $activo,
            'buscar' => $buscar,
            'productos_por_pagina' => 1000 // Número alto para obtener todos
        ];

        $resultado = $this->obtenerProductosFiltrados($filtros);
        return $resultado['productos'];
    }

    // Resto de métodos permanecen igual...
    private function normalizarTexto($texto)
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $acentos = [
            'á',
            'é',
            'í',
            'ó',
            'ú',
            'ä',
            'ë',
            'ï',
            'ö',
            'ü',
            'à',
            'è',
            'ì',
            'ò',
            'ù',
            'ñ',
            'Á',
            'É',
            'Í',
            'Ó',
            'Ú',
            'Ä',
            'Ë',
            'Ï',
            'Ö',
            'Ü',
            'À',
            'È',
            'Ì',
            'Ò',
            'Ù',
            'Ñ'
        ];
        $sinAcentos = [
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'n',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'n'
        ];
        $texto = str_replace($acentos, $sinAcentos, $texto);
        $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }

    public function obtenerCategoriasActivas()
    {
        return $this->db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
    }

    public function guardar($datos)
    {
        try {
            $id = $datos['id_producto'] ?? null;
            $codigo_barra = null;
            $nombre = trim($datos['nombre']);
            $descripcion = trim($datos['descripcion'] ?? '');
            $id_categoria = !empty($datos['id_categoria']) ? (int)$datos['id_categoria'] : null;
            $id_almacen = !empty($datos['id_almacen']) ? (int)$datos['id_almacen'] : null;
            $id_proveedor_preferido = !empty($datos['id_proveedor_preferido']) ? (int)$datos['id_proveedor_preferido'] : null;
            $precio_venta = (float)$datos['precio_venta'];
            $precio_compra = (float)($datos['precio_compra'] ?? 0);
            $stock = (int)$datos['stock'];
            $stock_minimo = (int)($datos['stock_minimo'] ?? 0);
            $activo = (int)($datos['activo'] ?? 1);
            $imagen_url = !empty($datos['imagen_url']) ? trim($datos['imagen_url']) : null;
            $imagen_path = !empty($datos['imagen_path']) ? trim($datos['imagen_path']) : null;

            if ($id) {
                $stmt = $this->db->prepare("
                UPDATE productos 
                SET nombre = ?, descripcion = ?, id_categoria = ?, id_almacen = ?, 
                    id_proveedor_preferido = ?, precio_venta = ?, precio_compra = ?, 
                    stock = ?, stock_minimo = ?, activo = ?, imagen_path = ?, imagen_url = ?,
                    codigo_barra = ?
                WHERE id_producto = ?
            ");
                $stmt->bind_param(
                    "ssiiiddiiisssi",
                    $nombre,
                    $descripcion,
                    $id_categoria,
                    $id_almacen,
                    $id_proveedor_preferido,
                    $precio_venta,
                    $precio_compra,
                    $stock,
                    $stock_minimo,
                    $activo,
                    $imagen_path,
                    $imagen_url,
                    $codigo_barra,
                    $id
                );
            } else {
                $stmt = $this->db->prepare("
                INSERT INTO productos 
                (nombre, descripcion, id_categoria, id_almacen, id_proveedor_preferido, 
                 precio_venta, precio_compra, stock, stock_minimo, activo, imagen_path, imagen_url, codigo_barra) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
                $stmt->bind_param(
                    "ssiiiddiiisss",
                    $nombre,
                    $descripcion,
                    $id_categoria,
                    $id_almacen,
                    $id_proveedor_preferido,
                    $precio_venta,
                    $precio_compra,
                    $stock,
                    $stock_minimo,
                    $activo,
                    $imagen_path,
                    $imagen_url,
                    $codigo_barra
                );
            }

            $result = $stmt->execute();

            if (!$result) {
                error_log("Error MySQL: " . $stmt->error);
                return false;
            }

            return $id ?: $stmt->insert_id;
        } catch (Exception $e) {
            error_log("Error en Producto::guardar - " . $e->getMessage());
            return false;
        }
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare("UPDATE productos SET activo = ? WHERE id_producto = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerMovimientos($id_producto)
    {
        $stmt = $this->db->prepare("SELECT m.*, u.nombre_completo, a.nombre as nombre_almacen
                                    FROM movimientos_inventario m
                                    JOIN usuarios u ON m.id_usuario = u.id_usuario
                                    LEFT JOIN almacenes a ON m.id_almacen = a.id
                                    WHERE m.id_producto = ? 
                                    ORDER BY m.fecha DESC");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerProductosBajosDeStock()
    {
        $sql = "SELECT p.id_producto, p.nombre, p.stock, p.stock_minimo, pr.nombre_proveedor
                FROM productos p
                LEFT JOIN proveedores pr ON p.id_proveedor_preferido = pr.id_proveedor
                WHERE p.activo = 1 AND p.stock <= p.stock_minimo
                ORDER BY p.stock ASC";

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }





// En models/Producto.php - Agrega estos métodos

    /**
     * 🔹 Obtener márgenes de productos
     */
    public function obtenerMargenesProductos()
    {
        $sql = "SELECT 
                p.id_producto,
                p.nombre,
                p.precio_compra,
                p.precio_venta,
                ROUND(((p.precio_venta - p.precio_compra) / p.precio_venta * 100), 2) as margen_porcentaje,
                (p.precio_venta - p.precio_compra) as margen_absoluto,
                COALESCE(SUM(dv.cantidad), 0) as total_vendido,
                COALESCE(SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)), 0) as ganancia_total
            FROM productos p
            LEFT JOIN detalle_ventas dv ON p.id_producto = dv.id_producto
            LEFT JOIN ventas v ON dv.id_venta = v.id_venta AND v.estado = 'completada'
            WHERE p.activo = 1
                AND dv.tipo_item = 'producto'
            GROUP BY p.id_producto, p.nombre, p.precio_compra, p.precio_venta
            HAVING margen_porcentaje IS NOT NULL
            ORDER BY ganancia_total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * 🔹 Obtener productos de baja rotación
     */
    public function obtenerProductosBajaRotacion($limite = 10)
    {
        $sql = "SELECT 
                p.id_producto,
                p.nombre,
                p.stock,
                p.stock_minimo,
                COALESCE(SUM(dv.cantidad), 0) as total_vendido_mes,
                DATEDIFF(NOW(), MAX(v.fecha_venta)) as dias_ultima_venta
            FROM productos p
            LEFT JOIN detalle_ventas dv ON p.id_producto = dv.id_producto
            LEFT JOIN ventas v ON dv.id_venta = v.id_venta AND v.estado = 'completada'
            WHERE p.activo = 1
                AND dv.tipo_item = 'producto'
            GROUP BY p.id_producto, p.nombre, p.stock, p.stock_minimo
            HAVING total_vendido_mes <= 5 OR dias_ultima_venta > 30 OR dias_ultima_venta IS NULL
            ORDER BY total_vendido_mes ASC, dias_ultima_venta DESC
            LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
