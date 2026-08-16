<?php
class Transferencia {
    private $db;
    public function __construct($conexion) { $this->db = $conexion; }

    public function getProductosActivos() {
        return $this->db->query("SELECT id_producto, nombre, stock, id_almacen FROM productos WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function getAlmacenesActivos() {
        return $this->db->query("SELECT id, nombre FROM almacenes WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function realizarTransferencia($datos) {
        $id_producto = (int)$datos['id_producto'];
        $id_almacen_origen = (int)$datos['id_almacen_origen'];
        $id_almacen_destino = (int)$datos['id_almacen_destino'];
        $cantidad = (int)$datos['cantidad'];
        $id_usuario = $_SESSION['id_usuario'];

        $this->db->begin_transaction();
        try {
            // 1. Verificar stock en el almacén de origen
            $stmt_stock = $this->db->prepare("SELECT stock FROM productos WHERE id_producto = ? AND id_almacen = ? FOR UPDATE");
            $stmt_stock->bind_param("ii", $id_producto, $id_almacen_origen);
            $stmt_stock->execute();
            $stock_origen = $stmt_stock->get_result()->fetch_assoc()['stock'];

            if ($stock_origen < $cantidad) {
                throw new Exception("Stock insuficiente en el almacén de origen.");
            }

            // 2. Registrar salida del almacén de origen
            $nuevo_stock_origen = $stock_origen - $cantidad;
            $stmt_kardex_salida = $this->db->prepare("INSERT INTO movimientos_inventario (id_producto, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, id_usuario) VALUES (?, 'transferencia_salida', ?, ?, ?, ?)");
            $stmt_kardex_salida->bind_param("iiiii", $id_producto, $cantidad, $stock_origen, $nuevo_stock_origen, $id_usuario);
            $stmt_kardex_salida->execute();
            
            // 3. Actualizar stock en el producto de origen
            $stmt_update_origen = $this->db->prepare("UPDATE productos SET stock = ? WHERE id_producto = ?");
            $stmt_update_origen->bind_param("ii", $nuevo_stock_origen, $id_producto);
            $stmt_update_origen->execute();

            // 4. Registrar entrada en el almacén de destino
            // Esta parte asume que el producto ya existe en el almacén de destino.
            // Una implementación más compleja crearía el producto si no existe.
            $stmt_stock_destino = $this->db->prepare("SELECT stock FROM productos WHERE id_producto = ? AND id_almacen = ? FOR UPDATE");
            $stmt_stock_destino->bind_param("ii", $id_producto, $id_almacen_destino);
            $stmt_stock_destino->execute();
            $stock_destino = $stmt_stock_destino->get_result()->fetch_assoc()['stock'];
            $nuevo_stock_destino = $stock_destino + $cantidad;
            
            $stmt_kardex_entrada = $this->db->prepare("INSERT INTO movimientos_inventario (id_producto, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, id_usuario) VALUES (?, 'transferencia_entrada', ?, ?, ?, ?)");
            $stmt_kardex_entrada->bind_param("iiiii", $id_producto, $cantidad, $stock_destino, $nuevo_stock_destino, $id_usuario);
            $stmt_kardex_entrada->execute();

            // 5. Actualizar stock en el producto de destino
            $stmt_update_destino = $this->db->prepare("UPDATE productos SET stock = ? WHERE id_producto = ? AND id_almacen = ?");
            $stmt_update_destino->bind_param("iii", $nuevo_stock_destino, $id_producto, $id_almacen_destino);
            $stmt_update_destino->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return $e->getMessage();
        }
    }
}