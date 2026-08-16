<?php
ob_clean();
require_once __DIR__ . '/../models/Caja.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CajaController
{
    private $cajaModel;
    private $ventaModel;

    public function __construct()
    {
        global $conexion;
        $this->cajaModel = new Caja($conexion);
        $this->ventaModel = new Venta($conexion);
    }

    /* ==========================================================
       🔹 1. VISTA PRINCIPAL DE CAJA
    ========================================================== */
    public function index()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $turno_abierto = $this->cajaModel->getTurnoAbierto($_SESSION['id_usuario']);
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/caja/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /* ==========================================================
       🔹 2. ABRIR TURNO
    ========================================================== */
    public function abrir()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario'])) {
            $monto_inicial = (float)$_POST['monto_inicial'];

            if ($this->cajaModel->abrirTurno($_SESSION['id_usuario'], $monto_inicial)) {
                $_SESSION['mensaje'] = 'Turno de caja iniciado correctamente.';
                $_SESSION['mensaje_tipo'] = 'success';
            } else {
                $_SESSION['mensaje'] = 'Error al iniciar el turno (posiblemente ya hay uno abierto).';
                $_SESSION['mensaje_tipo'] = 'danger';
            }
        }
        header('Location: ' . BASE_URL . 'caja');
    }

    /* ==========================================================
       🔹 3. AGREGAR MOVIMIENTO
    ========================================================== */
    public function agregarMovimiento()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario'])) {
            $id_turno = (int)$_POST['id_turno'];
            $tipo = $_POST['tipo_movimiento'];
            $monto = (float)$_POST['monto'];
            $descripcion = trim($_POST['descripcion']);

            $ok = $this->cajaModel->agregarMovimiento($id_turno, $_SESSION['id_usuario'], $tipo, $monto, $descripcion);

            $_SESSION['mensaje'] = $ok ? 'Movimiento registrado correctamente.' : 'Error al registrar el movimiento.';
            $_SESSION['mensaje_tipo'] = $ok ? 'success' : 'danger';
        }
        header('Location: ' . BASE_URL . 'caja');
    }

    /* ==========================================================
       🔹 4. CERRAR TURNO CON EXPORT AUTOMÁTICO
    ========================================================== */
    public function cerrar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario'])) {
            $id_turno = (int)($_POST['id_turno'] ?? 0);
            $monto_final_real = (float)($_POST['monto_final_real'] ?? 0);

            try {
                $resultado = $this->cajaModel->cerrarTurno($id_turno, $_SESSION['id_usuario'], $monto_final_real);

                if ($resultado) {
                    // 🔹 EXPORT AUTOMÁTICO DE LA BASE DE DATOS COMPLETA
                    $export_result = $this->exportarBaseDatosCompleto();
                    
                    $moneda = getMoneda();
                    $mensaje_export = $export_result ? 
                        "<br><small>✅ Backup COMPLETO de BD generado automáticamente</small>" : 
                        "<br><small>⚠️ Backup no generado (ver logs del sistema)</small>";
                    
                    $_SESSION['mensaje'] = "<strong>Turno cerrado con éxito.</strong><br>
                        Monto sistema: {$moneda}" . number_format($resultado['sistema'], 2) . "<br>
                        Monto real: {$moneda}" . number_format($resultado['real'], 2) . "<br>
                        <strong>Diferencia: {$moneda}" . number_format($resultado['diferencia'], 2) . "</strong>" . 
                        $mensaje_export;

                    $_SESSION['mensaje_tipo'] = ($resultado['diferencia'] == 0) ? 'success' : 'warning';
                } else {
                    $_SESSION['mensaje'] = 'Error al cerrar el turno.';
                    $_SESSION['mensaje_tipo'] = 'danger';
                }
            } catch (Exception $e) {
                $_SESSION['mensaje'] = 'Error al cerrar turno: ' . $e->getMessage();
                $_SESSION['mensaje_tipo'] = 'danger';
            }
        }

        header('Location: ' . BASE_URL . 'caja');
        exit;
    }

    /* ==========================================================
       🔹 5. EXPORTAR BASE DE DATOS COMPLETA (MÉTODO PRINCIPAL CORREGIDO)
    ========================================================== */
    private function exportarBaseDatosCompleto()
    {
        try {
            $db_name = DB_NAME;
            $backup_dir = __DIR__ . '/../../backups/';

            // Crear directorio si no existe
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }

            $timestamp = date('Y-m-d_His');
            $backup_file = $backup_dir . "backup_completo_{$db_name}_{$timestamp}.sql";

            // 🔹 CORREGIR: SEPARAR HOST Y PUERTO
            $host_parts = explode(':', DB_HOST);
            $host = $host_parts[0];
            $port = $host_parts[1] ?? '3306';

            // 🔹 CONFIGURACIÓN MEJORADA
            $mysql_path = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"';
            
            // 🔹 COMANDO COMPLETO 
            $command = "{$mysql_path} " .
                       "--host={$host} " .
                       "--port={$port} " .
                       "--user=" . DB_USER . " " .
                       "--password=" . DB_PASS . " " .
                       "--databases {$db_name} " .
                       "--add-drop-database " .
                       "--add-drop-table " .
                       "--complete-insert " .
                       "--extended-insert " .
                       "--single-transaction " .
                       "--routines " .
                       "--triggers " .
                       "--events " .
                       "--default-character-set=utf8mb4 " .
                       "--result-file=\"{$backup_file}\" " .
                       "2>&1";

            // Ejecutar comando
            exec($command, $output, $return_var);

            if ($return_var === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
                $tamaño = filesize($backup_file);
                error_log("✅ Backup COMPLETO creado: {$backup_file} ({$tamaño} bytes)");
                
                // 🔹 LIMPIAR BACKUPS ANTIGUOS
                $this->limpiarBackupsAntiguos($backup_dir);
                
                return true;
            } else {
                error_log("❌ Error en backup mysqldump. Código: {$return_var}");
                error_log("❌ Output: " . implode("\n", $output));
                
                // 🔹 INTENTAR CON MÉTODO ALTERNATIVO SI FALLA
                return $this->exportarBaseDatosCompletoPHP();
            }
        } catch (Exception $e) {
            error_log("❌ Excepción en backup mysqldump: " . $e->getMessage());
            return $this->exportarBaseDatosCompletoPHP();
        }
    }

    /* ==========================================================
       🔹 6. EXPORTAR BD COMPLETA CON PHP (MÉTODO ALTERNATIVO MEJORADO)
    ========================================================== */
    private function exportarBaseDatosCompletoPHP()
    {
        global $conexion;
        
        try {
            $db_name = DB_NAME;
            $backup_dir = __DIR__ . '/../../backups/';

            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }

            $timestamp = date('Y-m-d_His');
            $backup_file = $backup_dir . "backup_php_completo_{$db_name}_{$timestamp}.sql";

            $sql_script = "/*\n";
            $sql_script .= " * BACKUP COMPLETO - " . getConfig('nombre_tienda') . "\n";
            $sql_script .= " * Base de datos: {$db_name}\n";
            $sql_script .= " * Generado: " . date('d/m/Y H:i:s') . "\n";
            $sql_script .= " * RUC: " . getConfig('ruc') . "\n";
            $sql_script .= " * Método: PHP Native\n";
            $sql_script .= " */\n\n";
            
            $sql_script .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sql_script .= "SET UNIQUE_CHECKS=0;\n";
            $sql_script .= "SET NAMES utf8mb4;\n\n";

            // 🔹 1. OBTENER Y EXPORTAR TODAS LAS TABLAS
            $result = $conexion->query("SHOW TABLES");
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }

            $total_tablas = count($tables);
            $sql_script .= "-- Total de tablas: {$total_tablas}\n\n";

            foreach ($tables as $table) {
                $sql_script .= "-- --------------------------------------------------------\n";
                $sql_script .= "-- Estructura de tabla: `{$table}`\n";
                $sql_script .= "-- --------------------------------------------------------\n\n";
                
                // 🔹 OBTENER ESTRUCTURA COMPLETA
                $create_result = $conexion->query("SHOW CREATE TABLE `{$table}`");
                if ($create_result && $create_row = $create_result->fetch_array()) {
                    $sql_script .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $sql_script .= $create_row[1] . ";\n\n";
                }

                // 🔹 OBTENER Y EXPORTAR DATOS
                $sql_script .= "-- Volcado de datos para tabla: `{$table}`\n\n";
                $data_result = $conexion->query("SELECT * FROM `{$table}`");
                $row_count = 0;
                
                if ($data_result && $data_result->num_rows > 0) {
                    // Obtener nombres de columnas
                    $fields = $data_result->fetch_fields();
                    $column_names = [];
                    foreach ($fields as $field) {
                        $column_names[] = "`{$field->name}`";
                    }
                    $column_list = implode(', ', $column_names);
                    
                    // Reiniciar el resultado para leer los datos
                    $data_result->data_seek(0);
                    
                    while ($row = $data_result->fetch_assoc()) {
                        $values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . $conexion->real_escape_string($value) . "'";
                            }
                        }
                        
                        $sql_script .= "INSERT INTO `{$table}` ({$column_list}) VALUES (" . implode(', ', $values) . ");\n";
                        $row_count++;
                    }
                }
                $sql_script .= "-- Total registros: {$row_count}\n\n";
            }

            // 🔹 FINALIZAR SCRIPT
            $sql_script .= "SET FOREIGN_KEY_CHECKS=1;\n";
            $sql_script .= "SET UNIQUE_CHECKS=1;\n\n";
            $sql_script .= "-- Backup completado exitosamente\n";
            $sql_script .= "-- Archivo: " . basename($backup_file) . "\n";

            // 🔹 GUARDAR ARCHIVO
            if (file_put_contents($backup_file, $sql_script)) {
                $tamaño = filesize($backup_file);
                error_log("✅ Backup COMPLETO PHP creado: {$backup_file} ({$tamaño} bytes)");
                
                // 🔹 LIMPIAR BACKUPS ANTIGUOS
                $this->limpiarBackupsAntiguos($backup_dir);
                
                return true;
            } else {
                error_log("❌ Error guardando archivo backup completo");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Excepción en backup completo PHP: " . $e->getMessage());
            return false;
        }
    }

    /* ==========================================================
       🔹 7. LIMPIAR BACKUPS ANTIGUOS
    ========================================================== */
    private function limpiarBackupsAntiguos($backup_dir)
    {
        try {
            $max_backups = 10; // Mantener solo los últimos 10 backups

            $files = glob($backup_dir . "backup*.sql");
            if (count($files) > $max_backups) {
                // Ordenar por fecha de modificación (más antiguos primero)
                usort($files, function ($a, $b) {
                    return filemtime($a) - filemtime($b);
                });

                // Eliminar los más antiguos
                $files_to_delete = count($files) - $max_backups;
                for ($i = 0; $i < $files_to_delete; $i++) {
                    if (unlink($files[$i])) {
                        error_log("🗑️ Backup antiguo eliminado: " . basename($files[$i]));
                    }
                }
            }
        } catch (Exception $e) {
            error_log("⚠️ Error limpiando backups antiguos: " . $e->getMessage());
        }
    }

    /* ==========================================================
       🔹 8. LISTAR BACKUPS DESDE EL SISTEMA
    ========================================================== */
    public function listarBackups()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $backup_dir = __DIR__ . '/../../backups/';
        $backups = [];

        if (is_dir($backup_dir)) {
            $files = glob($backup_dir . "backup*.sql");
            
            // Ordenar por fecha (más recientes primero)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            foreach ($files as $file) {
                $backups[] = [
                    'nombre' => basename($file),
                    'ruta' => $file,
                    'tamaño' => $this->formatearTamaño(filesize($file)),
                    'fecha' => date('d/m/Y H:i:s', filemtime($file))
                ];
            }
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/caja/backups.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    private function formatearTamaño($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /* ==========================================================
       🔹 9. DESCARGAR BACKUP
    ========================================================== */
    public function descargarBackup()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $archivo = $_GET['archivo'] ?? '';
        $backup_dir = __DIR__ . '/../../backups/';
        $ruta_completa = $backup_dir . $archivo;

        // Validar que el archivo existe y es un backup válido
        if (file_exists($ruta_completa) && pathinfo($archivo, PATHINFO_EXTENSION) === 'sql') {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $archivo . '"');
            header('Content-Length: ' . filesize($ruta_completa));
            readfile($ruta_completa);
            exit;
        } else {
            $_SESSION['mensaje'] = 'Backup no encontrado o archivo inválido.';
            $_SESSION['mensaje_tipo'] = 'danger';
            header('Location: ' . BASE_URL . 'caja/listarBackups');
            exit;
        }
    }

    /* ==========================================================
       🔹 10. ELIMINAR BACKUP
    ========================================================== */
    public function eliminarBackup()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $archivo = $_GET['archivo'] ?? '';
        $backup_dir = __DIR__ . '/../../backups/';
        $ruta_completa = $backup_dir . $archivo;

        if (file_exists($ruta_completa) && unlink($ruta_completa)) {
            $_SESSION['mensaje'] = 'Backup eliminado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar el backup.';
            $_SESSION['mensaje_tipo'] = 'danger';
        }

        header('Location: ' . BASE_URL . 'caja/listarBackups');
        exit;
    }

    /* ==========================================================
       🔹 11. OBTENER TURNOS CON DATOS REALES DESDE VENTAS
    ========================================================== */
    private function obtenerTurnosConDatosReales()
    {
        // 🔹 PRIMERO: Obtener todos los turnos cerrados
        global $conexion;

        $sql_turnos = "
            SELECT 
                tc.*,
                u.nombre_completo as usuario
            FROM turnos_caja tc
            JOIN usuarios u ON tc.id_usuario = u.id_usuario
            WHERE tc.estado = 'cerrado'
            ORDER BY tc.fecha_apertura DESC
        ";

        $resultado_turnos = $conexion->query($sql_turnos);
        $turnos = [];

        if ($resultado_turnos && $resultado_turnos->num_rows > 0) {
            while ($turno = $resultado_turnos->fetch_assoc()) {
                $id_turno = $turno['id_turno'];

                // 🔹 CORREGIDO: Obtener datos REALES de ventas para este turno
                $datos_ventas = $this->obtenerDatosRealesVentasPorTurno($id_turno);

                // 🔹 COMBINAR datos del turno con datos reales de ventas
                $turno_completo = array_merge($turno, $datos_ventas);
                $turnos[] = $turno_completo;
            }
        }

        return $turnos;
    }

    /* ==========================================================
       🔹 12. OBTENER DATOS REALES DE VENTAS POR TURNO
    ========================================================== */
    private function obtenerDatosRealesVentasPorTurno($id_turno)
    {
        global $conexion;

        // 🔹 1. Obtener métodos de pago REALES
        $sql_metodos_pago = "
            SELECT 
                COUNT(*) as num_ventas,
                COALESCE(SUM(total_venta), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total_venta ELSE 0 END), 0) as efectivo,
                COALESCE(SUM(CASE WHEN metodo_pago = 'yape' THEN total_venta ELSE 0 END), 0) as yape,
                COALESCE(SUM(CASE WHEN metodo_pago = 'plin' THEN total_venta ELSE 0 END), 0) as plin,
                COALESCE(SUM(CASE WHEN metodo_pago = 'agora' THEN total_venta ELSE 0 END), 0) as agora,
                COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN total_venta ELSE 0 END), 0) as transferencia
            FROM ventas 
            WHERE estado = 'completada' 
            AND id_turno = ?
        ";

        $stmt = $conexion->prepare($sql_metodos_pago);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $metodos_pago = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 🔹 2. Obtener costos y ganancias REALES
        $sql_costos = "
            SELECT 
                COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) as total_bruto,
                COALESCE(SUM(dv.cantidad * COALESCE(p.precio_compra, 0)), 0) as total_neto,
                COALESCE(SUM((dv.cantidad * dv.precio_unitario) - (dv.cantidad * COALESCE(p.precio_compra, 0))), 0) as ganancia
            FROM ventas v
            INNER JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
            LEFT JOIN productos p ON dv.id_producto = p.id_producto
            WHERE v.estado = 'completada' 
            AND v.id_turno = ?
        ";

        $stmt = $conexion->prepare($sql_costos);
        $stmt->bind_param("i", $id_turno);
        $stmt->execute();
        $costos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 🔹 3. Retornar datos combinados con valores por defecto
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

    /* ==========================================================
       🔹 13. ACTUALIZAR DATOS DE TURNOS EXISTENTES
    ========================================================== */
    public function actualizarDatosTurnos()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        try {
            global $conexion;

            // Obtener todos los turnos cerrados
            $sql_turnos = "SELECT id_turno FROM turnos_caja WHERE estado = 'cerrado'";
            $resultado = $conexion->query($sql_turnos);
            $turnos_actualizados = 0;

            if ($resultado && $resultado->num_rows > 0) {
                while ($turno = $resultado->fetch_assoc()) {
                    $id_turno = $turno['id_turno'];
                    $datos_reales = $this->obtenerDatosRealesVentasPorTurno($id_turno);

                    // Actualizar el turno con datos reales
                    $sql_update = "
                        UPDATE turnos_caja SET 
                            num_ventas = ?,
                            total_ingresos = ?,
                            efectivo = ?,
                            yape = ?,
                            plin = ?,
                            agora = ?,
                            transferencia = ?,
                            total_bruto = ?,
                            total_neto = ?,
                            ganancia = ?
                        WHERE id_turno = ?
                    ";

                    $stmt = $conexion->prepare($sql_update);
                    $stmt->bind_param(
                        "idddddddddi",
                        $datos_reales['num_ventas'],
                        $datos_reales['total_ingresos'],
                        $datos_reales['efectivo'],
                        $datos_reales['yape'],
                        $datos_reales['plin'],
                        $datos_reales['agora'],
                        $datos_reales['transferencia'],
                        $datos_reales['total_bruto'],
                        $datos_reales['total_neto'],
                        $datos_reales['ganancia'],
                        $id_turno
                    );

                    if ($stmt->execute()) {
                        $turnos_actualizados++;
                    }
                    $stmt->close();
                }
            }

            echo json_encode([
                'success' => true,
                'mensaje' => "Se actualizaron $turnos_actualizados turnos con datos reales de ventas",
                'turnos_actualizados' => $turnos_actualizados
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /* ==========================================================
       🔹 14. EXPORTAR PDF DETALLADO (CORREGIDO CON DATOS REALES)
    ========================================================== */
    public function pdf()
    {
        require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

        // 🔹 CORREGIDO: Usar el nuevo método para obtener datos reales
        $turnos = $this->obtenerTurnosConDatosReales();

        // Eliminar cualquier salida previa (muy importante para FPDF)
        while (ob_get_level() > 0) ob_end_clean();

        // Configuración PDF (A4 landscape)
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->SetMargins(3, 6, 3); // márgenes muy pequeños para maximizar espacio
        $pdf->AddPage();

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, utf8_decode('Reporte Detallado de Caja'), 0, 1, 'C');
        $pdf->Ln(2);

        // Cabeceras (17 columnas)
        $headers = [
            'Usuario',
            'Apertura',
            'Monto Ini.',
            'Cierre',
            'Final Sist.',
            'Final Real',
            'Dif.',
            'Ventas',
            'Total Ventas',
            'Efectivo',
            'Yape',
            'Agora',
            'Plin',
            'Transfer.',
            'Ventas Brutas',
            'Costo Neto',
            'Ganancia'
        ];

        // Anchos recalculados para que sumen dentro del ancho útil de A4-Landscape
        // suma aproximada <= 291 mm (297 - márgenes)
        $widths = [21, 19, 17, 19, 17, 17, 10, 13, 19, 14, 14, 14, 14, 18, 19, 19, 19];

        // Cabecera estilo
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetFillColor(45, 45, 45);
        $pdf->SetTextColor(255);
        for ($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($widths[$i], 8, utf8_decode($headers[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Filas - datos
        $pdf->SetFont('Arial', '', 6.2); // fuente pequeña pero legible
        $pdf->SetTextColor(0);

        foreach ($turnos as $t) {
            $row = [
                $t['usuario'] ?? '',
                !empty($t['fecha_apertura']) ? date('d/m/Y H:i', strtotime($t['fecha_apertura'])) : '-',
                number_format($t['monto_inicial'] ?? 0, 2),
                !empty($t['fecha_cierre']) ? date('d/m/Y H:i', strtotime($t['fecha_cierre'])) : '-',
                number_format($t['monto_final_sistema'] ?? 0, 2),
                number_format($t['monto_final_real'] ?? 0, 2),
                number_format($t['diferencia'] ?? 0, 2),
                $t['num_ventas'] ?? 0,
                number_format($t['total_ingresos'] ?? 0, 2),
                number_format($t['efectivo'] ?? 0, 2),
                number_format($t['yape'] ?? 0, 2),
                number_format($t['agora'] ?? 0, 2),
                number_format($t['plin'] ?? 0, 2),
                number_format($t['transferencia'] ?? 0, 2),
                number_format($t['total_bruto'] ?? 0, 2),
                number_format($t['total_neto'] ?? 0, 2),
                number_format($t['ganancia'] ?? 0, 2)
            ];

            for ($i = 0; $i < count($row); $i++) {
                // Centrar y limitar a una línea por celda
                $pdf->Cell($widths[$i], 6, utf8_decode((string)$row[$i]), 1, 0, 'C');
            }
            $pdf->Ln();
        }

        // Totales generales (al final, con fondo)
        $totalBruto = array_sum(array_column($turnos, 'total_bruto'));
        $totalNeto = array_sum(array_column($turnos, 'total_neto'));
        $totalGanancia = array_sum(array_column($turnos, 'ganancia'));

        // Celda de label que ocupa las primeras 14 columnas de ancho sumarizado
        $labelWidth = array_sum(array_slice($widths, 0, 14)); // suma de anchos primera parte
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetFillColor(235, 235, 235);
        $pdf->Cell($labelWidth, 7, utf8_decode('Totales generales:'), 1, 0, 'R', true);

        // Celdas de totales (Ventas Brutas, Costo Neto, Ganancia)
        $pdf->Cell($widths[14], 7, number_format($totalBruto, 2), 1, 0, 'C', true);
        $pdf->Cell($widths[15], 7, number_format($totalNeto, 2), 1, 0, 'C', true);
        $pdf->Cell($widths[16], 7, number_format($totalGanancia, 2), 1, 1, 'C', true);

        // Enviar PDF (descarga)
        $pdf->Output('D', 'Reporte_Caja_' . date('Y-m-d') . '.pdf');
        exit;
    }

    /* ==========================================================
       🔹 15. EXPORTAR A EXCEL (CORREGIDO CON DATOS REALES)
    ========================================================== */
    public function excel()
    {
        try {
            // 🔹 CORREGIDO: Usar el nuevo método para obtener datos reales
            $turnos = $this->obtenerTurnosConDatosReales();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Caja');

            // Título
            $sheet->setCellValue('A1', 'Reporte Detallado de Caja - Datos Reales desde Ventas');
            $sheet->mergeCells('A1:Q1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Fecha de generación
            $sheet->setCellValue('A2', 'Generado el: ' . date('d/m/Y H:i:s'));
            $sheet->mergeCells('A2:Q2');
            $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Encabezados
            $headers = [
                'Usuario',
                'Apertura',
                'Monto Inicial',
                'Cierre',
                'Monto Final Sistema',
                'Monto Final Real',
                'Diferencia',
                'Ventas Realizadas',
                'Total Ventas',
                'Efectivo',
                'Yape',
                'Agora',
                'Plin',
                'Transferencia',
                'Ventas Brutas',
                'Costo Neto (Compra)',
                'Ganancia Neta'
            ];
            $sheet->fromArray($headers, null, 'A4');

            // Estilo encabezado
            $sheet->getStyle('A4:Q4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            // Datos
            $row = 5;
            foreach ($turnos as $t) {
                $sheet->fromArray([
                    $t['usuario'] ?? '',
                    $t['fecha_apertura'] ?? '',
                    $t['monto_inicial'] ?? 0,
                    $t['fecha_cierre'] ?? '',
                    $t['monto_final_sistema'] ?? 0,
                    $t['monto_final_real'] ?? 0,
                    $t['diferencia'] ?? 0,
                    $t['num_ventas'] ?? 0,
                    $t['total_ingresos'] ?? 0,
                    $t['efectivo'] ?? 0,
                    $t['yape'] ?? 0,
                    $t['agora'] ?? 0,
                    $t['plin'] ?? 0,
                    $t['transferencia'] ?? 0,
                    $t['total_bruto'] ?? 0,
                    $t['total_neto'] ?? 0,
                    $t['ganancia'] ?? 0
                ], null, "A{$row}");
                $row++;
            }

            // Totales generales
            $totalRow = $row + 1;
            $sheet->setCellValue("N{$totalRow}", "TOTALES GENERALES:");
            $sheet->getStyle("N{$totalRow}")->getFont()->setBold(true);

            $sheet->setCellValue("O{$totalRow}", array_sum(array_column($turnos, 'total_bruto')));
            $sheet->setCellValue("P{$totalRow}", array_sum(array_column($turnos, 'total_neto')));
            $sheet->setCellValue("Q{$totalRow}", array_sum(array_column($turnos, 'ganancia')));

            // Estilo totales
            $sheet->getStyle("O{$totalRow}:Q{$totalRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            // Estilo general y auto-ajuste
            $sheet->getStyle("A4:Q" . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
            ]);

            // Formato de números
            $sheet->getStyle("C5:C" . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("E5:G" . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("I5:Q" . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            foreach (range('A', 'Q') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Limpiar buffers previos antes de enviar
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Reporte_Caja_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Throwable $e) {
            error_log('Error al generar Excel: ' . $e->getMessage());
            $_SESSION['mensaje'] = 'Error al generar Excel: ' . $e->getMessage();
            $_SESSION['mensaje_tipo'] = 'danger';
            header('Location: ' . BASE_URL . 'caja');
        }
    }

    /* ==========================================================
       🔹 16. REPORTE DETALLADO EN VISTA (CORREGIDO)
    ========================================================== */
    public function reporte()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // 🔹 CORREGIDO: Usar el nuevo método para obtener datos reales
        $turnos = $this->obtenerTurnosConDatosReales();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/caja.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /* ==========================================================
       🔹 17. NUEVO: REPORTE DETALLADO (PARA LA NUEVA VISTA)
    ========================================================== */
    public function reporteDetallado()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // 🔹 CORREGIDO: Usar el nuevo método para obtener datos reales
        $turnos = $this->obtenerTurnosConDatosReales();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/caja/reporte_detallado.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}