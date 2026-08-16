<?php
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Producto.php';

// Librerías PDF
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';
define('FPDF_FONTPATH', __DIR__ . '/../../libs/fpdf/font/');

// Librerías Excel (PhpSpreadsheet)
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReporteController
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    // --- Menú principal de reportes ---
    public function index()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    public function inventario()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Cargar todos los productos activos
        $productoModel = new Producto($this->db);
        $productos = $productoModel->obtenerTodos();

        // Calcular el valor total del inventario a precio de costo
        $valor_total = 0;
        foreach ($productos as $p) {
            $valor_total += $p['stock'] * $p['precio_compra'];
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/inventario.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // --- Reporte de ventas ---
    public function ventas()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $ventaModel = new Venta($this->db);

        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-t');
        $accion       = $_GET['accion'] ?? 'ver';

        // --- RESUMEN GENERAL ---
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS num_ventas, SUM(total_venta) AS total_ingresos
            FROM ventas
            WHERE estado = 'completada' AND DATE(fecha_venta) BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $resumen = $stmt->get_result()->fetch_assoc();

        // --- LISTADO DETALLADO ---
        $stmt = $this->db->prepare("
            SELECT v.fecha_venta,
                   COALESCE(c.nombre_cliente, v.numero_cliente) AS cliente,
                   v.total_venta
            FROM ventas v
            LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
            WHERE v.estado = 'completada' AND DATE(v.fecha_venta) BETWEEN ? AND ?
            ORDER BY v.fecha_venta ASC
        ");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $ventas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // --- TOP 5 PRODUCTOS MÁS VENDIDOS ---
        $top_productos = $ventaModel->getTopProductos($fecha_inicio, $fecha_fin);

        // --- EXPORTAR A EXCEL ---
        if ($accion === 'excel') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Ventas');

            $sheet->setCellValue('A1', 'Reporte de Ventas');
            $sheet->mergeCells('A1:C1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', 'Total de Ingresos:');
            $sheet->setCellValue('B2', $resumen['total_ingresos'] ?? 0);
            $sheet->setCellValue('A3', 'Número de Ventas:');
            $sheet->setCellValue('B3', $resumen['num_ventas'] ?? 0);

            $sheet->setCellValue('A5', 'Fecha');
            $sheet->setCellValue('B5', 'Cliente');
            $sheet->setCellValue('C5', 'Total');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A5:C5')->applyFromArray($headerStyle);

            $row = 6;
            if (!empty($ventas)) {
                foreach ($ventas as $v) {
                    $sheet->setCellValue('A' . $row, $v['fecha_venta']);
                    $sheet->setCellValue('B' . $row, $v['cliente']);
                    $sheet->setCellValue('C' . $row, $v['total_venta']);
                    $row++;
                }
            } else {
                $sheet->setCellValue('A6', 'No hay ventas en este periodo');
                $sheet->mergeCells('A6:C6');
            }

            // --- Top productos (debajo de las ventas) ---
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Top 5 Productos Más Vendidos');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            $sheet->setCellValue("A{$row}", 'Producto');
            $sheet->setCellValue("B{$row}", 'Cantidad Vendida');
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($headerStyle);
            $row++;

            if (!empty($top_productos)) {
                foreach ($top_productos as $p) {
                    $sheet->setCellValue("A{$row}", $p['nombre']);
                    $sheet->setCellValue("B{$row}", $p['total_cantidad']);
                    $row++;
                }
            } else {
                $sheet->setCellValue("A{$row}", 'No hay productos vendidos');
                $sheet->mergeCells("A{$row}:B{$row}");
            }

            foreach (range('A', 'C') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="ventas.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }

        // --- EXPORTAR A PDF ---
        if ($accion === 'pdf') {
            $pdf = new FPDF('L', 'mm', 'A4');
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, utf8_decode("Reporte de Ventas"), 0, 1, 'C');
            $pdf->Ln(5);

            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 8, "Total de Ingresos: " . number_format($resumen['total_ingresos'] ?? 0, 2), 0, 1);
            $pdf->Cell(0, 10, utf8_decode("Número de Ventas: " . $resumen['num_ventas']), 0, 1);
            $pdf->Ln(5);

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(76, 175, 80);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(50, 10, 'Fecha', 1, 0, 'C', true);
            $pdf->Cell(110, 10, 'Cliente', 1, 0, 'C', true);
            $pdf->Cell(50, 10, 'Total', 1, 1, 'C', true);

            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            if (!empty($ventas)) {
                foreach ($ventas as $v) {
                    $pdf->Cell(50, 10, $v['fecha_venta'], 1, 0, 'C');
                    $pdf->Cell(110, 10, utf8_decode($v['cliente']), 1, 0, 'L');
                    $pdf->Cell(50, 10, number_format($v['total_venta'], 2), 1, 1, 'R');
                }
            } else {
                $pdf->Cell(0, 10, utf8_decode("No hay ventas en este periodo"), 1, 1, 'C');
            }

            // --- Sección Top 5 productos ---
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->Cell(0, 10, utf8_decode("Top 5 Productos Más Vendidos"), 0, 1, 'L');

            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetFillColor(33, 150, 243);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(140, 10, 'Producto', 1, 0, 'C', true);
            $pdf->Cell(50, 10, 'Cantidad Vendida', 1, 1, 'C', true);

            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            if (!empty($top_productos)) {
                foreach ($top_productos as $p) {
                    $pdf->Cell(140, 10, utf8_decode($p['nombre']), 1, 0, 'L');
                    $pdf->Cell(50, 10, $p['total_cantidad'], 1, 1, 'C');
                }
            } else {
                $pdf->Cell(0, 10, utf8_decode("No hay productos vendidos en este periodo"), 1, 1, 'C');
            }

            $pdf->Output("D", "ventas.pdf");
            exit;
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/ventas.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // --- Reporte de caja ---
    public function caja()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $sql = "
            SELECT 
                t.id_turno,
                u.nombre_completo AS usuario,
                t.monto_inicial,
                t.fecha_apertura,
                t.fecha_cierre,
                t.monto_final_sistema,
                t.monto_final_real,
                t.diferencia,
                COUNT(v.id_venta) AS num_ventas,
                IFNULL(SUM(v.total_venta), 0) AS total_ventas
            FROM turnos_caja t
            INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
            LEFT JOIN ventas v ON v.id_caja = t.id_turno
            WHERE t.estado = 'cerrado'
            GROUP BY t.id_turno
            ORDER BY t.fecha_apertura DESC
        ";
        $turnos = $this->db->query($sql);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/caja.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function stockBajo()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $productoModel = new Producto($this->db);

        // Obtener productos con bajo stock
        $data = $productoModel->obtenerProductosBajosDeStock();

        // Enriquecer los datos con información completa de cada producto
        $enrichedData = [];
        foreach ($data as $producto) {
            // Obtener información completa del producto para la imagen
            $productoCompleto = $productoModel->obtenerPorId($producto['id_producto']);

            // Determinar la URL de la imagen de manera más robusta
            $imagen_display = BASE_URL . 'assets/img/default-product.png'; // Valor por defecto

            if (!empty($productoCompleto['imagen_url'])) {
                $imagen_display = $productoCompleto['imagen_url'];
            } elseif (!empty($productoCompleto['imagen_path'])) {
                $full_path = BASE_URL . $productoCompleto['imagen_path'];
                // Verificar si la imagen existe (opcional, puede omitirse si es costoso)
                $imagen_display = $full_path;
            }

            $enrichedData[] = [
                'id_producto' => $producto['id_producto'],
                'nombre' => $producto['nombre'],
                'stock' => $producto['stock'],
                'stock_minimo' => $producto['stock_minimo'],
                'nombre_proveedor' => $producto['nombre_proveedor'],
                'imagen_display' => $imagen_display
            ];
        }

        // Pasar los datos enriquecidos directamente como array
        $productos = $enrichedData;

        // Incluir la vista
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/stock_bajo.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
    public function stockBajoPDF()
    {
        $productoModel = new Producto($this->db);
        $productos = $productoModel->obtenerProductosBajosDeStock();



        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        // === LOGO ===
        $logoPath = __DIR__ . '/../../../imagenes/studio/logosinfondo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 8, 35); // Ancho: 35mm (~100px)
        }

        // === ENCABEZADO ===
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(220, 53, 69);
        $pdf->Cell(0, 12, utf8_decode('REPORTE DE BAJO STOCK'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, utf8_decode('Productos que requieren reabastecimiento urgente'), 0, 1, 'C');
        $pdf->Ln(8);

        // === TABLA DE PRODUCTOS ===
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(220, 53, 69); // Rojo oscuro
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(200, 200, 200);

        $pdf->Cell(75, 10, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(55, 10, 'Proveedor', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Stock', 1, 0, 'C', true);
        $pdf->Cell(25, 10, utf8_decode('Mínimo'), 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 240, 240); // Fondo rojo claro para filas

        if (!empty($productos)) {
            $fill = false;
            foreach ($productos as $p) {
                $pdf->Cell(75, 9, utf8_decode($p['nombre']), 1, 0, 'L', $fill);
                $pdf->Cell(55, 9, utf8_decode($p['nombre_proveedor'] ?? 'Sin proveedor'), 1, 0, 'L', $fill);
                $pdf->Cell(25, 9, $p['stock'], 1, 0, 'C', $fill);
                $pdf->Cell(25, 9, $p['stock_minimo'], 1, 1, 'C', $fill);
                $fill = !$fill;
            }
        } else {
            $pdf->SetFont('Arial', 'I', 11);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(180, 12, utf8_decode('¡Felicidades! No hay productos con bajo stock.'), 1, 1, 'C');
        }

        // === PIE DE PÁGINA ===
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 8, utf8_decode('Generado el ') . date('d/m/Y H:i'), 0, 1, 'C');

        $pdf->Output('D', 'reporte_stock_bajo_' . date('Ymd') . '.pdf');
        exit;
    }
    public function stockBajoExcel()
    {
        // === 1. LIMPIAR TODO BUFFER PARA EVITAR SALIDA PREVIA ===
        if (ob_get_length()) ob_clean();
        while (ob_get_level() > 0) ob_end_clean();

        // === 2. EVITAR ERRORES QUE ROMPAN EL ARCHIVO ===
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        try {
            $productoModel = new Producto($this->db);
            $productos = $productoModel->obtenerProductosBajosDeStock();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stock Bajo');

            // === TÍTULO ===
            $sheet->setCellValue('A1', 'REPORTE DE PRODUCTOS CON BAJO STOCK');
            $sheet->mergeCells('A1:D1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'DC3545']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            $sheet->setCellValue('A2', 'Productos que requieren reabastecimiento urgente');
            $sheet->mergeCells('A2:D2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '6C757D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // === ENCABEZADOS ===
            $headers = ['Producto', 'Proveedor', 'Stock Actual', 'Stock Mínimo'];
            $sheet->fromArray($headers, null, 'A4');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC3545']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A4:D4')->applyFromArray($headerStyle);

            // === DATOS ===
            $row = 5;
            if (!empty($productos)) {
                foreach ($productos as $p) {
                    $sheet->setCellValue("A{$row}", $p['nombre'] ?? '');
                    $sheet->setCellValue("B{$row}", $p['nombre_proveedor'] ?? 'Sin proveedor');
                    $sheet->setCellValue("C{$row}", (int)($p['stock'] ?? 0));
                    $sheet->setCellValue("D{$row}", (int)($p['stock_minimo'] ?? 0));

                    // Resaltar si stock es crítico
                    if (($p['stock'] ?? 0) <= 3) {
                        $sheet->getStyle("A{$row}:D{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFEBEE');
                        $sheet->getStyle("C{$row}")->getFont()->getColor()->setRGB('D32F2F');
                    }

                    $row++;
                }
            } else {
                $sheet->setCellValue("A{$row}", '¡Felicidades! No hay productos con bajo stock.');
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '28A745']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $row++;
            }

            // === AUTOAJUSTE DE COLUMNAS ===
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // === ESTILO DE TABLA ===
            $lastRow = $row - 1;
            $dataRange = "A4:D{$lastRow}";
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);

            // === PIE DE PÁGINA ===
            $sheet->setCellValue("A{$row}", 'Generado: ' . date('d/m/Y H:i'));
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '888888']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // === CONFIGURAR HEADERS CORRECTOS ===
            $filename = 'stock_bajo_' . date('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Expires: 0');

            // === GUARDAR EN OUTPUT ===
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            // === LIMPIAR Y SALIR ===
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (Exception $e) {
            // Si hay error, mostrar mensaje claro (solo en desarrollo)
            if (defined('DEBUG') && constant('DEBUG')) {
                die("Error al generar Excel: " . $e->getMessage());
            } else {
                http_response_code(500);
                die("Error interno al generar el reporte.");
            }
        }

        exit;
    }

    // Agrega estos métodos a tu ReporteController class

    public function exportarInventario()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $accion = $_GET['accion'] ?? 'excel';
        $tipo_reporte = $_GET['tipo_reporte'] ?? 'inventario_completo';

        // Cargar todos los productos activos
        $productoModel = new Producto($this->db);
        $productos = $productoModel->obtenerTodos();

        // Calcular estadísticas
        $valor_total_costo = 0;
        $valor_total_venta = 0;
        $total_productos = 0;
        $productos_bajo_stock = 0;
        $productos_sin_precio_compra = 0;

        foreach ($productos as $p) {
            if ($p['activo'] == 1) {
                $total_productos++;

                if ($p['precio_compra'] <= 0) {
                    $productos_sin_precio_compra++;
                } else {
                    $valor_total_costo += $p['stock'] * $p['precio_compra'];
                    $valor_total_venta += $p['stock'] * $p['precio_venta'];

                    if ($p['stock'] <= $p['stock_minimo']) {
                        $productos_bajo_stock++;
                    }
                }
            }
        }

        $ganancia_potencial = $valor_total_venta - $valor_total_costo;
        $margen_ganancia = $valor_total_costo > 0 ? ($ganancia_potencial / $valor_total_costo) * 100 : 0;

        if ($accion === 'excel') {
            $this->exportarInventarioExcel($productos, [
                'valor_total_costo' => $valor_total_costo,
                'valor_total_venta' => $valor_total_venta,
                'ganancia_potencial' => $ganancia_potencial,
                'margen_ganancia' => $margen_ganancia,
                'total_productos' => $total_productos,
                'productos_bajo_stock' => $productos_bajo_stock,
                'productos_sin_precio_compra' => $productos_sin_precio_compra
            ]);
        } elseif ($accion === 'pdf') {
            $this->exportarInventarioPDF($productos, [
                'valor_total_costo' => $valor_total_costo,
                'valor_total_venta' => $valor_total_venta,
                'ganancia_potencial' => $ganancia_potencial,
                'margen_ganancia' => $margen_ganancia,
                'total_productos' => $total_productos,
                'productos_bajo_stock' => $productos_bajo_stock,
                'productos_sin_precio_compra' => $productos_sin_precio_compra
            ]);
        }
    }

    private function exportarInventarioExcel($productos, $estadisticas)
    {
        // Limpiar buffers
        if (ob_get_length()) ob_clean();
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Inventario Completo');

            // === TÍTULO Y ESTADÍSTICAS ===
            $sheet->setCellValue('A1', 'REPORTE DE INVENTARIO COMPLETO');
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '2E86C1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // Estadísticas
            $sheet->setCellValue('A2', 'Valor Total del Inventario:');
            $sheet->setCellValue('B2', $estadisticas['valor_total_costo']);
            $sheet->setCellValue('A3', 'Ganancia Potencial:');
            $sheet->setCellValue('B3', $estadisticas['ganancia_potencial']);
            $sheet->setCellValue('A4', 'Margen de Ganancia:');
            $sheet->setCellValue('B4', $estadisticas['margen_ganancia'] . '%');
            $sheet->setCellValue('A5', 'Total Productos:');
            $sheet->setCellValue('B5', $estadisticas['total_productos']);
            $sheet->setCellValue('A6', 'Productos con Stock Bajo:');
            $sheet->setCellValue('B6', $estadisticas['productos_bajo_stock']);
            $sheet->setCellValue('A7', 'Productos sin Precio Compra:');
            $sheet->setCellValue('B7', $estadisticas['productos_sin_precio_compra']);

            // === ENCABEZADOS DE TABLA ===
            $headers = [
                'Producto',
                'Categoría',
                'Stock Actual',
                'Stock Mínimo',
                'Estado',
                'Precio Costo',
                'Precio Venta',
                'Ganancia Unit.',
                'Valor Total',
                'Proveedor'
            ];

            $sheet->fromArray($headers, null, 'A9');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A9:J9')->applyFromArray($headerStyle);

            // === DATOS DE PRODUCTOS ===
            $row = 10;
            if (!empty($productos)) {
                foreach ($productos as $p) {
                    if ($p['activo'] == 1) {
                        $has_price = $p['precio_compra'] > 0;
                        $valor_total_producto = $has_price ? $p['stock'] * $p['precio_compra'] : 0;
                        $ganancia_unitaria = $has_price ? ($p['precio_venta'] - $p['precio_compra']) : null;

                        // Determinar estado
                        $estado = 'Normal';
                        if ($p['stock'] <= 0) {
                            $estado = 'Sin Stock';
                        } elseif ($p['stock'] <= $p['stock_minimo']) {
                            $estado = 'Bajo Stock';
                        }
                        if (!$has_price) {
                            $estado = 'Sin Precio';
                        }

                        $sheet->setCellValue("A{$row}", $p['nombre'] ?? '');
                        $sheet->setCellValue("B{$row}", $p['nombre_categoria'] ?? 'Sin categoría');
                        $sheet->setCellValue("C{$row}", (int)($p['stock'] ?? 0));
                        $sheet->setCellValue("D{$row}", (int)($p['stock_minimo'] ?? 0));
                        $sheet->setCellValue("E{$row}", $estado);
                        $sheet->setCellValue("F{$row}", $has_price ? $p['precio_compra'] : 'NULO');
                        $sheet->setCellValue("G{$row}", $p['precio_venta'] ? $p['precio_venta'] : 'NULO');
                        $sheet->setCellValue("H{$row}", $ganancia_unitaria !== null ? $ganancia_unitaria : 'NULO');
                        $sheet->setCellValue("I{$row}", $has_price ? $valor_total_producto : 'NULO');
                        $sheet->setCellValue("J{$row}", $p['nombre_proveedor'] ?? 'Sin proveedor');

                        // Configurar wrap text para la columna de producto
                        $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);

                        // Configurar wrap text para categoría también
                        $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);

                        // Resaltar según estado
                        $rowStyle = [];
                        switch ($estado) {
                            case 'Sin Stock':
                                $rowStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FADBD8']]];
                                break;
                            case 'Bajo Stock':
                                $rowStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCF3CF']]];
                                break;
                            case 'Sin Precio':
                                $rowStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E8E8']]];
                                break;
                        }

                        if (!empty($rowStyle)) {
                            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray($rowStyle);
                        }

                        $row++;
                    }
                }
            }

            // === CONFIGURAR ANCHOS DE COLUMNA OPTIMIZADOS ===
            $sheet->getColumnDimension('A')->setWidth(60); // Producto - MUCHO más ancho
            $sheet->getColumnDimension('B')->setWidth(25); // Categoría
            $sheet->getColumnDimension('C')->setWidth(12); // Stock Actual
            $sheet->getColumnDimension('D')->setWidth(12); // Stock Mínimo
            $sheet->getColumnDimension('E')->setWidth(15); // Estado
            $sheet->getColumnDimension('F')->setWidth(15); // Precio Costo
            $sheet->getColumnDimension('G')->setWidth(15); // Precio Venta
            $sheet->getColumnDimension('H')->setWidth(15); // Ganancia Unit.
            $sheet->getColumnDimension('I')->setWidth(15); // Valor Total
            $sheet->getColumnDimension('J')->setWidth(25); // Proveedor

            // Configurar altura de fila automática para permitir texto multilínea
            for ($i = 10; $i <= $row; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(-1); // Altura automática
            }

            // === ESTILO DE TABLA ===
            $lastRow = $row - 1;
            $dataRange = "A9:J{$lastRow}";
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);

            // === PIE DE PÁGINA ===
            $sheet->setCellValue("A{$row}", 'Generado: ' . date('d/m/Y H:i'));
            $sheet->mergeCells("A{$row}:J{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '888888']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // === CONFIGURAR HEADERS ===
            $filename = 'inventario_completo_' . date('Ymd_His') . '.xlsx';

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
            if (defined('DEBUG') && constant('DEBUG')) {
                die("Error al generar Excel: " . $e->getMessage());
            } else {
                http_response_code(500);
                die("Error interno al generar el reporte.");
            }
        }
        exit;
    }

    private function exportarInventarioPDF($productos, $estadisticas)
    {
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        // === LOGO ===
        $logoPath = __DIR__ . '/../../../imagenes/studio/logosinfondo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 8, 35);
        }

        // === ENCABEZADO ===
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(46, 134, 193);
        $pdf->Cell(0, 12, utf8_decode('REPORTE DE INVENTARIO COMPLETO'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, utf8_decode('Estado actual del stock y valoración del inventario'), 0, 1, 'C');

        // === ESTADÍSTICAS ===
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Cell(60, 8, utf8_decode('Valor Total del Inventario:'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 8, getMoneda() . number_format($estadisticas['valor_total_costo'], 2), 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 8, utf8_decode('Ganancia Potencial:'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 8, getMoneda() . number_format($estadisticas['ganancia_potencial'], 2), 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 8, utf8_decode('Margen de Ganancia:'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 8, number_format($estadisticas['margen_ganancia'], 1) . '%', 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 8, utf8_decode('Total Productos:'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 8, $estadisticas['total_productos'], 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 8, utf8_decode('Productos con Stock Bajo:'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 8, $estadisticas['productos_bajo_stock'], 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 8, utf8_decode('Productos sin Precio Compra:'), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 8, $estadisticas['productos_sin_precio_compra'], 0, 1);

        $pdf->Ln(8);

        // === TABLA DE PRODUCTOS ===
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(46, 134, 193);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(200, 200, 200);

        // Encabezados de tabla - AMPLIADOS
        $pdf->Cell(70, 8, 'PRODUCTO', 1, 0, 'C', true); // Ampliado de 45 a 70
        $pdf->Cell(35, 8, 'CATEGORIA', 1, 0, 'C', true); // Ampliado de 30 a 35
        $pdf->Cell(15, 8, 'STOCK', 1, 0, 'C', true);
        $pdf->Cell(15, 8, utf8_decode('MÍN.'), 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'ESTADO', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'P. COSTO', 1, 0, 'C', true); // Ampliado de 20 a 25
        $pdf->Cell(25, 8, 'P. VENTA', 1, 0, 'C', true); // Ampliado de 20 a 25
        $pdf->Cell(25, 8, 'GAN. UNIT.', 1, 0, 'C', true); // Ampliado de 20 a 25
        $pdf->Cell(30, 8, 'VALOR TOTAL', 1, 1, 'C', true); // Ampliado de 25 a 30

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);

        if (!empty($productos)) {
            foreach ($productos as $p) {
                if ($p['activo'] == 1) {
                    $has_price = $p['precio_compra'] > 0;
                    $valor_total_producto = $has_price ? $p['stock'] * $p['precio_compra'] : 0;
                    $ganancia_unitaria = $has_price ? ($p['precio_venta'] - $p['precio_compra']) : null;

                    // Determinar estado y color
                    $estado = 'Normal';
                    $fill_color = null;

                    if ($p['stock'] <= 0) {
                        $estado = 'Sin Stock';
                        $fill_color = [255, 220, 220]; // Rojo claro
                    } elseif ($p['stock'] <= $p['stock_minimo']) {
                        $estado = 'Bajo Stock';
                        $fill_color = [255, 250, 220]; // Amarillo claro
                    }
                    if (!$has_price) {
                        $estado = 'Sin Precio';
                        $fill_color = [240, 240, 240]; // Gris claro
                    }

                    // Aplicar color de fondo si es necesario
                    if ($fill_color) {
                        $pdf->SetFillColor($fill_color[0], $fill_color[1], $fill_color[2]);
                    }

                    // Celda de producto más grande - permite nombres más largos
                    $pdf->Cell(70, 7, utf8_decode(substr($p['nombre'] ?? '', 0, 35)), 1, 0, 'L', (bool)$fill_color); // Ampliado de 25 a 35 caracteres
                    $pdf->Cell(35, 7, utf8_decode(substr($p['nombre_categoria'] ?? 'Sin categoría', 0, 20)), 1, 0, 'L', (bool)$fill_color); // Ampliado de 15 a 20 caracteres
                    $pdf->Cell(15, 7, $p['stock'] ?? 0, 1, 0, 'C', (bool)$fill_color);
                    $pdf->Cell(15, 7, $p['stock_minimo'] ?? 0, 1, 0, 'C', (bool)$fill_color);
                    $pdf->Cell(20, 7, $estado, 1, 0, 'C', (bool)$fill_color);
                    $pdf->Cell(25, 7, $has_price ? getMoneda() . number_format($p['precio_compra'], 2) : 'NULO', 1, 0, 'R', (bool)$fill_color);
                    $pdf->Cell(25, 7, $p['precio_venta'] ? getMoneda() . number_format($p['precio_venta'], 2) : 'NULO', 1, 0, 'R', (bool)$fill_color);
                    $pdf->Cell(25, 7, $ganancia_unitaria !== null ? getMoneda() . number_format($ganancia_unitaria, 2) : 'NULO', 1, 0, 'R', (bool)$fill_color);
                    $pdf->Cell(30, 7, $has_price ? getMoneda() . number_format($valor_total_producto, 2) : 'NULO', 1, 1, 'R', (bool)$fill_color);

                    // Restaurar color de fondo
                    if ($fill_color) {
                        $pdf->SetFillColor(255, 255, 255);
                    }
                }
            }
        }

        // === PIE DE PÁGINA ===
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 8, utf8_decode('Generado el ') . date('d/m/Y H:i'), 0, 1, 'C');

        $pdf->Output('D', 'reporte_inventario_completo_' . date('Ymd') . '.pdf');
        exit;
    }

    // En controllers/ReporteController.php - Agregar estos métodos

    /**
     * 🔹 Reporte de ventas por fecha con filtros por método de pago
     */
    public function ventasPorFecha()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $ventaModel = new Venta($this->db);

        // Filtros
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $metodo_pago = $_GET['metodo_pago'] ?? '';
        $accion = $_GET['accion'] ?? 'ver';

        // Obtener datos
        $ventas = $ventaModel->obtenerVentasPorFecha($fecha_inicio, $fecha_fin, $metodo_pago);
        $resumenMetodos = $ventaModel->obtenerResumenVentasPorMetodoPago($fecha_inicio, $fecha_fin);
        $ventasDiarias = $ventaModel->obtenerVentasDiarias($fecha_inicio, $fecha_fin);

        // Calcular totales generales
        $totales = $this->calcularTotalesVentas($ventas);

        // Métodos de pago disponibles
        $metodos_disponibles = ['efectivo', 'yape', 'plin', 'transferencia', 'saldo'];

        // Exportar a Excel
        if ($accion === 'excel') {
            $this->exportarVentasPorFechaExcel($ventas, $totales, $resumenMetodos, $fecha_inicio, $fecha_fin, $metodo_pago);
        }

        // Exportar a PDF
        if ($accion === 'pdf') {
            $this->exportarVentasPorFechaPDF($ventas, $totales, $resumenMetodos, $fecha_inicio, $fecha_fin, $metodo_pago);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/ventas_fecha.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * Calcular totales de ventas
     */
    private function calcularTotalesVentas($ventas)
    {
        $totales = [
            'total_ventas' => 0,
            'efectivo' => 0,
            'yape' => 0,
            'plin' => 0,
            'transferencia' => 0,
            'saldo' => 0,
            'num_ventas' => count($ventas),
            'promedio_venta' => 0
        ];

        foreach ($ventas as $venta) {
            $totales['total_ventas'] += $venta['total_venta'];

            switch ($venta['metodo_pago']) {
                case 'efectivo':
                    $totales['efectivo'] += $venta['total_venta'];
                    break;
                case 'yape':
                    $totales['yape'] += $venta['total_venta'];
                    break;
                case 'plin':
                    $totales['plin'] += $venta['total_venta'];
                    break;
                case 'transferencia':
                    $totales['transferencia'] += $venta['total_venta'];
                    break;
                case 'saldo':
                    $totales['saldo'] += $venta['total_venta'];
                    break;
            }
        }

        $totales['promedio_venta'] = $totales['num_ventas'] > 0 ?
            $totales['total_ventas'] / $totales['num_ventas'] : 0;

        return $totales;
    }

    /**
     * Exportar a Excel
     */
    private function exportarVentasPorFechaExcel($ventas, $totales, $resumenMetodos, $fecha_inicio, $fecha_fin, $metodo_pago)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Ventas por Fecha');

            // Título
            $sheet->setCellValue('A1', 'Reporte de Ventas por Fecha');
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Periodo y filtros
            $filtro_metodo = $metodo_pago ? "Método: " . ucfirst($metodo_pago) : "Todos los métodos";
            $sheet->setCellValue('A2', "Periodo: {$fecha_inicio} al {$fecha_fin} - {$filtro_metodo}");
            $sheet->mergeCells('A2:H2');
            $sheet->getStyle('A2')->getFont()->setItalic(true);

            // Resumen general
            $sheet->setCellValue('A4', 'RESUMEN GENERAL');
            $sheet->getStyle('A4')->getFont()->setBold(true);

            $sheet->setCellValue('A5', 'Total Ventas:');
            $sheet->setCellValue('B5', $totales['num_ventas']);
            $sheet->setCellValue('A6', 'Monto Total:');
            $sheet->setCellValue('B6', $totales['total_ventas']);
            $sheet->setCellValue('A7', 'Promedio por Venta:');
            $sheet->setCellValue('B7', $totales['promedio_venta']);

            // Resumen por método de pago
            $sheet->setCellValue('D4', 'RESUMEN POR MÉTODO DE PAGO');
            $sheet->getStyle('D4')->getFont()->setBold(true);

            $row = 5;
            foreach ($resumenMetodos as $resumen) {
                $sheet->setCellValue("D{$row}", ucfirst($resumen['metodo_pago']));
                $sheet->setCellValue("E{$row}", $resumen['num_ventas']);
                $sheet->setCellValue("F{$row}", $resumen['total_ventas']);
                $sheet->setCellValue("G{$row}", $resumen['promedio_venta']);
                $row++;
            }

            // Detalle de ventas
            $sheet->setCellValue('A9', 'DETALLE DE VENTAS');
            $sheet->mergeCells('A9:H9');
            $sheet->getStyle('A9')->getFont()->setBold(true);

            // Encabezados de tabla
            $headers = ['Fecha', 'Cliente', 'Método Pago', 'Total', 'Descuento Sellos', 'Descuento Manual', 'Items', 'Vendedor'];
            $sheet->fromArray($headers, null, 'A10');

            // Estilo encabezados
            $sheet->getStyle('A10:H10')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Datos de ventas
            $row = 11;
            foreach ($ventas as $venta) {
                $sheet->setCellValue("A{$row}", $venta['fecha_venta']);
                $sheet->setCellValue("B{$row}", $venta['cliente']);
                $sheet->setCellValue("C{$row}", ucfirst($venta['metodo_pago']));
                $sheet->setCellValue("D{$row}", $venta['total_venta']);
                $sheet->setCellValue("E{$row}", $venta['descuento_sellos'] ?? 0);
                $sheet->setCellValue("F{$row}", $venta['descuento_manual'] ?? 0);
                $sheet->setCellValue("G{$row}", $venta['num_items']);
                $sheet->setCellValue("H{$row}", $venta['vendedor']);
                $row++;
            }

            // Autoajustar columnas
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Configurar respuesta
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="ventas_por_fecha.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            error_log("Error al generar Excel: " . $e->getMessage());
            $_SESSION['mensaje'] = 'Error al generar el reporte Excel';
            $_SESSION['mensaje_tipo'] = 'danger';
        }
    }

    /**
     * Exportar a PDF
     */
    private function exportarVentasPorFechaPDF($ventas, $totales, $resumenMetodos, $fecha_inicio, $fecha_fin, $metodo_pago)
    {
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        // Logo
        $logoPath = __DIR__ . '/../../../imagenes/studio/logosinfondo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 8, 35);
        }

        // Título
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(46, 134, 193);
        $pdf->Cell(0, 12, utf8_decode('Reporte de Ventas por Fecha'), 0, 1, 'C');

        // Periodo y filtros
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $filtro_metodo = $metodo_pago ? "Método: " . ucfirst($metodo_pago) : "Todos los métodos";
        $pdf->Cell(0, 8, "Periodo: {$fecha_inicio} al {$fecha_fin} - {$filtro_metodo}", 0, 1, 'C');

        // Resumen general
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, utf8_decode('Resumen General'), 0, 1);

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(50, 8, utf8_decode('Total de Ventas:'), 0, 0);
        $pdf->Cell(30, 8, $totales['num_ventas'], 0, 1);

        $pdf->Cell(50, 8, utf8_decode('Monto Total:'), 0, 0);
        $pdf->Cell(30, 8, getMoneda() . number_format($totales['total_ventas'], 2), 0, 1);

        $pdf->Cell(50, 8, utf8_decode('Promedio por Venta:'), 0, 0);
        $pdf->Cell(30, 8, getMoneda() . number_format($totales['promedio_venta'], 2), 0, 1);

        // Detalle de ventas
        $pdf->Ln(8);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, utf8_decode('Detalle de Ventas'), 0, 1);

        // Encabezados de tabla
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(46, 134, 193);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(25, 8, 'FECHA', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'CLIENTE', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'MÉTODO', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'DESC. SELLOS', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'DESC. MANUAL', 1, 0, 'C', true);
        $pdf->Cell(15, 8, 'ITEMS', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'VENDEDOR', 1, 1, 'C', true);

        // Datos
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($ventas as $venta) {
            $pdf->Cell(25, 7, substr($venta['fecha_venta'], 0, 10), 1, 0, 'C');
            $pdf->Cell(50, 7, substr(utf8_decode($venta['cliente']), 0, 25), 1, 0, 'L');
            $pdf->Cell(25, 7, strtoupper(substr($venta['metodo_pago'], 0, 4)), 1, 0, 'C');
            $pdf->Cell(25, 7, getMoneda() . number_format($venta['total_venta'], 2), 1, 0, 'R');
            $pdf->Cell(25, 7, $venta['descuento_sellos'] ?? '0.00', 1, 0, 'R');
            $pdf->Cell(25, 7, $venta['descuento_manual'] ?? '0.00', 1, 0, 'R');
            $pdf->Cell(15, 7, $venta['num_items'], 1, 0, 'C');
            $pdf->Cell(40, 7, substr(utf8_decode($venta['vendedor']), 0, 20), 1, 1, 'L');
        }

        $pdf->Output('D', 'ventas_por_fecha_' . date('Ymd') . '.pdf');
        exit;
    }

        // 🔹 NUEVO: Reporte de Métodos de Pago
    public function metodosPago()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $ventaModel = new Venta($this->db);

        // Filtros
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $accion = $_GET['accion'] ?? 'ver';

        // Obtener distribución de métodos de pago
        $distribucion = $ventaModel->obtenerDistribucionMetodosPago($fecha_inicio, $fecha_fin);
        
        // Obtener ventas por método de pago (para gráficos)
        $ventasPorMetodo = $ventaModel->obtenerVentasPorMetodoPago($fecha_inicio, $fecha_fin);
        
        // Obtener tendencias mensuales
        $tendencias = $ventaModel->obtenerTendenciasMetodosPago();

        // Calcular total general
        $total_general = 0;
        foreach ($distribucion as $metodo) {
            $total_general += $metodo['monto_total'];
        }

        // Exportar a Excel
        if ($accion === 'excel') {
            $this->exportarMetodosPagoExcel($distribucion, $total_general, $fecha_inicio, $fecha_fin);
        }

        // Exportar a PDF
        if ($accion === 'pdf') {
            $this->exportarMetodosPagoPDF($distribucion, $total_general, $fecha_inicio, $fecha_fin);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/metodos_pago.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // 🔹 NUEVO: Reporte de Productos Más Vendidos
    public function topProductos()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $ventaModel = new Venta($this->db);
        $productoModel = new Producto($this->db);

        // Filtros
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $tipo_ranking = $_GET['tipo_ranking'] ?? 'cantidad'; // 'cantidad' o 'revenue'
        $limite = $_GET['limite'] ?? 10;
        $accion = $_GET['accion'] ?? 'ver';

        // Obtener datos según el tipo de ranking
        if ($tipo_ranking === 'revenue') {
            $topProductos = $ventaModel->obtenerTopProductosPorRevenue($fecha_inicio, $fecha_fin, $limite);
        } else {
            $topProductos = $ventaModel->obtenerTopProductosPorCantidad($fecha_inicio, $fecha_fin, $limite);
        }

        // Obtener productos de baja rotación
        $bajaRotacion = $productoModel->obtenerProductosBajaRotacion();

        // Calcular totales
        $totales = $this->calcularTotalesProductos($topProductos, $tipo_ranking);

        // Exportar a Excel
        if ($accion === 'excel') {
            $this->exportarTopProductosExcel($topProductos, $totales, $fecha_inicio, $fecha_fin, $tipo_ranking);
        }

        // Exportar a PDF
        if ($accion === 'pdf') {
            $this->exportarTopProductosPDF($topProductos, $totales, $fecha_inicio, $fecha_fin, $tipo_ranking);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/top_productos.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // 🔹 NUEVO: Análisis de Rentabilidad
    public function rentabilidad()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $ventaModel = new Venta($this->db);
        $productoModel = new Producto($this->db);

        // Filtros
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $accion = $_GET['accion'] ?? 'ver';

        // Obtener datos de rentabilidad
        $margenesProductos = $productoModel->obtenerMargenesProductos();
        $rentabilidadGeneral = $ventaModel->obtenerRentabilidadGeneral($fecha_inicio, $fecha_fin);
        $productosMasRentables = $ventaModel->obtenerProductosMasRentables($fecha_inicio, $fecha_fin);

        // Calcular KPIs adicionales
        $kpis = $this->calcularKPIsRentabilidad($rentabilidadGeneral, $margenesProductos);

        // Exportar a Excel
        if ($accion === 'excel') {
            $this->exportarRentabilidadExcel($margenesProductos, $rentabilidadGeneral, $productosMasRentables, $kpis, $fecha_inicio, $fecha_fin);
        }

        // Exportar a PDF
        if ($accion === 'pdf') {
            $this->exportarRentabilidadPDF($margenesProductos, $rentabilidadGeneral, $productosMasRentables, $kpis, $fecha_inicio, $fecha_fin);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/reportes/rentabilidad.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // =========================================================================
    // MÉTODOS PRIVADOS AUXILIARES
    // =========================================================================

    /**
     * Calcular totales para productos
     */
    private function calcularTotalesProductos($productos, $tipo_ranking)
    {
        $totales = [
            'total_cantidad' => 0,
            'total_revenue' => 0,
            'total_ganancia' => 0,
            'promedio_margen' => 0
        ];

        foreach ($productos as $producto) {
            $totales['total_cantidad'] += $producto['total_vendido'] ?? 0;
            $totales['total_revenue'] += $producto['revenue_total'] ?? 0;
            $totales['total_ganancia'] += $producto['ganancia_total'] ?? 0;
        }

        if ($totales['total_revenue'] > 0) {
            $totales['promedio_margen'] = ($totales['total_ganancia'] / $totales['total_revenue']) * 100;
        }

        return $totales;
    }

    /**
     * Calcular KPIs de rentabilidad
     */
    private function calcularKPIsRentabilidad($rentabilidadGeneral, $margenesProductos)
    {
        $kpis = [
            'margen_global' => $rentabilidadGeneral['margen_global'] ?? 0,
            'productos_rentables' => 0,
            'productos_no_rentables' => 0,
            'margen_promedio' => 0,
            'roi_promedio' => 0
        ];

        $total_margen = 0;
        $total_productos = count($margenesProductos);

        foreach ($margenesProductos as $producto) {
            $margen = $producto['margen_porcentaje'] ?? 0;
            $total_margen += $margen;

            if ($margen > 20) {
                $kpis['productos_rentables']++;
            } elseif ($margen <= 0) {
                $kpis['productos_no_rentables']++;
            }
        }

        if ($total_productos > 0) {
            $kpis['margen_promedio'] = $total_margen / $total_productos;
        }

        return $kpis;
    }

    // =========================================================================
    // MÉTODOS DE EXPORTACIÓN EXCEL
    // =========================================================================

    /**
     * Exportar Métodos de Pago a Excel
     */
    private function exportarMetodosPagoExcel($distribucion, $total_general, $fecha_inicio, $fecha_fin)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Métodos de Pago');

            // Título
            $sheet->setCellValue('A1', 'Reporte de Distribución de Métodos de Pago');
            $sheet->mergeCells('A1:D1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Periodo
            $sheet->setCellValue('A2', "Periodo: {$fecha_inicio} al {$fecha_fin}");
            $sheet->mergeCells('A2:D2');
            $sheet->getStyle('A2')->getFont()->setItalic(true);

            // Total general
            $sheet->setCellValue('A3', 'Total General:');
            $sheet->setCellValue('B3', $total_general);
            $sheet->getStyle('A3:B3')->getFont()->setBold(true);

            // Encabezados
            $headers = ['Método de Pago', 'N° Ventas', 'Monto Total', 'Porcentaje'];
            $sheet->fromArray($headers, null, 'A5');

            // Estilo encabezados
            $sheet->getStyle('A5:D5')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Datos
            $row = 6;
            foreach ($distribucion as $metodo) {
                $sheet->setCellValue("A{$row}", ucfirst($metodo['metodo_pago']));
                $sheet->setCellValue("B{$row}", $metodo['total_ventas']);
                $sheet->setCellValue("C{$row}", $metodo['monto_total']);
                $sheet->setCellValue("D{$row}", $metodo['porcentaje'] . '%');
                $row++;
            }

            // Autoajustar columnas
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Configurar respuesta
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="metodos_pago.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            error_log("Error al generar Excel: " . $e->getMessage());
            $_SESSION['mensaje'] = 'Error al generar el reporte Excel';
            $_SESSION['mensaje_tipo'] = 'danger';
        }
    }

    /**
     * Exportar Top Productos a Excel
     */
    private function exportarTopProductosExcel($productos, $totales, $fecha_inicio, $fecha_fin, $tipo_ranking)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Top Productos');

            // Título según tipo de ranking
            $titulo = $tipo_ranking === 'revenue' ? 
                'Top Productos por Revenue' : 'Top Productos por Cantidad Vendida';
            
            $sheet->setCellValue('A1', $titulo);
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Periodo y totales
            $sheet->setCellValue('A2', "Periodo: {$fecha_inicio} al {$fecha_fin}");
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->getFont()->setItalic(true);

            $sheet->setCellValue('A3', 'Total Cantidad Vendida:');
            $sheet->setCellValue('B3', $totales['total_cantidad']);
            $sheet->setCellValue('A4', 'Total Revenue:');
            $sheet->setCellValue('B4', $totales['total_revenue']);
            $sheet->setCellValue('A5', 'Margen Promedio:');
            $sheet->setCellValue('B5', number_format($totales['promedio_margen'], 2) . '%');

            // Encabezados
            $headers = ['Producto', 'Cantidad Vendida', 'Precio Venta', 'Revenue Total', 'Ganancia Total', 'Margen %'];
            $sheet->fromArray($headers, null, 'A7');

            // Estilo encabezados
            $sheet->getStyle('A7:F7')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '27AE60']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Datos
            $row = 8;
            foreach ($productos as $producto) {
                $sheet->setCellValue("A{$row}", $producto['nombre']);
                $sheet->setCellValue("B{$row}", $producto['total_vendido']);
                $sheet->setCellValue("C{$row}", $producto['precio_venta'] ?? 0);
                $sheet->setCellValue("D{$row}", $producto['revenue_total'] ?? 0);
                $sheet->setCellValue("E{$row}", $producto['ganancia_total'] ?? 0);
                
                $margen = 0;
                if ($producto['revenue_total'] > 0) {
                    $margen = ($producto['ganancia_total'] / $producto['revenue_total']) * 100;
                }
                $sheet->setCellValue("F{$row}", number_format($margen, 2) . '%');
                
                $row++;
            }

            // Autoajustar columnas
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Configurar respuesta
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="top_productos.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            error_log("Error al generar Excel: " . $e->getMessage());
            $_SESSION['mensaje'] = 'Error al generar el reporte Excel';
            $_SESSION['mensaje_tipo'] = 'danger';
        }
    }

    /**
     * Exportar Rentabilidad a Excel
     */
    private function exportarRentabilidadExcel($margenesProductos, $rentabilidadGeneral, $productosMasRentables, $kpis, $fecha_inicio, $fecha_fin)
    {
        try {
            $spreadsheet = new Spreadsheet();
            
            // === HOJA 1: RESUMEN GENERAL ===
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Resumen Rentabilidad');

            // Título
            $sheet1->setCellValue('A1', 'Análisis de Rentabilidad General');
            $sheet1->mergeCells('A1:D1');
            $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Periodo
            $sheet1->setCellValue('A2', "Periodo: {$fecha_inicio} al {$fecha_fin}");
            $sheet1->mergeCells('A2:D2');
            $sheet1->getStyle('A2')->getFont()->setItalic(true);

            // KPIs principales
            $sheet1->setCellValue('A4', 'MARGEN GLOBAL:');
            $sheet1->setCellValue('B4', number_format($kpis['margen_global'], 2) . '%');
            $sheet1->getStyle('A4:B4')->getFont()->setBold(true)->setSize(12);

            $sheet1->setCellValue('A5', 'Productos Rentables:');
            $sheet1->setCellValue('B5', $kpis['productos_rentables']);
            $sheet1->setCellValue('A6', 'Productos No Rentables:');
            $sheet1->setCellValue('B6', $kpis['productos_no_rentables']);
            $sheet1->setCellValue('A7', 'Margen Promedio:');
            $sheet1->setCellValue('B7', number_format($kpis['margen_promedio'], 2) . '%');

            // === HOJA 2: PRODUCTOS MÁS RENTABLES ===
            $spreadsheet->createSheet();
            $sheet2 = $spreadsheet->getSheet(1);
            $sheet2->setTitle('Productos Rentables');

            $sheet2->setCellValue('A1', 'Top Productos Más Rentables');
            $sheet2->mergeCells('A1:F1');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $headers = ['Producto', 'Precio Costo', 'Precio Venta', 'Margen %', 'Ganancia Total', 'Cantidad Vendida'];
            $sheet2->fromArray($headers, null, 'A3');

            $sheet2->getStyle('A3:F3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '27AE60']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            $row = 4;
            foreach ($productosMasRentables as $producto) {
                $sheet2->setCellValue("A{$row}", $producto['nombre']);
                $sheet2->setCellValue("B{$row}", $producto['precio_compra']);
                $sheet2->setCellValue("C{$row}", $producto['precio_venta']);
                $sheet2->setCellValue("D{$row}", number_format($producto['margen_porcentaje'], 2) . '%');
                $sheet2->setCellValue("E{$row}", $producto['ganancia_total']);
                $sheet2->setCellValue("F{$row}", $producto['total_vendido']);
                $row++;
            }

            // Autoajustar columnas en ambas hojas
            foreach (['A','B','C','D','E','F'] as $col) {
                $sheet1->getColumnDimension($col)->setAutoSize(true);
                $sheet2->getColumnDimension($col)->setAutoSize(true);
            }

            // Volver a la primera hoja
            $spreadsheet->setActiveSheetIndex(0);

            // Configurar respuesta
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="analisis_rentabilidad.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            error_log("Error al generar Excel: " . $e->getMessage());
            $_SESSION['mensaje'] = 'Error al generar el reporte Excel';
            $_SESSION['mensaje_tipo'] = 'danger';
        }
    }

    // =========================================================================
    // MÉTODOS DE EXPORTACIÓN PDF (SIMPLIFICADOS)
    // =========================================================================

    private function exportarMetodosPagoPDF($distribucion, $total_general, $fecha_inicio, $fecha_fin)
    {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Reporte de Métodos de Pago', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, "Periodo: {$fecha_inicio} al {$fecha_fin}", 0, 1, 'C');
        $pdf->Cell(0, 10, "Total General: " . getMoneda() . number_format($total_general, 2), 0, 1, 'C');
        $pdf->Ln(10);

        // Tabla
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(60, 10, 'Método de Pago', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Ventas', 1, 0, 'C');
        $pdf->Cell(50, 10, 'Monto Total', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Porcentaje', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        foreach ($distribucion as $metodo) {
            $pdf->Cell(60, 10, ucfirst($metodo['metodo_pago']), 1, 0, 'L');
            $pdf->Cell(40, 10, $metodo['total_ventas'], 1, 0, 'C');
            $pdf->Cell(50, 10, getMoneda() . number_format($metodo['monto_total'], 2), 1, 0, 'R');
            $pdf->Cell(40, 10, number_format($metodo['porcentaje'], 2) . '%', 1, 1, 'C');
        }

        $pdf->Output('D', 'metodos_pago.pdf');
        exit;
    }

    private function exportarTopProductosPDF($productos, $totales, $fecha_inicio, $fecha_fin, $tipo_ranking)
    {
        $pdf = new FPDF();
        $pdf->AddPage();
        
        $titulo = $tipo_ranking === 'revenue' ? 
            'Top Productos por Revenue' : 'Top Productos por Cantidad Vendida';
            
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, "Periodo: {$fecha_inicio} al {$fecha_fin}", 0, 1, 'C');
        $pdf->Ln(10);

        // Tabla
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(80, 10, 'Producto', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Revenue', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Ganancia', 1, 0, 'C');
        $pdf->Cell(20, 10, 'Margen %', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        foreach ($productos as $producto) {
            $margen = 0;
            if ($producto['revenue_total'] > 0) {
                $margen = ($producto['ganancia_total'] / $producto['revenue_total']) * 100;
            }

            $pdf->Cell(80, 10, substr(utf8_decode($producto['nombre']), 0, 35), 1, 0, 'L');
            $pdf->Cell(30, 10, $producto['total_vendido'], 1, 0, 'C');
            $pdf->Cell(30, 10, getMoneda() . number_format($producto['revenue_total'], 2), 1, 0, 'R');
            $pdf->Cell(30, 10, getMoneda() . number_format($producto['ganancia_total'], 2), 1, 0, 'R');
            $pdf->Cell(20, 10, number_format($margen, 1) . '%', 1, 1, 'C');
        }

        $pdf->Output('D', 'top_productos.pdf');
        exit;
    }

    private function exportarRentabilidadPDF($margenesProductos, $rentabilidadGeneral, $productosMasRentables, $kpis, $fecha_inicio, $fecha_fin)
    {
        $pdf = new FPDF();
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Analisis de Rentabilidad', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, "Periodo: {$fecha_inicio} al {$fecha_fin}", 0, 1, 'C');
        $pdf->Ln(10);

        // KPIs
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, "Margen Global: " . number_format($kpis['margen_global'], 2) . '%', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, "Productos Rentables: {$kpis['productos_rentables']}", 0, 1);
        $pdf->Cell(0, 8, "Margen Promedio: " . number_format($kpis['margen_promedio'], 2) . '%', 0, 1);
        $pdf->Ln(10);

        // Productos más rentables
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Productos Mas Rentables:', 0, 1);
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(70, 8, 'Producto', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Margen %', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Ganancia Total', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Cantidad', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 8);
        foreach ($productosMasRentables as $producto) {
            $pdf->Cell(70, 8, substr(utf8_decode($producto['nombre']), 0, 30), 1, 0, 'L');
            $pdf->Cell(30, 8, number_format($producto['margen_porcentaje'], 2) . '%', 1, 0, 'C');
            $pdf->Cell(40, 8, getMoneda() . number_format($producto['ganancia_total'], 2), 1, 0, 'R');
            $pdf->Cell(30, 8, $producto['total_vendido'], 1, 1, 'C');
        }

        $pdf->Output('D', 'analisis_rentabilidad.pdf');
        exit;
    }
}

