<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Exports\PdfReport;
use App\Services\Exports\ReportPresentation;
use App\Services\Exports\TabularExport;
use App\Services\Reports\ReportQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ReportController extends Controller
{
    public function index(): mixed
    {
        abort_if(auth()->user()->isMultifunctional(), 403);
        return view('reports.index', ['types' => ReportQuery::TYPES]);
    }

    public function show(Request $request, ReportQuery $reports, TabularExport $excel, PdfReport $pdf, string $type): mixed
    {
        abort_if(auth()->user()->isMultifunctional(), 403);
        abort_unless(isset(ReportQuery::TYPES[$type]), 404);
        [$title, $permission] = ReportQuery::TYPES[$type];
        Gate::authorize($permission);
        $data = $request->validate(['desde' => ['nullable', 'date_format:Y-m-d'], 'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'], 'formato' => ['nullable', 'in:csv,xlsx,pdf']]);
        $since = $data['desde'] ?? today()->startOfMonth()->toDateString();
        $until = $data['hasta'] ?? today()->toDateString();
        $query = $reports->build($type, $since, $until);
        if (in_array($data['formato'] ?? '', ['pdf', 'xlsx'], true)) {
            $distribution = ReportPresentation::distribution($query, $type);
            $caption = $type === 'top-productos' ? 'Unidades netas por producto' : 'Distribución de registros · principales grupos';
            $period = in_array($type, ['inventario','stock-bajo','clientes','proveedores','saldos'], true)
                ? 'Estado actual al '.today()->format('d/m/Y') : $since.' — '.$until;
        }
        if (($data['formato'] ?? '') === 'xlsx') {
            return response()->download($excel->workbook($query->cursor(), $title, $period, $caption, $distribution), $type.'.xlsx')->deleteFileAfterSend();
        }
        if (($data['formato'] ?? '') === 'pdf') {
            return response($pdf->render($title, $query->cursor(), $period, $caption, $distribution), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$type.'.pdf"']);
        }
        if (($data['formato'] ?? '') === 'csv') {
            return response()->streamDownload(function () use ($query): void {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                $header = false;
                foreach ($query->cursor() as $record) {
                    $row = (array) $record;
                    if (! $header) {
                        fputcsv($file, array_keys($row), ',', '"', '');
                        $header = true;
                    }
                    fputcsv($file, array_map(fn ($value) => preg_match('/^[=+@\-]/', (string) $value) ? "'".$value : $value, array_values($row)), ',', '"', '');
                }
                fclose($file);
            }, $type.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('reports.show', ['title' => $title, 'type' => $type, 'since' => $since, 'until' => $until, 'summary' => $reports->summary($type, $since, $until), 'rows' => $query->paginate(config('business.pagination'))->withQueryString()]);
    }
}
