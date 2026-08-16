<?php
// Define DEBUG constant if not already defined
if (!defined('DEBUG')) {
    define('DEBUG', false);
}

require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/../models/Almacen.php';

// PDF
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';
define('FPDF_FONTPATH', __DIR__ . '/../../libs/fpdf/font/');

// PhpSpreadsheet (Composer autoload)
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CompraController
{
    private $db;
    private $compraModel;
    private $almacenModel;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
        $this->compraModel = new Compra($conexion);
        $this->almacenModel = new Almacen($conexion);
    }

    // Lista de compras (index)
    public function index()
    {
        $this->verificarSesion();

        $compras = $this->compraModel->obtenerTodas();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/compras/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }


    public function crear()
    {
        $this->verificarSesion();

        $proveedores = $this->compraModel->obtenerProveedoresActivos();
        $almacenes = $this->almacenModel->obtenerTodos();

        // DEBUG: Verificar qué se está obteniendo
        if (DEBUG) {
            error_log("Almacenes obtenidos: " . print_r($almacenes, true));
            error_log("Número de almacenes: " . count($almacenes));
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/compras/crear.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // En controllers/CompraController.php
    public function buscarProductos()
    {
        header('Content-Type: application/json');
        if (!$this->sesionActiva()) {
            echo json_encode([]);
            exit;
        }

        $term = $_GET['term'] ?? '';
        $id_almacen = $_GET['id_almacen'] ?? null;

        // Llamar al método corregido del modelo
        $productos_res = $this->compraModel->buscarProductos($term, $id_almacen);

        $productos = [];
        while ($row = $productos_res->fetch_assoc()) {
            $productos[] = $row;
        }
        echo json_encode($productos);
    }

    // Guardar compra
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->sesionActiva()) {
            $productos = json_decode($_POST['productos_compra'], true);
            $_POST['productos_compra'] = $productos;
            $ok = $this->compraModel->guardarCompra($_POST);
            $_SESSION['flash_success'] = $ok ? 'Compra registrada.' : 'Error al registrar compra.';
        }
        header('Location: ' . BASE_URL . 'compra');
        exit;
    }

    public function recibir($id_compra)
    {
        // Habilitar logging detallado
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        error_log("=== INICIANDO RECEPCIÓN COMPRA ID: $id_compra ===");

        if (!$this->sesionActiva()) {
            error_log("ERROR: Usuario no autenticado");
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Verificar si es solicitud AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        error_log("Es AJAX: " . ($isAjax ? 'Sí' : 'No'));
        error_log("ID Usuario: " . ($_SESSION['id_usuario'] ?? 'No hay sesión'));

        try {
            // Procesar la recepción
            error_log("Llamando a recibirCompra()...");
            $resultado = $this->compraModel->recibirCompra((int)$id_compra);
            error_log("Resultado de recibirCompra: " . ($resultado ? 'TRUE' : 'FALSE'));

            // Verificar error de MySQL si hay
            global $conexion;
            if ($conexion->error) {
                error_log("Error MySQL: " . $conexion->error);
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                if ($resultado) {
                    error_log("Enviando respuesta JSON exitosa");
                    echo json_encode([
                        'success' => true,
                        'message' => 'Compra recibida exitosamente',
                        'compra_id' => $id_compra
                    ]);
                } else {
                    error_log("Enviando respuesta JSON de error");
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error al recibir la compra. ' . ($conexion->error ?? 'Verifica los logs.')
                    ]);
                }
                exit;
            }

            // Para solicitudes normales
            if ($resultado) {
                $_SESSION['flash_success'] = "Compra #$id_compra marcada como recibida.";
            } else {
                $_SESSION['flash_error'] = "Error al recibir la compra #$id_compra. " . ($conexion->error ?? '');
            }

            header('Location: ' . BASE_URL . 'compra');
            exit;
        } catch (Exception $e) {
            error_log("EXCEPCIÓN en recibir(): " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Excepción: ' . $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_error'] = "Error: " . $e->getMessage();
            header('Location: ' . BASE_URL . 'compra');
            exit;
        }
    }

    // Método para detectar si es AJAX
    private function esAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // REPORTE (HTML + export: excel/pdf)
    public function reporte()
    {
        $this->verificarSesion();

        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-t');
        $accion       = $_GET['accion'] ?? 'ver';

        // Formatear fechas para mostrar
        $f_inicio = date('d/m/Y', strtotime($fecha_inicio));
        $f_fin    = date('d/m/Y', strtotime($fecha_fin));

        // Resumen de compras
        $stmt = $this->db->prepare("SELECT COUNT(*) AS num_compras, COALESCE(SUM(total_compra),0) AS total_compras
                                    FROM compras
                                    WHERE DATE(fecha_compra) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $resumen = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Listado de compras
        $stmt2 = $this->db->prepare("SELECT c.id_compra, COALESCE(p.nombre_proveedor, 'Sin proveedor') AS nombre_proveedor, c.fecha_compra, c.total_compra, c.estado
                                     FROM compras c
                                     LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                                     WHERE DATE(c.fecha_compra) BETWEEN ? AND ?
                                     ORDER BY c.fecha_compra ASC");
        $stmt2->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt2->execute();
        $compras = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        // --- EXPORTAR A EXCEL ---
        if ($accion === 'excel') {
            if (ob_get_length()) ob_clean();
            while (ob_get_level() > 0) ob_end_clean();

            ini_set('display_errors', 0);
            error_reporting(E_ALL);

            try {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Reporte Compras');

                // TÍTULO CON RANGO DE FECHAS
                $sheet->setCellValue('A1', 'REPORTE DE COMPRAS');
                $sheet->mergeCells('A1:E1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1A2A44']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                $sheet->setCellValue('A2', "Del {$f_inicio} al {$f_fin}");
                $sheet->mergeCells('A2:E2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '6C757D']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                // RESUMEN
                $sheet->setCellValue('A4', 'Total de Compras:');
                $sheet->setCellValue('B4', $resumen['num_compras'] ?? 0);
                $sheet->setCellValue('A5', 'Total Gastado:');
                $sheet->setCellValue('B5', 'S/ ' . number_format($resumen['total_compras'] ?? 0, 2));
                $sheet->getStyle('A4:A5')->getFont()->setBold(true);
                $sheet->getStyle('B4:B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // ENCABEZADOS
                $headers = ['ID', 'Proveedor', 'Fecha', 'Total (S/)', 'Estado'];
                $sheet->fromArray($headers, null, 'A7');
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2A44']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ];
                $sheet->getStyle('A7:E7')->applyFromArray($headerStyle);

                // DATOS
                $row = 8;
                if (!empty($compras)) {
                    foreach ($compras as $c) {
                        $sheet->setCellValue("A{$row}", $c['id_compra']);
                        $sheet->setCellValue("B{$row}", $c['nombre_proveedor']);
                        $sheet->setCellValue("C{$row}", date('d/m/Y', strtotime($c['fecha_compra'])));
                        $sheet->setCellValue("D{$row}", (float)$c['total_compra']);
                        $sheet->setCellValue("E{$row}", ucfirst($c['estado']));
                        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $row++;
                    }
                } else {
                    $sheet->setCellValue("A{$row}", 'No hay compras en este periodo');
                    $sheet->mergeCells("A{$row}:E{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['italic' => true, 'color' => ['rgb' => '28A745']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                    ]);
                    $row++;
                }

                // ESTILO TABLA
                $lastRow = $row - 1;
                $dataRange = "A7:E{$lastRow}";
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD']
                        ]
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);

                foreach (range('A', 'E') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // PIE DE PÁGINA
                $sheet->setCellValue("A{$row}", 'Generado el ' . date('d/m/Y H:i'));
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '888888']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                // DESCARGA
                $filename = 'reporte_compras_' . date('Ymd_His') . '.xlsx';
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                header('Pragma: public');
                header('Expires: 0');

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (Exception $e) {
                if (defined('DEBUG') && DEBUG) {
                    die("Error al generar Excel: " . $e->getMessage());
                } else {
                    http_response_code(500);
                    die("Error interno al generar el reporte.");
                }
            }
            exit;
        }

        // --- EXPORTAR A PDF ---
        if ($accion === 'pdf') {
            if (ob_get_length()) ob_clean();
            while (ob_get_level() > 0) ob_end_clean();

            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 15);

            // === LOGO ===
            $logoPath = __DIR__ . '/../../../imagenes/studio/logosinfondo.png';
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 10, 8, 35);
            }

            // === TÍTULO CON RANGO DE FECHAS ===
            $pdf->SetFont('Arial', 'B', 18);
            $pdf->SetTextColor(26, 42, 68); // #1A2A44
            $pdf->Cell(0, 15, utf8_decode('REPORTE DE COMPRAS'), 0, 1, 'C');

            $pdf->SetFont('Arial', 'I', 11);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 8, utf8_decode("Del {$f_inicio} al {$f_fin}"), 0, 1, 'C');
            $pdf->Ln(8);

            // === RESUMEN ===
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 8, utf8_decode('Resumen del Período'), 0, 1, 'L');

            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 7, utf8_decode('Total de Compras: ') . ($resumen['num_compras'] ?? 0), 0, 1, 'L');
            $pdf->Cell(0, 7, utf8_decode('Total Gastado: S/ ') . number_format($resumen['total_compras'] ?? 0, 2), 0, 1, 'L');
            $pdf->Ln(8);

            // === TABLA ===
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetFillColor(26, 42, 68); // #1A2A44
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(200, 200, 200);

            $w = [20, 65, 30, 35, 30];
            $headers = ['ID', 'Proveedor', 'Fecha', 'Total (S/)', 'Estado'];

            for ($i = 0; $i < count($headers); $i++) {
                $pdf->Cell($w[$i], 10, utf8_decode($headers[$i]), 1, 0, 'C', true);
            }
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(248, 249, 250);

            if (!empty($compras)) {
                $fill = false;
                foreach ($compras as $c) {
                    $pdf->Cell($w[0], 9, $c['id_compra'], 1, 0, 'C', $fill);
                    $pdf->Cell($w[1], 9, utf8_decode($c['nombre_proveedor']), 1, 0, 'L', $fill);
                    $pdf->Cell($w[2], 9, date('d/m/Y', strtotime($c['fecha_compra'])), 1, 0, 'C', $fill);
                    $pdf->Cell($w[3], 9, number_format($c['total_compra'], 2), 1, 0, 'R', $fill);
                    $pdf->Cell($w[4], 9, utf8_decode(ucfirst($c['estado'])), 1, 1, 'C', $fill);
                    $fill = !$fill;
                }
            } else {
                $pdf->SetFont('Arial', 'I', 11);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(array_sum($w), 12, utf8_decode('No hay compras en este periodo.'), 1, 1, 'C');
            }

            // === PIE DE PÁGINA ===
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 8, utf8_decode('Generado el ') . date('d/m/Y \a \l\a\s H:i'), 0, 1, 'C');

            // === DESCARGA ===
            $filename = 'reporte_compras_' . date('Ymd_His') . '.pdf';
            $pdf->Output('D', $filename);
            exit;
        }

        // VISTA NORMAL
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/compras/reporte.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // Helpers
    private function verificarSesion()
    {
        if (!$this->sesionActiva()) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
    }

    private function sesionActiva()
    {
        return isset($_SESSION['id_usuario']);
    }
}
