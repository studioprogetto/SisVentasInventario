<?php
class Venta
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }
    // 🔹 Procesar cambio de producto CORREGIDO
    public function procesarCambioProducto($datos_cambio)
    {
        $id_venta_original = $datos_cambio['id_venta_original'];
        $id_cliente = $datos_cambio['id_cliente'];
        $productos_originales = $datos_cambio['productos_originales'];
        $productos_nuevos = $datos_cambio['productos_nuevos'];
        $observacion = $datos_cambio['observacion'] ?? '';

        $this->db->begin_transaction();
        try {
            // 1. Verificar estado actual de la venta
            $venta_original = $this->obtenerVentaPorId($id_venta_original);
            if (!$venta_original) {
                throw new Exception("Venta original no encontrada");
            }

            // Validar que la venta no esté anulada
            if ($venta_original['estado'] === 'anulada') {
                throw new Exception("No se puede procesar cambio en una venta anulada");
            }

            // 2. Calcular total de productos originales (valor a devolver)
            $total_original = 0;
            foreach ($productos_originales as $producto) {
                $total_original += ($producto['precio'] * $producto['cantidad']);
                $this->devolverStock($producto['id_producto'], $producto['cantidad']);

                // Registrar detalle de productos devueltos
                $this->registrarDetalleCambio([
                    'id_cambio' => 0, // Se actualizará después
                    'id_producto' => $producto['id_producto'],
                    'cantidad' => $producto['cantidad'],
                    'precio' => $producto['precio'],
                    'tipo' => 'devolucion'
                ]);
            }

            // 3. Calcular total de productos nuevos
            $total_nuevo = 0;
            foreach ($productos_nuevos as $producto) {
                $total_nuevo += ($producto['precio'] * $producto['cantidad']);
                $this->validarStock($producto['id_producto'], $producto['cantidad']);
            }

            // 4. Calcular saldo
            $saldo = $total_original - $total_nuevo;

            // 5. Crear registro de cambio
            $id_cambio = $this->crearRegistroCambio([
                'id_venta_original' => $id_venta_original,
                'id_cliente' => $id_cliente,
                'tipo' => 'cambio',
                'monto_saldo' => $saldo,
                'observacion' => $observacion
            ]);

            // 6. Actualizar detalles de cambio con el ID correcto
            $this->actualizarDetallesCambioConId($id_cambio);

            // 7. Manejar saldo según el caso
            if ($saldo > 0) {
                // Saldo a favor del cliente
                $this->guardarSaldoCliente($id_cliente, $saldo);
            } elseif ($saldo < 0) {
                // Cliente debe pagar diferencia - usar saldo existente
                $saldo_absoluto = abs($saldo);
                $this->utilizarSaldoCliente($id_cliente, $saldo_absoluto);
            }

            // 8. Si hay productos nuevos, crear nueva venta
            $id_venta_nueva = null;
            if (!empty($productos_nuevos)) {
                $id_venta_nueva = $this->crearVentaCambio([
                    'id_cliente' => $id_cliente,
                    'productos' => $productos_nuevos,
                    'id_cambio' => $id_cambio,
                    'observacion' => "CAMBIO - Venta original: $id_venta_original - $observacion"
                ]);

                $this->actualizarCambioConNuevaVenta($id_cambio, $id_venta_nueva);

                // Registrar detalles de productos nuevos
                foreach ($productos_nuevos as $producto) {
                    $this->registrarDetalleCambio([
                        'id_cambio' => $id_cambio,
                        'id_producto' => $producto['id_producto'],
                        'cantidad' => $producto['cantidad'],
                        'precio' => $producto['precio'],
                        'tipo' => 'nuevo'
                    ]);
                }
            }

            // 9. Actualizar estado de la venta original
            $this->actualizarEstadoVenta($id_venta_original, 'parcialmente_devuelta');

            $this->db->commit();

            // 10. Generar ticket del cambio
            $this->generarTicketCambio($id_cambio, $id_venta_nueva);

            return [
                'success' => true,
                'id_cambio' => $id_cambio,
                'id_venta_nueva' => $id_venta_nueva,
                'saldo_generado' => $saldo > 0 ? $saldo : 0,
                'saldo_utilizado' => $saldo < 0 ? abs($saldo) : 0,
                'total_devolucion' => $total_original,
                'total_nuevos' => $total_nuevo,
                'saldo_final' => $saldo,
                'nuevo_estado' => 'parcialmente_devuelta',
                'mensaje' => 'Cambio procesado exitosamente'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error en procesarCambioProducto: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function generarTicketCambio($id_cambio, $id_venta_nueva = null)
    {
        try {
            // Obtener información del cambio
            $cambio = $this->obtenerCambioPorId($id_cambio);
            if (!$cambio) {
                error_log("ERROR - No se pudo encontrar el cambio con ID: $id_cambio");
                return false;
            }

            // Log para debugging
            error_log("DEBUG - Ticket cambio generado: ID Cambio $id_cambio, Venta Nueva: " . ($id_venta_nueva ?? 'N/A'));

            // Si hay venta nueva, el ticket normal se generará automáticamente
            // Aquí podrías generar un ticket especial para cambios si lo deseas
            if ($id_venta_nueva) {
                // El ticket de venta normal se generará cuando se acceda a /venta/ticket/{id_venta_nueva}
                error_log("DEBUG - Ticket de venta nueva disponible en: /venta/ticket/$id_venta_nueva");
            }

            return true;
        } catch (Exception $e) {
            error_log("ERROR en generarTicketCambio: " . $e->getMessage());
            return false;
        }
    }

    // 🔹 Método auxiliar para obtener cambio por ID
    private function obtenerCambioPorId($id_cambio)
    {
        $stmt = $this->db->prepare("
        SELECT * FROM cambios_ventas 
        WHERE id_cambio = ?
    ");
        $stmt->bind_param("i", $id_cambio);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // 🔹 Método auxiliar para registrar detalles del cambio
    private function registrarDetalleCambio($datos)
    {
        // Solo registrar si tenemos un ID de cambio válido (0 significa temporal)
        if ($datos['id_cambio'] == 0) {
            return true; // Se actualizará después con el ID correcto
        }

        $stmt = $this->db->prepare("
        INSERT INTO detalles_cambio 
        (id_cambio, id_producto, cantidad, precio, tipo) 
        VALUES (?, ?, ?, ?, ?)
    ");
        $stmt->bind_param(
            "iiids",
            $datos['id_cambio'],
            $datos['id_producto'],
            $datos['cantidad'],
            $datos['precio'],
            $datos['tipo']
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // 🔹 Método para actualizar detalles temporales con ID correcto
    private function actualizarDetallesCambioConId($id_cambio)
    {
        $stmt = $this->db->prepare("
        UPDATE detalles_cambio 
        SET id_cambio = ? 
        WHERE id_cambio = 0
    ");
        $stmt->bind_param("i", $id_cambio);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // 🔹 Procesar devolución completa CORREGIDO
    public function procesarDevolucion($datos_devolucion)
    {
        $id_venta_original = $datos_devolucion['id_venta_original'];
        $id_cliente = $datos_devolucion['id_cliente'];
        $productos_devolver = $datos_devolucion['productos_devolver'];
        $observacion = $datos_devolucion['observacion'] ?? '';

        $this->db->begin_transaction();
        try {
            // 1. Obtener venta original y sus productos
            $venta_original = $this->obtenerVentaPorId($id_venta_original);
            $productos_originales = $this->obtenerDetallesVentaParaCambio($id_venta_original);

            if (!$venta_original) {
                throw new Exception("Venta original no encontrada");
            }

            // 2. Calcular total a devolver y verificar si es devolución total
            $total_devolucion = 0;
            $total_productos_original = 0;
            $productos_devueltos_count = 0;

            foreach ($productos_devolver as $producto) {
                $total_devolucion += ($producto['precio'] * $producto['cantidad']);
                $this->devolverStock($producto['id_producto'], $producto['cantidad']);
                $productos_devueltos_count += $producto['cantidad'];
            }

            foreach ($productos_originales as $producto) {
                $total_productos_original += $producto['cantidad'];
            }

            // 3. Determinar si es devolución total
            $es_devolucion_total = ($productos_devueltos_count >= $total_productos_original);

            // 🔹 CORRECCIÓN: Quitar sello si es devolución total
            if ($es_devolucion_total && $id_cliente) {
                $this->quitarSelloCliente($id_cliente);
            }

            // 4. Guardar saldo para el cliente (reembolso)
            if ($total_devolucion > 0) {
                $this->guardarSaldoCliente($id_cliente, $total_devolucion);
            }

            // 5. Crear registro de devolución
            $id_cambio = $this->crearRegistroCambio([
                'id_venta_original' => $id_venta_original,
                'id_cliente' => $id_cliente,
                'tipo' => 'devolucion',
                'monto_saldo' => $total_devolucion,
                'observacion' => $observacion
            ]);

            // 6. Actualizar estado de la venta original
            $nuevo_estado = $es_devolucion_total ? 'anulada' : 'parcialmente_devuelta';
            $this->actualizarEstadoVenta($id_venta_original, $nuevo_estado);

            $this->db->commit();

            return [
                'success' => true,
                'id_cambio' => $id_cambio,
                'monto_devolucion' => $total_devolucion,
                'nuevo_estado' => $nuevo_estado,
                'es_devolucion_total' => $es_devolucion_total,
                'mensaje' => $es_devolucion_total ?
                    'Devolución total procesada - Venta anulada y sello removido' :
                    'Devolución parcial procesada exitosamente'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error en procesarDevolucion: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // 🔹 NUEVO MÉTODO: Quitar sello del cliente
    private function quitarSelloCliente($id_cliente)
    {
        $stmt = $this->db->prepare("SELECT sellos FROM clientes WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result && $result['sellos'] > 0) {
            $nuevos_sellos = max(0, $result['sellos'] - 1);
            $stmt = $this->db->prepare("UPDATE clientes SET sellos = ? WHERE id_cliente = ?");
            $stmt->bind_param("ii", $nuevos_sellos, $id_cliente);
            $stmt->execute();
            $stmt->close();
            error_log("DEBUG - Sello removido: Cliente $id_cliente, sellos anteriores: {$result['sellos']}, nuevos: $nuevos_sellos");
        }
    }
    // 🔹 Métodos auxiliares para cambios y devoluciones

    private function devolverStock($id_producto, $cantidad)
    {
        $stmt = $this->db->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
        $stmt->bind_param("ii", $cantidad, $id_producto);
        $stmt->execute();
        $stmt->close();
        error_log("DEBUG - Stock devuelto: Producto $id_producto, cantidad $cantidad");
    }

    private function validarStock($id_producto, $cantidad)
    {
        $stmt = $this->db->prepare("SELECT stock, nombre FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            throw new Exception("Producto no encontrado");
        }

        if ($result['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente para {$result['nombre']}. Stock actual: {$result['stock']}, solicitado: $cantidad");
        }
    }
    private function guardarSaldoCliente($id_cliente, $saldo)
    {
        // Verificar si ya existe saldo
        $stmt = $this->db->prepare("SELECT id_saldo, saldo FROM saldos_clientes WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
            // Actualizar saldo existente
            $nuevo_saldo = $result['saldo'] + $saldo;
            $stmt = $this->db->prepare("UPDATE saldos_clientes SET saldo = ?, fecha_actualizacion = NOW() WHERE id_cliente = ?");
            $stmt->bind_param("di", $nuevo_saldo, $id_cliente);
        } else {
            // Crear nuevo saldo - CORREGIDO: usar fecha_actualizacion en lugar de fecha_creacion
            $stmt = $this->db->prepare("INSERT INTO saldos_clientes (id_cliente, saldo, fecha_actualizacion, estado) VALUES (?, ?, NOW(), 'activo')");
            $stmt->bind_param("id", $id_cliente, $saldo);
        }

        $stmt->execute();
        $stmt->close();
        error_log("DEBUG - Saldo actualizado: Cliente $id_cliente, saldo: $saldo");
    }

    private function crearRegistroCambio($datos)
    {
        $stmt = $this->db->prepare("
            INSERT INTO cambios_ventas (id_venta_original, id_cliente, tipo, monto_saldo, observacion, estado) 
            VALUES (?, ?, ?, ?, ?, 'completado')
        ");
        $stmt->bind_param(
            "iisds",
            $datos['id_venta_original'],
            $datos['id_cliente'],
            $datos['tipo'],
            $datos['monto_saldo'],
            $datos['observacion']
        );
        $stmt->execute();
        $id_cambio = $this->db->insert_id;
        $stmt->close();
        return $id_cambio;
    }

    private function crearVentaCambio($datos)
    {
        // Similar a guardarVenta pero para cambios
        $total_venta = 0;
        foreach ($datos['productos'] as $producto) {
            $total_venta += ($producto['precio'] * $producto['cantidad']);
        }

        $id_usuario = $_SESSION['id_usuario'] ?? null;
        $id_turno = $this->getTurnoActivo($id_usuario);

        $stmt = $this->db->prepare("
            INSERT INTO ventas (id_cliente, id_usuario, id_turno, total_venta, metodo_pago, observacion, estado, fecha_venta, es_cambio) 
            VALUES (?, ?, ?, ?, 'saldo', ?, 'completada', NOW(), 1)
        ");
        $stmt->bind_param(
            "iiids",
            $datos['id_cliente'],
            $id_usuario,
            $id_turno,
            $total_venta,
            $datos['observacion']
        );
        $stmt->execute();
        $id_venta = $this->db->insert_id;
        $stmt->close();

        // Guardar detalles de venta
        $this->guardarDetallesVentaCambio($id_venta, $datos['productos']);

        return $id_venta;
    }

    private function guardarDetallesVentaCambio($id_venta, $productos)
    {
        $stmt = $this->db->prepare("
            INSERT INTO detalle_ventas (id_venta, id_producto, tipo_item, cantidad, precio_unitario, descuento) 
            VALUES (?, ?, 'producto', ?, ?, 0)
        ");

        foreach ($productos as $producto) {
            $stmt->bind_param(
                "iiid",
                $id_venta,
                $producto['id_producto'],
                $producto['cantidad'],
                $producto['precio']
            );
            $stmt->execute();

            // Actualizar stock
            $stmt_stock = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
            $stmt_stock->bind_param("ii", $producto['cantidad'], $producto['id_producto']);
            $stmt_stock->execute();
            $stmt_stock->close();
        }

        $stmt->close();
    }

    private function actualizarCambioConNuevaVenta($id_cambio, $id_venta_nueva)
    {
        $stmt = $this->db->prepare("UPDATE cambios_ventas SET id_venta_nueva = ? WHERE id_cambio = ?");
        $stmt->bind_param("ii", $id_venta_nueva, $id_cambio);
        $stmt->execute();
        $stmt->close();
    }

    private function actualizarEstadoVenta($id_venta, $estado)
    {
        $estados_permitidos = ['completada', 'parcialmente_devuelta', 'anulada'];

        if (!in_array($estado, $estados_permitidos)) {
            throw new Exception("Estado de venta no válido: $estado");
        }

        $stmt = $this->db->prepare("UPDATE ventas SET estado = ? WHERE id_venta = ?");
        $stmt->bind_param("si", $estado, $id_venta);
        $stmt->execute();
        $stmt->close();
    }

    // En models/Venta.php
    public function obtenerDetallesVentaParaCambio($id_venta)
    {
        $sql = "SELECT 
                dv.id_detalle_venta,
                dv.id_producto,
                p.nombre,
                dv.cantidad,
                dv.precio_unitario as precio_venta,
                COALESCE(
                    (SELECT SUM(dc.cantidad) 
                     FROM detalles_cambio dc 
                     JOIN cambios_ventas cv ON dc.id_cambio = cv.id_cambio 
                     WHERE dc.id_producto = dv.id_producto 
                     AND cv.id_venta_original = dv.id_venta 
                     AND dc.tipo = 'devolucion'
                     AND cv.estado = 'completado'), 0
                ) as cantidad_devuelta
            FROM detalle_ventas dv
            LEFT JOIN productos p ON dv.id_producto = p.id_producto
            WHERE dv.id_venta = ?
            AND dv.id_producto IS NOT NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_venta);
        $stmt->execute();
        $result = $stmt->get_result();

        $detalles = [];
        while ($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }

        $stmt->close();
        return $detalles;
    }


    // 🔹 Verificar si una venta tiene cambios/devoluciones
    public function tieneCambiosDevoluciones($id_venta)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM cambios_ventas 
            WHERE id_venta_original = ? OR id_venta_nueva = ?
        ");
        $stmt->bind_param("ii", $id_venta, $id_venta);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($result['total'] ?? 0) > 0;
    }

    // 🔹 Obtener ventas con información de cambios 
    public function obtenerTodasConCambios()
    {
        $sql = "SELECT v.id_venta, v.fecha_venta, v.total_venta, v.metodo_pago, v.observacion, 
                   v.id_cliente, v.estado, v.descuento_sellos, v.descuento_manual,
                   IFNULL(c.nombre_cliente, 'Venta genérica') as cliente,
                   u.nombre_completo as cajero,
                   EXISTS(
                       SELECT 1 FROM cambios_ventas cv 
                       WHERE cv.id_venta_original = v.id_venta AND cv.estado = 'completado'
                   ) as tiene_cambios
            FROM ventas v
            LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
            JOIN usuarios u ON v.id_usuario = u.id_usuario
            WHERE v.estado != 'anulada' 
            ORDER BY v.fecha_venta DESC";

        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // 🔹 Obtener clientes activos
    public function obtenerClientesActivos()
    {
        $res = $this->db->query("SELECT id_cliente, nombre_cliente FROM clientes WHERE activo = 1 ORDER BY nombre_cliente ASC");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // 🔹 Obtener turno activo del usuario
    private function getTurnoActivo($id_usuario)
    {
        $stmt = $this->db->prepare("SELECT id_turno FROM turnos_caja WHERE id_usuario = ? AND estado = 'abierto' ORDER BY fecha_apertura DESC LIMIT 1");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res['id_turno'] ?? null;
    }

    // 🔹 Normalizar texto
    private function normalizarTexto($texto)
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $acentos = ['á', 'é', 'í', 'ó', 'ú', 'ä', 'ë', 'ï', 'ö', 'ü', 'à', 'è', 'ì', 'ò', 'ù', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ä', 'Ë', 'Ï', 'Ö', 'Ü', 'À', 'È', 'Ì', 'Ò', 'Ù', 'Ñ'];
        $sinAcentos = ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n'];
        $texto = str_replace($acentos, $sinAcentos, $texto);
        $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }

    // 🔹 Búsqueda combinada de productos y servicios activos
    public function buscarItemsActivos($term)
    {
        $term = $this->normalizarTexto($term);
        $palabras = explode(" ", $term);
        $data = [];

        // --- Productos ---
        $sql_prod = "SELECT id_producto AS id, nombre, precio_venta, stock, 'producto' AS tipo FROM productos WHERE stock>0 AND activo=1";
        $params_prod = [];
        $types_prod = "";
        foreach ($palabras as $p) {
            $sql_prod .= " AND nombre COLLATE utf8mb4_general_ci LIKE ?";
            $params_prod[] = "%$p%";
            $types_prod .= "s";
        }
        $sql_prod .= " LIMIT 10";
        $stmt_prod = $this->db->prepare($sql_prod);
        if (!empty($params_prod)) {
            $a_params = [];
            $a_params[] = &$types_prod;
            foreach ($params_prod as $key => $value) $a_params[] = &$params_prod[$key];
            call_user_func_array([$stmt_prod, 'bind_param'], $a_params);
        }
        $stmt_prod->execute();
        $res_prod = $stmt_prod->get_result();
        while ($row = $res_prod->fetch_assoc()) $data[] = $row;
        $stmt_prod->close();

        // --- Servicios ---
        $sql_serv = "SELECT id_servicio AS id, nombre, precio_venta, 0 AS stock, 'servicio' AS tipo FROM servicios WHERE activo=1";
        $params_serv = [];
        $types_serv = "";
        foreach ($palabras as $p) {
            $sql_serv .= " AND nombre COLLATE utf8mb4_general_ci LIKE ?";
            $params_serv[] = "%$p%";
            $types_serv .= "s";
        }
        $sql_serv .= " LIMIT 10";
        $stmt_serv = $this->db->prepare($sql_serv);
        if (!empty($params_serv)) {
            $a_params = [];
            $a_params[] = &$types_serv;
            foreach ($params_serv as $key => $value) $a_params[] = &$params_serv[$key];
            call_user_func_array([$stmt_serv, 'bind_param'], $a_params);
        }
        $stmt_serv->execute();
        $res_serv = $stmt_serv->get_result();
        while ($row = $res_serv->fetch_assoc()) $data[] = $row;
        $stmt_serv->close();

        // --- Búsqueda difusa si no hay resultados ---
        if (empty($data) && !empty($term)) {
            $todos_prod = $this->db->query("SELECT id_producto AS id, nombre, precio_venta, stock, 'producto' AS tipo FROM productos WHERE stock>0 AND activo=1")->fetch_all(MYSQLI_ASSOC);
            foreach ($todos_prod as $p) {
                if (levenshtein($term, $this->normalizarTexto($p['nombre'])) <= max(strlen($term), strlen($p['nombre'])) / 3) $data[] = $p;
            }
            $todos_serv = $this->db->query("SELECT id_servicio AS id, nombre, precio_venta, 0 AS stock, 'servicio' AS tipo FROM servicios WHERE activo=1")->fetch_all(MYSQLI_ASSOC);
            foreach ($todos_serv as $s) {
                if (levenshtein($term, $this->normalizarTexto($s['nombre'])) <= max(strlen($term), strlen($s['nombre'])) / 3) $data[] = $s;
            }
        }

        return $data;
    }

    public function guardarVenta($datos, $clienteModel = null)
    {
        // 🔹 DEBUG: Log de datos recibidos
        error_log("=== INICIO guardarVenta ===");
        error_log("DEBUG guardarVenta - Datos recibidos: " . json_encode($datos));
        error_log("DEBUG guardarVenta - Sesión usuario: " . ($_SESSION['id_usuario'] ?? 'NO HAY SESIÓN'));

        $id_cliente  = !empty($datos['id_cliente']) ? (int)$datos['id_cliente'] : null;
        $id_usuario  = $_SESSION['id_usuario'] ?? null;
        $id_turno    = $this->getTurnoActivo($id_usuario);
        $metodo_pago = $datos['metodo_pago'] ?? 'efectivo';
        $carrito     = $datos['carrito'] ?? [];
        $descuento_manual = isset($datos['descuento_manual']) ? (float)$datos['descuento_manual'] : 0;
        $observacion = trim($datos['observacion'] ?? '') ?: null;

        error_log("DEBUG - id_cliente: " . ($id_cliente ?? 'NULL'));
        error_log("DEBUG - id_usuario: " . ($id_usuario ?? 'NULL'));
        error_log("DEBUG - id_turno: " . ($id_turno ?? 'NULL'));
        error_log("DEBUG - carrito items: " . count($carrito));

        if (empty($carrito) || !$id_turno || !$id_usuario) {
            $error_msg = "FALLO: Sin carrito, turno activo o usuario (id_turno: " . ($id_turno ?? 'null') . ", id_usuario: " . ($id_usuario ?? 'null') . ")";
            error_log($error_msg);
            throw new Exception($error_msg);
        }

        $total_original = 0;
        $sellos_nuevos = 0;
        $es_generico = empty($id_cliente);

        if (!$es_generico && $clienteModel) {
           
                foreach ($carrito as $item) {
                    if (($item['tipo'] ?? '') === 'producto' && empty($item['es_cambio'])) {
                        $sellos_nuevos = 1;
                        break;
                    }
                }
            }
        

        $descuento_sellos = 0;
        if (!$es_generico && $clienteModel) {
            $resultado_sellos = $clienteModel->procesarSellosVenta($id_cliente, $sellos_nuevos);
            $descuento_sellos = $resultado_sellos['descuento_sellos'] ?? 0;
        }

        foreach ($carrito as $item) {
            $precio = (float)($item['precio'] ?? 0);
            $cantidad = (int)($item['cantidad'] ?? 1);
            $total_original += $precio * $cantidad;
        }

        $total_final = max(0, $total_original - ($total_original * $descuento_sellos) - $descuento_manual);
        error_log("DEBUG - total_original: " . $total_original . ", total_final: " . $total_final);

        $this->db->begin_transaction();
        try {
            error_log("DEBUG - Preparando INSERT en ventas...");

            $stmt_venta = $this->db->prepare("
                INSERT INTO ventas (
                    id_cliente, id_usuario, id_turno, total_venta,
                    descuento_sellos, descuento_manual, metodo_pago,
                    observacion, estado, fecha_venta
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completada', NOW())
            ");

            if (!$stmt_venta) {
                throw new Exception("Error preparando statement de venta: " . $this->db->error);
            }

            $stmt_venta->bind_param(
                "iiidddss",
                $id_cliente,
                $id_usuario,
                $id_turno,
                $total_final,
                $descuento_sellos,
                $descuento_manual,
                $metodo_pago,
                $observacion
            );

            $execute_result = $stmt_venta->execute();
            if (!$execute_result) {
                throw new Exception("Error ejecutando inserción de venta: " . $stmt_venta->error);
            }

            $id_venta = $this->db->insert_id;
            $stmt_venta->close();

            error_log("DEBUG - Venta insertada con ID: " . $id_venta);

            // Guardar detalles de venta
            $stmt_detalle = $this->db->prepare("
                INSERT INTO detalle_ventas (id_venta, id_producto, id_servicio, tipo_item, cantidad, precio_unitario, descuento)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt_detalle) {
                throw new Exception("Error preparando statement de detalle: " . $this->db->error);
            }

            $stmt_stock = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
            if (!$stmt_stock) {
                throw new Exception("Error preparando statement de stock: " . $this->db->error);
            }

            foreach ($carrito as $item) {
                $id_item  = (int)($item['id'] ?? 0);
                $precio   = (float)($item['precio'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 1);
                $tipo     = $item['tipo'] ?? 'producto';
                $descuento_item = 0.00;

                $id_producto_db = ($tipo === 'producto') ? $id_item : null;
                $id_servicio_db = ($tipo === 'servicio') ? $id_item : null;

                // Validar stock
                if ($tipo === 'producto') {
                    $res_stock = $this->db->query("SELECT stock, nombre FROM productos WHERE id_producto = $id_item")->fetch_assoc();
                    if (!$res_stock) {
                        throw new Exception("Producto con ID $id_item no encontrado");
                    }
                    if ($res_stock['stock'] < $cantidad) {
                        throw new Exception("Stock insuficiente para {$res_stock['nombre']}. Stock actual: {$res_stock['stock']}, solicitado: $cantidad");
                    }
                    error_log("DEBUG - Stock válido para producto ID $id_item: {$res_stock['stock']} >= $cantidad");
                }

                $stmt_detalle->bind_param("iiisidd", $id_venta, $id_producto_db, $id_servicio_db, $tipo, $cantidad, $precio, $descuento_item);
                $execute_detalle = $stmt_detalle->execute();
                if (!$execute_detalle) {
                    throw new Exception("Error insertando detalle: " . $stmt_detalle->error);
                }

                if ($tipo === 'producto') {
                    $stmt_stock->bind_param("ii", $cantidad, $id_item);
                    $execute_stock = $stmt_stock->execute();
                    if (!$execute_stock) {
                        throw new Exception("Error actualizando stock: " . $stmt_stock->error);
                    }
                }

                error_log("DEBUG - Detalle insertado: $tipo ID $id_item, cantidad $cantidad");
            }

            $stmt_detalle->close();
            $stmt_stock->close();

            $this->db->commit();
            error_log("=== TRANSACCIÓN COMPLETADA EXITOSAMENTE ===");

            return [
                'id_venta' => $id_venta,
                'id_cliente' => $id_cliente,
                'descuento_sellos' => $descuento_sellos,
                'descuento_manual' => $descuento_manual,
                'sellos_nuevos' => $sellos_nuevos,
                'metodo_pago' => $metodo_pago,
                'observacion' => $observacion,
                'total_final' => $total_final
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("=== ERROR EN TRANSACCIÓN ===");
            error_log("Error en guardarVenta: " . $e->getMessage()
                . " | Error DB: " . ($this->db->error ?? 'N/A'));
            throw $e;
        }
    }


    // 🔹 Obtener todas las ventas
    public function obtenerTodas()
    {
        $sql = "SELECT v.id_venta, v.fecha_venta, v.total_venta, v.metodo_pago, v.observacion,
                   v.id_cliente, v.estado, v.descuento_sellos, v.descuento_manual,
                   IFNULL(c.nombre_cliente, 'Venta genérica') as cliente,
                   u.nombre_completo as cajero
            FROM ventas v
            LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
            JOIN usuarios u ON v.id_usuario = u.id_usuario
            WHERE v.estado = 'completada'
            ORDER BY v.fecha_venta DESC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // 🔹 Obtener venta por ID
    public function obtenerVentaPorId($id_venta)
    {
        $sql = "SELECT v.*, c.nombre_cliente, u.nombre_completo as cajero
                FROM ventas v
                LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                JOIN usuarios u ON v.id_usuario = u.id_usuario
                WHERE v.id_venta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_venta);
        $stmt->execute();
        $venta = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $venta ?: [];
    }

    // 🔹 Obtener detalles por venta
    public function obtenerDetallesPorIdVenta($id_venta)
    {
        $sql = "SELECT d.*, 
                       p.nombre as producto_nombre, 
                       s.nombre as servicio_nombre,
                       CASE 
                           WHEN d.tipo_item = 'producto' THEN p.nombre
                           WHEN d.tipo_item = 'servicio' THEN s.nombre
                           ELSE 'Item Desconocido'
                       END as nombre_item,
                       (d.cantidad * d.precio_unitario - d.descuento) as subtotal
                FROM detalle_ventas d
                LEFT JOIN productos p ON d.id_producto = p.id_producto
                LEFT JOIN servicios s ON d.id_servicio = s.id_servicio
                WHERE d.id_venta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_venta);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    // 🔹 Resumen por fechas
    public function getResumenVentas($fecha_inicio, $fecha_fin)
    {
        $sql = "SELECT 
                COUNT(*) AS num_ventas,
                IFNULL(SUM(total_venta),0) AS total_ingresos
            FROM ventas
            WHERE estado='completada'
              AND fecha_venta BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: ['num_ventas' => 0, 'total_ingresos' => 0];
    }

    // 🔹 Top 5 productos vendidos
    public function getTopProductos($fecha_inicio, $fecha_fin)
    {
        $sql = "
        SELECT 
            p.nombre, 
            SUM(d.cantidad) AS total_cantidad
        FROM detalle_ventas d
        INNER JOIN ventas v ON d.id_venta = v.id_venta
        INNER JOIN productos p ON d.id_producto = p.id_producto
        WHERE 
            v.estado = 'completada'
            AND d.tipo_item = 'producto'
            AND v.fecha_venta BETWEEN ? AND ?
        GROUP BY p.id_producto
        ORDER BY total_cantidad DESC
        LIMIT 5
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    // En models/Venta.php - REEMPLAZAR el método completo
    public function getResumenVentasDetalladoPorTurno($id_turno)
    {
        // 🔹 PRIMERO: Obtener métodos de pago CORRECTAMENTE
        $sql_metodos_pago = "
    SELECT 
        COUNT(v.id_venta) AS num_ventas,
        IFNULL(SUM(v.total_venta), 0) AS total_ingresos,
        -- Métodos de pago ESPECÍFICOS para tu sistema
        IFNULL(SUM(CASE WHEN v.metodo_pago = 'efectivo' THEN v.total_venta ELSE 0 END), 0) AS efectivo,
        IFNULL(SUM(CASE WHEN v.metodo_pago = 'yape' THEN v.total_venta ELSE 0 END), 0) AS yape,
        IFNULL(SUM(CASE WHEN v.metodo_pago = 'plin' THEN v.total_venta ELSE 0 END), 0) AS plin,
        IFNULL(SUM(CASE WHEN v.metodo_pago = 'agora' THEN v.total_venta ELSE 0 END), 0) AS agora,
        IFNULL(SUM(CASE WHEN v.metodo_pago = 'transferencia' THEN v.total_venta ELSE 0 END), 0) AS transferencia
    FROM ventas v
    WHERE v.estado = 'completada' AND v.id_turno = ?
    ";

        $stmt = $this->db->prepare($sql_metodos_pago);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $metodos_pago = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 🔹 SEGUNDO: Obtener costos y ganancias
        $sql_costos = "
    SELECT 
        -- Ventas brutas (precio venta total)
        IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) AS total_bruto,
        -- Ventas netas (según precio compra)
        IFNULL(SUM(dv.cantidad * p.precio_compra), 0) AS total_neto,
        -- Margen de ganancia (bruto - neto)
        IFNULL(SUM((dv.cantidad * dv.precio_unitario) - (dv.cantidad * p.precio_compra)), 0) AS ganancia
    FROM ventas v
    INNER JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
    LEFT JOIN productos p ON dv.id_producto = p.id_producto
    WHERE v.estado = 'completada' AND v.id_turno = ?
    ";

        $stmt = $this->db->prepare($sql_costos);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $costos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 🔹 COMBINAR resultados asegurando valores numéricos
        return [
            'num_ventas'      => (int)($metodos_pago['num_ventas'] ?? 0),
            'total_ingresos'  => (float)($metodos_pago['total_ingresos'] ?? 0),
            'efectivo'        => (float)($metodos_pago['efectivo'] ?? 0),
            'yape'            => (float)($metodos_pago['yape'] ?? 0),
            'plin'            => (float)($metodos_pago['plin'] ?? 0),
            'agora'           => (float)($metodos_pago['agora'] ?? 0),
            'transferencia'   => (float)($metodos_pago['transferencia'] ?? 0),
            'total_bruto'     => (float)($costos['total_bruto'] ?? 0),
            'total_neto'      => (float)($costos['total_neto'] ?? 0),
            'ganancia'        => (float)($costos['ganancia'] ?? 0)
        ];
    }


    // 🔹 Reporte detallado de caja
    public function getReporteCajaDetallado($fecha, $id_turno = '')
    {
        // Ventas del día
        $sql_ventas = "SELECT 
        COUNT(*) as num_ventas,
        SUM(total_venta) as total_ventas,
        SUM(CASE WHEN metodo_pago = 'efectivo' THEN total_venta ELSE 0 END) as efectivo,
        SUM(CASE WHEN metodo_pago = 'yape' THEN total_venta ELSE 0 END) as yape,
        SUM(CASE WHEN metodo_pago = 'plin' THEN total_venta ELSE 0 END) as plin,
        SUM(CASE WHEN metodo_pago = 'transferencia' THEN total_venta ELSE 0 END) as transferencia,
        SUM(CASE WHEN metodo_pago = 'saldo' THEN total_venta ELSE 0 END) as saldo
    FROM ventas 
    WHERE estado = 'completada' 
    AND DATE(fecha_venta) = ?";

        $params = [$fecha];

        if (!empty($id_turno)) {
            $sql_ventas .= " AND id_turno = ?";
            $params[] = $id_turno;
        }

        $stmt = $this->db->prepare($sql_ventas);
        if (!empty($id_turno)) {
            $stmt->bind_param("si", $fecha, $id_turno);
        } else {
            $stmt->bind_param("s", $fecha);
        }
        $stmt->execute();
        $ventas = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Movimientos de caja
        $sql_movimientos = "SELECT 
        SUM(CASE WHEN tipo_movimiento = 'ingreso' THEN monto ELSE 0 END) as ingresos_caja,
        SUM(CASE WHEN tipo_movimiento = 'egreso' THEN monto ELSE 0 END) as egresos_caja
    FROM movimientos_caja mc
    JOIN turnos_caja tc ON mc.id_turno = tc.id_turno
    WHERE DATE(tc.fecha_apertura) = ?";

        if (!empty($id_turno)) {
            $sql_movimientos .= " AND tc.id_turno = ?";
        }

        $stmt = $this->db->prepare($sql_movimientos);
        if (!empty($id_turno)) {
            $stmt->bind_param("si", $fecha, $id_turno);
        } else {
            $stmt->bind_param("s", $fecha);
        }
        $stmt->execute();
        $movimientos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'ventas' => $ventas ?: [
                'num_ventas' => 0,
                'total_ventas' => 0,
                'efectivo' => 0,
                'yape' => 0,
                'plin' => 0,
                'transferencia' => 0,
                'saldo' => 0
            ],
            'movimientos' => $movimientos ?: ['ingresos_caja' => 0, 'egresos_caja' => 0],
            'fecha' => $fecha
        ];
    }
   
    public function obtenerSaldoCliente($id_cliente)
    {
        $stmt = $this->db->prepare("SELECT saldo FROM saldos_clientes WHERE id_cliente = ? AND estado = 'activo'");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ? $result['saldo'] : 0;
    }

    public function utilizarSaldoCliente($id_cliente, $monto_utilizar)
    {
        $this->db->begin_transaction();
        try {
            // Verificar saldo disponible
            $saldo_actual = $this->obtenerSaldoCliente($id_cliente);

            if ($saldo_actual < $monto_utilizar) {
                throw new Exception("Saldo insuficiente. Saldo actual: S/. $saldo_actual, intento usar: S/. $monto_utilizar");
            }

            // Actualizar saldo y estado
            $stmt = $this->db->prepare("UPDATE saldos_clientes SET saldo = saldo - ?, fecha_actualizacion = NOW(), estado = 'utilizado' WHERE id_cliente = ?");
            $stmt->bind_param("di", $monto_utilizar, $id_cliente);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    // 🔹 Obtener historial de cambios de un cliente
    public function obtenerHistorialCambios($id_cliente)
    {
        $sql = "SELECT 
                cv.id_cambio,
                cv.id_venta_original,
                cv.id_venta_nueva,
                cv.tipo,
                cv.monto_saldo,
                cv.estado,
                cv.observacion,
                cv.fecha_cambio,
                vo.id_venta as venta_original,
                vn.id_venta as venta_nueva
            FROM cambios_ventas cv
            LEFT JOIN ventas vo ON cv.id_venta_original = vo.id_venta
            LEFT JOIN ventas vn ON cv.id_venta_nueva = vn.id_venta
            WHERE cv.id_cliente = ?
            ORDER BY cv.fecha_cambio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $result;
    }
    


// En models/Venta.php - Agregar estos métodos para reportes

/**
 * 🔹 Obtener distribución de métodos de pago
 */
public function obtenerDistribucionMetodosPago($fecha_inicio, $fecha_fin)
{
    $sql = "
        SELECT 
            metodo_pago,
            COUNT(*) as total_ventas,
            SUM(total_venta) as monto_total,
            ROUND((SUM(total_venta) / (SELECT SUM(total_venta) FROM ventas 
                   WHERE estado = 'completada' 
                   AND DATE(fecha_venta) BETWEEN ? AND ?)) * 100, 2) as porcentaje
        FROM ventas 
        WHERE estado = 'completada' 
        AND DATE(fecha_venta) BETWEEN ? AND ?
        GROUP BY metodo_pago 
        ORDER BY monto_total DESC
    ";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ssss", $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener ventas por método de pago (para gráficos)
 */
public function obtenerVentasPorMetodoPago($fecha_inicio, $fecha_fin)
{
    $sql = "
        SELECT 
            metodo_pago,
            DATE(fecha_venta) as fecha,
            SUM(total_venta) as total_diario
        FROM ventas 
        WHERE estado = 'completada' 
        AND DATE(fecha_venta) BETWEEN ? AND ?
        GROUP BY metodo_pago, DATE(fecha_venta)
        ORDER BY fecha, metodo_pago
    ";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener tendencias de métodos de pago
 */
public function obtenerTendenciasMetodosPago()
{
    $sql = "
        SELECT 
            YEAR(fecha_venta) as año,
            MONTH(fecha_venta) as mes,
            metodo_pago,
            COUNT(*) as total_ventas,
            SUM(total_venta) as monto_total
        FROM ventas 
        WHERE estado = 'completada'
        AND fecha_venta >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(fecha_venta), MONTH(fecha_venta), metodo_pago
        ORDER BY año DESC, mes DESC, monto_total DESC
    ";
    
    $result = $this->db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener top productos por cantidad
 */
public function obtenerTopProductosPorCantidad($fecha_inicio, $fecha_fin, $limite = 10)
{
    $sql = "SELECT 
                p.id_producto,
                p.nombre,
                p.descripcion,
                p.precio_venta,
                p.precio_compra,
                SUM(dv.cantidad) as total_vendido,
                SUM(dv.cantidad * dv.precio_unitario) as revenue_total,
                SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) as ganancia_total
            FROM detalle_ventas dv
            JOIN productos p ON dv.id_producto = p.id_producto
            JOIN ventas v ON dv.id_venta = v.id_venta
            WHERE v.estado = 'completada'
                AND DATE(v.fecha_venta) BETWEEN ? AND ?
                AND dv.tipo_item = 'producto'
            GROUP BY p.id_producto, p.nombre, p.descripcion, p.precio_venta, p.precio_compra
            ORDER BY total_vendido DESC
            LIMIT ?";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ssi", $fecha_inicio, $fecha_fin, $limite);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener top productos por revenue
 */
public function obtenerTopProductosPorRevenue($fecha_inicio, $fecha_fin, $limite = 10)
{
    $sql = "SELECT 
                p.id_producto,
                p.nombre,
                p.descripcion,
                p.precio_venta,
                p.precio_compra,
                SUM(dv.cantidad) as total_vendido,
                SUM(dv.cantidad * dv.precio_unitario) as revenue_total,
                SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) as ganancia_total
            FROM detalle_ventas dv
            JOIN productos p ON dv.id_producto = p.id_producto
            JOIN ventas v ON dv.id_venta = v.id_venta
            WHERE v.estado = 'completada'
                AND DATE(v.fecha_venta) BETWEEN ? AND ?
                AND dv.tipo_item = 'producto'
            GROUP BY p.id_producto, p.nombre, p.descripcion, p.precio_venta, p.precio_compra
            ORDER BY revenue_total DESC
            LIMIT ?";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ssi", $fecha_inicio, $fecha_fin, $limite);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener rentabilidad general
 */
public function obtenerRentabilidadGeneral($fecha_inicio, $fecha_fin)
{
    $sql = "SELECT 
                COUNT(*) as total_ventas,
                SUM(v.total_venta) as ingresos_totales,
                SUM(dv.cantidad * p.precio_compra) as costos_totales,
                SUM(v.total_venta) - SUM(dv.cantidad * p.precio_compra) as ganancia_neta,
                CASE 
                    WHEN SUM(v.total_venta) > 0 
                    THEN ROUND(((SUM(v.total_venta) - SUM(dv.cantidad * p.precio_compra)) / SUM(v.total_venta) * 100), 2)
                    ELSE 0 
                END as margen_global
            FROM ventas v
            JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
            JOIN productos p ON dv.id_producto = p.id_producto
            WHERE v.estado = 'completada'
                AND DATE(v.fecha_venta) BETWEEN ? AND ?
                AND dv.tipo_item = 'producto'";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return [
        'total_ventas' => $result['total_ventas'] ?? 0,
        'ingresos_totales' => $result['ingresos_totales'] ?? 0,
        'costos_totales' => $result['costos_totales'] ?? 0,
        'ganancia_neta' => $result['ganancia_neta'] ?? 0,
        'margen_global' => $result['margen_global'] ?? 0
    ];
}

/**
 * 🔹 Obtener productos más rentables
 */
public function obtenerProductosMasRentables($fecha_inicio, $fecha_fin, $limite = 10)
{
    $sql = "SELECT 
                p.id_producto,
                p.nombre,
                p.precio_compra,
                p.precio_venta,
                ROUND(((p.precio_venta - p.precio_compra) / p.precio_venta * 100), 2) as margen_porcentaje,
                COALESCE(SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)), 0) as ganancia_total,
                COALESCE(SUM(dv.cantidad), 0) as total_vendido
            FROM productos p
            LEFT JOIN detalle_ventas dv ON p.id_producto = dv.id_producto
            LEFT JOIN ventas v ON dv.id_venta = v.id_venta 
                AND v.estado = 'completada'
                AND DATE(v.fecha_venta) BETWEEN ? AND ?
            WHERE p.activo = 1 
                AND p.precio_compra > 0
            GROUP BY p.id_producto, p.nombre, p.precio_compra, p.precio_venta
            HAVING ganancia_total > 0
            ORDER BY ganancia_total DESC
            LIMIT ?";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ssi", $fecha_inicio, $fecha_fin, $limite);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener ventas por fecha con filtros
 */
public function obtenerVentasPorFecha($fecha_inicio, $fecha_fin, $metodo_pago = '')
{
    $sql = "
        SELECT 
            v.id_venta,
            v.fecha_venta,
            COALESCE(c.nombre_cliente, CONCAT('Cliente #', v.numero_cliente)) AS cliente,
            v.total_venta,
            v.metodo_pago,
            v.estado,
            v.descuento_sellos,
            v.descuento_manual,
            u.nombre_completo AS vendedor,
            COUNT(dv.id_detalle_venta) AS num_items
        FROM ventas v
        LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
        INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
        LEFT JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
        WHERE v.estado = 'completada' 
        AND DATE(v.fecha_venta) BETWEEN ? AND ?
    ";

    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";

    if (!empty($metodo_pago)) {
        $sql .= " AND v.metodo_pago = ?";
        $params[] = $metodo_pago;
        $types .= "s";
    }

    $sql .= " GROUP BY v.id_venta ORDER BY v.fecha_venta DESC";

    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la consulta: " . $this->db->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener resumen de ventas por método de pago
 */
public function obtenerResumenVentasPorMetodoPago($fecha_inicio, $fecha_fin)
{
    $sql = "
        SELECT 
            metodo_pago,
            COUNT(*) as num_ventas,
            SUM(total_venta) as total_ventas,
            AVG(total_venta) as promedio_venta
        FROM ventas 
        WHERE estado = 'completada' 
        AND DATE(fecha_venta) BETWEEN ? AND ?
        GROUP BY metodo_pago
        ORDER BY total_ventas DESC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * 🔹 Obtener ventas diarias con resumen por método de pago
 */
public function obtenerVentasDiarias($fecha_inicio, $fecha_fin)
{
    $sql = "
        SELECT 
            DATE(fecha_venta) as fecha,
            COUNT(*) as num_ventas,
            SUM(total_venta) as total_dia,
            SUM(CASE WHEN metodo_pago = 'efectivo' THEN total_venta ELSE 0 END) as efectivo,
            SUM(CASE WHEN metodo_pago = 'yape' THEN total_venta ELSE 0 END) as yape,
            SUM(CASE WHEN metodo_pago = 'plin' THEN total_venta ELSE 0 END) as plin,
            SUM(CASE WHEN metodo_pago = 'transferencia' THEN total_venta ELSE 0 END) as transferencia,
            SUM(CASE WHEN metodo_pago = 'saldo' THEN total_venta ELSE 0 END) as saldo
        FROM ventas 
        WHERE estado = 'completada' 
        AND DATE(fecha_venta) BETWEEN ? AND ?
        GROUP BY DATE(fecha_venta)
        ORDER BY fecha DESC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}






}
