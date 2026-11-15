<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\Tickets\TicketDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class TicketDeliveryController extends Controller
{
    public function __construct(private TicketDelivery $delivery) {}

    private function cashier(Request $request): int
    {
        if (! $request->user()->isAdministrator() || ! $request->filled('caja')) {
            return (int) $request->user()->getKey();
        }

        return (int) $request->validate(['caja' => 'required|integer|exists:usuarios,id_usuario'])['caja'];
    }

    public function index(Request $request)
    {
        $cashier = $this->cashier($request);
        $base = DB::table('entregas_ticket as e')->join('ventas as v', 'v.id_venta', '=', 'e.venta')->where('e.cajero', $cashier);
        $history = $request->boolean('historial');
        $queue = clone $base;
        if (! $history) {
            $queue->whereIn('e.estado', ['pendiente', 'activo', 'pausado']);
            $queue->where(fn ($q) => $q->whereNull('e.vence')->orWhere('e.vence', '>', now()));
        }
        $direction = $history ? 'desc' : 'asc';
        $rows = $queue->orderBy('e.fecha_venta', $direction)->orderBy('e.venta', $direction)
            ->select('e.*', 'v.total_venta', 'v.moneda_codigo')
            ->selectSub(DB::table('eventos_ticket')->selectRaw('COUNT(*)')->whereColumn('entrega', 'e.id')->where('accion', 'consultado'), 'consultas')->paginate(12)->withQueryString();

        return view('tickets.center', [
            'cashier' => $cashier,
            'cashiers' => $request->user()->isAdministrator() ? DB::table('usuarios')->whereIn('id_usuario', DB::table('ventas')->select('id_usuario'))
                ->orWhere('id_usuario', $cashier)->orderBy('nombre_completo')->get(['id_usuario', 'nombre_completo']) : [],
            'rows' => $rows, 'history' => $history, 'pending' => (clone $base)->where('e.estado', 'pendiente')->count(),
            'today' => DB::table('ventas')->where('id_usuario', $cashier)->whereDate('fecha_venta', today())->count(),
            'portal' => $this->delivery->publicUrl('ticket/portal'),
        ]);
    }

    public function manage(Request $request, int $sale)
    {
        $data = $request->validate(['accion' => 'required|in:publicar,solicitar,mostrar,pausar,reanudar,renovar,revocar,cerrar', 'segundos' => 'nullable|integer|in:0,10,30,60,120']);
        $cashier = $request->user()->isAdministrator() ? (int) DB::table('ventas')->where('id_venta', $sale)->firstOrFail()->id_usuario : (int) $request->user()->getKey();
        $message = $this->delivery->operate($cashier, $sale, $data['accion'], isset($data['segundos']) ? (int) $data['segundos'] : null, (int) $request->user()->getKey());

        if ($data['accion'] === 'publicar') {
            return redirect()->route('tickets.publication', $sale)->with('status', $message);
        }

        return redirect()->route('tickets.center', $cashier === (int) $request->user()->getKey() ? [] : ['caja' => $cashier])->with('status', $message);
    }

    public function publication(Request $request, int $sale)
    {
        $row = DB::table('entregas_ticket')->where('venta', $sale)->firstOrFail();
        abort_unless($request->user()->isAdministrator() || (int) $row->cajero === (int) $request->user()->getKey(), 403);

        if ($request->expectsJson()) {
            $active = $this->delivery->display($row->cajero);
            if ((int) ($active['ticket'] ?? 0) === $sale) {
                return response()->json($active);
            }

            return response()->json(['url' => null, 'status' => match ($row->estado) {
                'pendiente' => 'En cola. Atiende la entrega anterior y pulsa «Volver a generar QR».',
                'entregado' => 'El cliente ya recibió este ticket.',
                'pausado' => 'Entrega pausada.',
                'activo','expirado' => 'El QR venció. Puedes generar uno nuevo.',
                default => 'Esta entrega está cerrada o revocada.',
            }]);
        }

        return view('tickets.publication', compact('row'));
    }

    public function screen(Request $request)
    {
        $cashier = $this->cashier($request);
        $token = bin2hex(random_bytes(32));
        $key = hash('sha256', $token);
        Cache::put('ticket-screen:'.$key, $cashier, now()->addHours(4));
        Cache::put('ticket-screen-cashier:'.$cashier, $key, now()->addHours(4));

        return redirect()->away($this->delivery->publicUrl('ticket/pantalla/'.$token));
    }

    public function customerScreen(string $token)
    {
        $key = hash('sha256', $token);
        $cashier = Cache::get('ticket-screen:'.$key);
        abort_unless($cashier && Cache::get('ticket-screen-cashier:'.$cashier) === $key, 410);
        if (request()->routeIs('tickets.screenData')) {
            return response()->json($this->delivery->display($cashier));
        }

        return view('tickets.public', ['mode' => 'screen', 'stateUrl' => route('tickets.screenData', $token)]);
    }

    public function state(Request $request)
    {
        return response()->json($this->delivery->display($this->cashier($request)));
    }

    public function portal(Request $request)
    {
        return view('tickets.public', ['mode' => 'portal', 'hasTicket' => $this->access($request) !== null]);
    }

    public function confirm(string $token)
    {
        $available = $this->delivery->available($token)->first();
        if ($available) {
            $this->delivery->event($available->id, 'consultado');
        }

        return response()->view('tickets.public', ['mode' => $available ? 'confirm' : 'invalid', 'token' => $token], $available ? 200 : 410);
    }

    public function claim(Request $request, string $token)
    {
        $request->session()->put('ticket_access', $this->delivery->claim($token));

        return redirect()->route('tickets.receipt');
    }

    private function access(Request $request): ?object
    {
        $grant = $request->session()->get('ticket_access');
        if (! $grant) {
            return null;
        }

        return DB::table('entregas_ticket')->where(['id' => $grant['id'], 'estado' => 'entregado', 'acceso_hash' => hash('sha256', $grant['clave'])])
            ->where('vence', '>', now())->first();
    }

    public function document(Request $request, int $document)
    {
        $row = $this->access($request);
        abort_unless($row, 410);
        $file = DB::table('documentos_ticket')->where(['venta' => $row->venta, 'id' => $document])->firstOrFail();
        $this->delivery->event($row->id, 'descarga_'.$file->tipo);

        return Storage::disk('local')->download($file->archivo, $file->numero.'.'.$file->extension);
    }

    public function receipt(Request $request)
    {
        $row = $this->access($request);
        if (! $row) {
            return response()->view('tickets.public', ['mode' => 'invalid'], 410);
        }
        $sale = Venta::with('detalles.producto', 'detalles.servicio', 'usuario', 'cliente')->findOrFail($row->venta);
        $pdf = $request->routeIs('tickets.pdf');
        $this->delivery->event($row->id, $pdf ? 'descarga_pdf' : 'visualizado');
        if (! $pdf) {
            return view('tickets.public', ['mode' => 'receipt', 'sale' => $sale,
                'documents' => DB::table('documentos_ticket')->where('venta', $row->venta)->get(['id', 'tipo', 'numero', 'extension'])]);
        }
        $document = new \FPDF;
        $document->AddPage();
        $text = fn ($value) => iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $value);
        $document->SetFont('Helvetica', 'B', 16);
        $document->MultiCell(0, 9, $text(config('business.name')));
        $document->SetFont('Helvetica', '', 12);
        $document->MultiCell(0, 8, $text("Ticket interno #{$sale->id_venta} · {$sale->fecha_venta}"));
        $document->Ln(4);
        foreach ($sale->detalles as $line) {
            $name = $line->producto?->nombre ?? $line->servicio?->nombre ?? 'Artículo';
            $amount = $line->importe_neto ?? $line->cantidad * $line->precio_unitario;
            $document->MultiCell(0, 7, $text("{$line->cantidad} × {$name}   ".$sale->moneda_codigo.' '.number_format($amount, 2)));
        }
        $document->SetFont('Helvetica', 'B', 13);
        $document->MultiCell(0, 10, $text('Total '.$sale->moneda_codigo.' '.number_format($sale->total_venta, 2)));
        $document->SetFont('Helvetica', '', 11);
        $document->MultiCell(0, 7, $text('Pago: '.$sale->metodo_pago.'. Revisa tu producto al comprar. Cambios por falla hasta 48 horas después de la venta. Puedes elegir otro producto o saldo a favor.'));

        return response($document->Output('S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="ticket-'.$sale->id_venta.'.pdf"']);
    }
}
