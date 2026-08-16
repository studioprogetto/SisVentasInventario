<?php
class Cliente
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    public function obtenerTodos($activo = 1)
    {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE activo = ? ORDER BY nombre_cliente ASC");
        $stmt->bind_param("i", $activo);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function obtenerClientePorId($id_cliente)
    {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function guardar($datos)
    {
        $id = $datos['id_cliente'] ?? null;
        $nombre = trim($datos['nombre_cliente']);
        $documento = !empty(trim($datos['documento_identidad'])) ? trim($datos['documento_identidad']) : null;
        $telefono = !empty(trim($datos['telefono'])) ? trim($datos['telefono']) : null;
        $email = !empty(trim($datos['email'])) ? trim($datos['email']) : null;
        $direccion = !empty(trim($datos['direccion'])) ? trim($datos['direccion']) : null;

        if ($id) {
            $stmt = $this->db->prepare("
                UPDATE clientes 
                SET nombre_cliente = ?, documento_identidad = ?, telefono = ?, email = ?, direccion = ? 
                WHERE id_cliente = ?
            ");
            $stmt->bind_param("sssssi", $nombre, $documento, $telefono, $email, $direccion, $id);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO clientes (nombre_cliente, documento_identidad, telefono, email, direccion) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $nombre, $documento, $telefono, $email, $direccion);
        }
        return $stmt->execute();
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare("UPDATE clientes SET activo = ? WHERE id_cliente = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }

    public function buscar($term)
    {
        $term = "%" . $term . "%";
        $stmt = $this->db->prepare("
            SELECT 
                c.id_cliente, 
                c.nombre_cliente, 
                c.documento_identidad, 
                c.sellos,
                COALESCE(sc.saldo, 0) as saldo
            FROM clientes c
            LEFT JOIN saldos_clientes sc ON c.id_cliente = sc.id_cliente 
            WHERE c.activo = 1 AND (c.nombre_cliente LIKE ? OR c.documento_identidad LIKE ?)
            ORDER BY c.nombre_cliente ASC
            LIMIT 10
        ");
        $stmt->bind_param("ss", $term, $term);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function obtenerSellosCliente($id_cliente)
    {
        $stmt = $this->db->prepare("SELECT sellos FROM clientes WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? (int)$result['sellos'] : 0;
    }

    public function obtenerSaldoCliente($id_cliente)
    {
        $stmt = $this->db->prepare("SELECT saldo FROM saldos_clientes WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ? (float)$result['saldo'] : 0.00;
    }

    private function actualizarSellosYTarjetas($id_cliente, $sellos_restantes, $tarjetas_llenas_extra)
    {
        $stmt = $this->db->prepare("
            UPDATE clientes 
            SET sellos = ?, tarjetas_llenas = tarjetas_llenas + ? 
            WHERE id_cliente = ?
        ");
        $stmt->bind_param("iii", $sellos_restantes, $tarjetas_llenas_extra, $id_cliente);
        return $stmt->execute();
    }

    public function procesarSellosVenta($id_cliente, $sellos_nuevos)
    {
        if ($sellos_nuevos <= 0) {
            $sellos_antes = $this->obtenerSellosCliente($id_cliente);
            return [
                'descuento_sellos' => 0,
                'sellos_restantes' => $sellos_antes,
                'tarjetas_llenas_extra' => 0,
                'sellos_antes' => $sellos_antes
            ];
        }

        $sellos_antes = $this->obtenerSellosCliente($id_cliente);
        $total_sellos = $sellos_antes + $sellos_nuevos;
        $descuento = 0;
        $tarjetas_llenas_extra = 0;
        $sellos_restantes = $total_sellos;

        if ($total_sellos === 6) {
            $descuento = 0.05;
            $sellos_restantes = 6;
        } elseif ($total_sellos === 12) {
            $descuento = 0.10;
            $tarjetas_llenas_extra = 1;
            $sellos_restantes = 0;
        } elseif ($total_sellos > 12) {
            $tarjetas_llenas_extra = intdiv($total_sellos, 12);
            $sellos_restantes = $total_sellos % 12;
        }

        $this->actualizarSellosYTarjetas($id_cliente, $sellos_restantes, $tarjetas_llenas_extra);

        return [
            'descuento_sellos' => $descuento,
            'sellos_restantes' => $sellos_restantes,
            'tarjetas_llenas_extra' => $tarjetas_llenas_extra,
            'sellos_antes' => $sellos_antes
        ];
    }

    public function incrementarTotalCompras($id_cliente)
    {
        $stmt = $this->db->prepare("UPDATE clientes SET total_compras = total_compras + 1 WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        return $stmt->execute();
    }
}