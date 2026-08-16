<?php
class Compra
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    public function obtenerTodas()
    {
        $sql = "SELECT c.*, p.nombre_proveedor, a.nombre as nombre_almacen
                FROM compras c
                LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                LEFT JOIN almacenes a ON c.id_almacen = a.id
                ORDER BY c.fecha_compra DESC";
        return $this->db->query($sql);
    }

    public function obtenerTodasArray()
    {
        $sql = "SELECT c.id_compra, p.nombre_proveedor, c.fecha_compra, c.total_compra, c.estado
            FROM compras c
            LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
            ORDER BY c.fecha_compra DESC";
        $res = $this->db->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerProveedoresActivos()
    {
        return $this->db->query("SELECT id_proveedor, nombre_proveedor FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC");
    }
    
    public function obtenerPorRangoFechas($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->db->prepare("SELECT c.id_compra, pr.nombre_proveedor, c.fecha_compra, c.total_compra, c.estado 
                                FROM compras c
                                JOIN proveedores pr ON c.id_proveedor = pr.id_proveedor
                                WHERE DATE(c.fecha_compra) BETWEEN ? AND ?
                                ORDER BY c.fecha_compra ASC");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // En models/Compra.php
    public function buscarProductos($term, $id_almacen = null)
    {
        $searchTerm = "%{$term}%";

        if ($id_almacen) {
            // Buscar productos en un almacén específico
            $stmt = $this->db->prepare("
            SELECT p.id_producto, p.nombre, p.precio_compra, p.id_almacen 
            FROM productos p 
            WHERE (p.nombre LIKE ? OR p.codigo_barra LIKE ?) 
            AND p.activo = 1 
            AND (p.id_almacen = ? OR p.id_almacen IS NULL)
            LIMIT 10
        ");
            $stmt->bind_param("ssi", $searchTerm, $searchTerm, $id_almacen);
        } else {
            // Buscar en todos los almacenes
            $stmt = $this->db->prepare("
            SELECT p.id_producto, p.nombre, p.precio_compra, p.id_almacen 
            FROM productos p 
            WHERE (p.nombre LIKE ? OR p.codigo_barra LIKE ?) 
            AND p.activo = 1 
            LIMIT 10
        ");
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    public function guardarCompra($datos)
    {
        $id_proveedor = (int)$datos['id_proveedor'];
        $id_almacen = (int)$datos['id_almacen'];
        $total_compra = (float)$datos['total_compra'];
        $productos_compra = $datos['productos_compra'];
        $id_usuario = $_SESSION['id_usuario'];

        if (empty($productos_compra) || empty($id_proveedor) || empty($id_almacen)) {
            return false;
        }

        $this->db->begin_transaction();
        try {
            $stmt_compra = $this->db->prepare("INSERT INTO compras (id_proveedor, id_almacen, id_usuario, total_compra, estado) VALUES (?, ?, ?, ?, 'solicitada')");
            $stmt_compra->bind_param("iiid", $id_proveedor, $id_almacen, $id_usuario, $total_compra);
            $stmt_compra->execute();
            $id_compra = $this->db->insert_id;
            $stmt_compra->close();

            $stmt_detalle = $this->db->prepare("INSERT INTO detalle_compras (id_compra, id_producto, cantidad, costo_unitario) VALUES (?, ?, ?, ?)");
            foreach ($productos_compra as $producto) {
                $stmt_detalle->bind_param("iiid", $id_compra, $producto['id'], $producto['cantidad'], $producto['costo']);
                $stmt_detalle->execute();
            }
            $stmt_detalle->close();
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            // Para depuración, podrías registrar el error: error_log($e->getMessage());
            return false;
        }
    }
    
    public function recibirCompra($id_compra)
    {
        error_log("=== [MODELO] INICIANDO recibirCompra para ID: $id_compra ===");
        error_log("Hora: " . date('Y-m-d H:i:s'));
        error_log("Usuario ID: " . ($_SESSION['id_usuario'] ?? 'No disponible'));

        // 1. Verificar que la compra existe y está en estado 'solicitada'
        $verificar_compra_stmt = $this->db->prepare("
        SELECT id_compra, id_almacen, id_proveedor, total_compra 
        FROM compras 
        WHERE id_compra = ? AND estado = 'solicitada'
    ");

        if (!$verificar_compra_stmt) {
            error_log("[ERROR] Error en prepare de verificar_compra: " . $this->db->error);
            return false;
        }

        $verificar_compra_stmt->bind_param("i", $id_compra);
        $verificar_compra_stmt->execute();
        $resultado = $verificar_compra_stmt->get_result();
        $compra_info = $resultado->fetch_assoc();
        $verificar_compra_stmt->close();

        error_log("[INFO] Información de compra obtenida: " . print_r($compra_info, true));

        if (!$compra_info) {
            error_log("[ERROR] Compra no encontrada o ya recibida - ID: $id_compra");

            // Verificar qué estado tiene realmente
            $check_stmt = $this->db->prepare("SELECT estado FROM compras WHERE id_compra = ?");
            $check_stmt->bind_param("i", $id_compra);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            error_log("[INFO] Estado actual de compra $id_compra: " . ($check_result['estado'] ?? 'No existe'));
            return false;
        }

        $id_almacen_destino = $compra_info['id_almacen'];
        error_log("[INFO] ID Almacén destino: $id_almacen_destino");

        // 2. Obtener detalles de la compra
        $detalles_stmt = $this->db->prepare("
        SELECT dc.id_producto, dc.cantidad, dc.costo_unitario, 
               p.nombre, p.descripcion, p.id_categoria, p.precio_venta
        FROM detalle_compras dc
        LEFT JOIN productos p ON dc.id_producto = p.id_producto
        WHERE dc.id_compra = ?
    ");

        if (!$detalles_stmt) {
            error_log("[ERROR] Error en prepare de detalles: " . $this->db->error);
            return false;
        }

        $detalles_stmt->bind_param("i", $id_compra);
        $detalles_stmt->execute();
        $detalles_result = $detalles_stmt->get_result();
        $detalles = $detalles_result->fetch_all(MYSQLI_ASSOC);
        $detalles_stmt->close();

        error_log("[INFO] Detalles encontrados: " . count($detalles));
        error_log("[DEBUG] Detalles: " . print_r($detalles, true));

        if (empty($detalles)) {
            error_log("[ERROR] No hay detalles para la compra ID: $id_compra");
            return false;
        }

        // 3. INICIAR TRANSACCIÓN
        error_log("[TRANSACCIÓN] Iniciando transacción...");
        $this->db->begin_transaction();

        try {
            foreach ($detalles as $index => $detalle) {
                error_log("[PRODUCTO {$index}] Procesando ID={$detalle['id_producto']}, Cantidad={$detalle['cantidad']}, Costo={$detalle['costo_unitario']}");

                $id_producto = $detalle['id_producto'];
                $cantidad = $detalle['cantidad'];
                $costo_unitario = $detalle['costo_unitario'];
                $stock_anterior = 0; // Inicializar

                // 4. Verificar si el producto existe en el almacén destino
                $check_product_stmt = $this->db->prepare("
                SELECT id_producto, stock, precio_compra, precio_venta 
                FROM productos 
                WHERE id_producto = ? AND id_almacen = ?
            ");

                if (!$check_product_stmt) {
                    throw new Exception("Error prepare check_product: " . $this->db->error);
                }

                $check_product_stmt->bind_param("ii", $id_producto, $id_almacen_destino);
                $check_product_stmt->execute();
                $producto_existente = $check_product_stmt->get_result()->fetch_assoc();
                $check_product_stmt->close();

                error_log("[PRODUCTO {$index}] Producto existente: " . print_r($producto_existente, true));

                if ($producto_existente) {
                    // Producto existe - actualizar stock
                    error_log("[PRODUCTO {$index}] EXISTE en almacén $id_almacen_destino");

                    $stock_anterior = (int)$producto_existente['stock'];
                    $stock_nuevo = $stock_anterior + $cantidad;

                    error_log("[PRODUCTO {$index}] Stock anterior: $stock_anterior, Stock nuevo: $stock_nuevo");

                    // Calcular nuevo precio de compra promedio
                    $costo_total_anterior = (float)$producto_existente['precio_compra'] * $stock_anterior;
                    $costo_total_nuevo = (float)$costo_unitario * $cantidad;

                    // Evitar división por cero
                    if ($stock_nuevo > 0) {
                        $nuevo_precio_compra = ($costo_total_anterior + $costo_total_nuevo) / $stock_nuevo;
                    } else {
                        $nuevo_precio_compra = $costo_unitario;
                    }

                    error_log("[PRODUCTO {$index}] Nuevo precio compra: $nuevo_precio_compra");

                    // Actualizar producto
                    $update_stmt = $this->db->prepare("
                    UPDATE productos 
                    SET stock = ?, precio_compra = ?, fecha_actualizacion = CURRENT_TIMESTAMP 
                    WHERE id_producto = ? AND id_almacen = ?
                ");

                    if (!$update_stmt) {
                        throw new Exception("Error prepare update: " . $this->db->error);
                    }

                    $update_stmt->bind_param("ddii", $stock_nuevo, $nuevo_precio_compra, $id_producto, $id_almacen_destino);
                    $update_result = $update_stmt->execute();

                    if (!$update_result) {
                        throw new Exception("Error execute update: " . $update_stmt->error);
                    }

                    $update_stmt->close();
                    error_log("[PRODUCTO {$index}] Actualizado correctamente");
                } else {
                    // Producto NO existe - crear nuevo
                    error_log("[PRODUCTO {$index}] NO existe en almacén $id_almacen_destino. Creando...");

                    $nombre = $detalle['nombre'] ?? "Producto ID: $id_producto";
                    $descripcion = $detalle['descripcion'] ?? "Importado por compra ID: $id_compra";
                    $id_categoria = $detalle['id_categoria'] ?? null;
                    $precio_venta = $detalle['precio_venta'] ?? ($costo_unitario * 1.3);

                    // Insertar nuevo producto en el almacén destino
                    $insert_stmt = $this->db->prepare("
                    INSERT INTO productos 
                    (nombre, descripcion, id_categoria, id_almacen, 
                     precio_compra, precio_venta, stock, activo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");

                    if (!$insert_stmt) {
                        throw new Exception("Error prepare insert: " . $this->db->error);
                    }

                    $insert_stmt->bind_param(
                        "ssiiddi",
                        $nombre,
                        $descripcion,
                        $id_categoria,
                        $id_almacen_destino,
                        $costo_unitario,
                        $precio_venta,
                        $cantidad
                    );

                    $insert_result = $insert_stmt->execute();

                    if (!$insert_result) {
                        throw new Exception("Error execute insert: " . $insert_stmt->error);
                    }

                    $insert_stmt->close();

                    $stock_anterior = 0;
                    $stock_nuevo = $cantidad;

                    error_log("[PRODUCTO {$index}] Creado exitosamente con stock: $stock_nuevo");
                }

                // 5. Registrar movimiento en kardex (si existe la tabla)
                if ($this->tieneTablaMovimientos()) {
                    $kardex_stmt = $this->db->prepare("
                        INSERT INTO movimientos_inventario 
                        (id_producto, id_almacen, tipo_movimiento, cantidad, 
                         stock_anterior, stock_nuevo, id_usuario, referencia_id) 
                        VALUES (?, ?, 'compra', ?, ?, ?, ?, ?)
                    ");

                    if ($kardex_stmt) {
                        // Asegurar que los valores no sean null
                        $id_usuario_kardex = $_SESSION['id_usuario'] ?? 0;
                        $stock_anterior_valor = $stock_anterior ?? 0;
                        
                        error_log("[DEBUG Kardex] Parámetros: producto=$id_producto, almacen=$id_almacen_destino, cantidad=$cantidad, stock_ant=$stock_anterior_valor, stock_nuevo=$stock_nuevo, usuario=$id_usuario_kardex, compra=$id_compra");
                        error_log("[DEBUG Kardex] Número de parámetros esperados: 7 (no 8, porque 'compra' es un valor fijo)");
                        
                        // CORRECCIÓN: Solo 7 parámetros (el 3ro es 'compra' como valor fijo, no como ?)
                        $kardex_stmt->bind_param(
                            "iiiiiii",  // ← 7 "i" para 7 parámetros
                            $id_producto,           // 1
                            $id_almacen_destino,    // 2
                            $cantidad,              // 3
                            $stock_anterior_valor,  // 4
                            $stock_nuevo,           // 5
                            $id_usuario_kardex,     // 6
                            $id_compra              // 7
                        );
                        
                        $kardex_result = $kardex_stmt->execute();
                        
                        if (!$kardex_result) {
                            error_log("[ERROR Kardex] " . $kardex_stmt->error);
                            // No lanzar excepción aquí para no interrumpir el flujo
                        } else {
                            error_log("[PRODUCTO {$index}] Movimiento registrado en kardex");
                        }
                        
                        $kardex_stmt->close();
                    } else {
                        error_log("[ERROR] Falló prepare para kardex: " . $this->db->error);
                    }
                }
            }

            // 6. Marcar compra como recibida
            error_log("[ACTUALIZACIÓN] Marcando compra como recibida...");

            $compra_update_stmt = $this->db->prepare("
            UPDATE compras 
            SET estado = 'recibida', fecha_recepcion = CURRENT_TIMESTAMP 
            WHERE id_compra = ?
        ");

            if (!$compra_update_stmt) {
                throw new Exception("Error prepare update compra: " . $this->db->error);
            }

            $compra_update_stmt->bind_param("i", $id_compra);
            $compra_update_result = $compra_update_stmt->execute();

            if (!$compra_update_result) {
                throw new Exception("Error execute update compra: " . $compra_update_stmt->error);
            }

            $compra_update_stmt->close();

            // 7. CONFIRMAR TRANSACCIÓN
            error_log("[TRANSACCIÓN] Confirmando transacción...");
            $this->db->commit();

            error_log("[ÉXITO] === Compra ID: $id_compra marcada como recibida exitosamente ===");

            return true;
        } catch (Exception $e) {
            // 8. REVERTIR TRANSACCIÓN EN CASO DE ERROR
            error_log("[ERROR] Excepción: " . $e->getMessage());
            error_log("[ERROR] Trace: " . $e->getTraceAsString());

            if (isset($this->db) && method_exists($this->db, 'rollback')) {
                error_log("[TRANSACCIÓN] Revertiendo transacción...");
                $this->db->rollback();
            }

            return false;
        }
    }

    private function tieneTablaMovimientos()
    {
        $result = $this->db->query("SHOW TABLES LIKE 'movimientos_inventario'");
        return $result && $result->num_rows > 0;
    }
}