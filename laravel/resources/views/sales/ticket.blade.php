@extends('layouts.app')
@php
    $currency = $sale->moneda_codigo ?? $sale->usuario?->moneda ?? config('business.display_currency', 'S/');
    $ticketUser = $sale->usuario?->nombre_completo ?? auth()->user()?->nombre_completo ?? 'Usuario del sistema';
    $saleDate = \Illuminate\Support\Carbon::parse($sale->fecha_venta);
@endphp
@push('styles')
<style>
    .ticket-shell{display:flex;justify-content:center;padding:18px 0 32px}
    .ticket-paper{width:min(100%, 440px);background:#fff;border:1px solid #d7dfe9;border-radius:16px;box-shadow:0 25px 60px rgba(17,24,39,.12);padding:18px 16px 12px}
    .ticket-logo{display:flex;justify-content:center;margin:6px auto 12px;width:100%}
    .ticket-logo-mark{width:64px;height:64px;border-radius:16px;overflow:hidden;display:grid;place-items:center;background:#f5f7fb;border:1px solid #dfe7f1;box-shadow:0 12px 26px rgba(15,23,42,.08)}
    .ticket-logo-mark img{display:block;width:100%;height:100%;object-fit:contain;padding:8px;background:#fff}
    .ticket-brand{text-align:center;margin-bottom:10px;font-family:Georgia,'Times New Roman',serif}
    .ticket-brand h2{font-size:1.12rem;margin:0 0 4px;color:#1f2937;letter-spacing:.02em}
    .ticket-brand p{margin:2px 0;color:#575d6d;font-size:.72rem}
    .ticket-divider{border-top:1px dashed #aab4bf;margin:10px 0 12px}
    .ticket-meta{text-align:center;line-height:1.6;color:#1f2937;font-size:.74rem}
    .ticket-meta strong{font-size:.8rem}
    .ticket-table{width:100%;border-collapse:collapse;margin-top:8px}
    .ticket-table th,.ticket-table td{padding:4px 0;font-size:.72rem;vertical-align:top;color:#1f2937}
    .ticket-table th{text-align:left;font-weight:700}
    .ticket-table td:nth-child(2),.ticket-table td:nth-child(3),.ticket-table td:nth-child(4){text-align:right}
    .ticket-totals{margin-top:12px;padding-top:8px;border-top:1px dashed #aab4bf}
    .ticket-total-line{display:flex;justify-content:space-between;align-items:center;font-size:.78rem;color:#1f2937;padding:2px 0}
    .ticket-total-line strong{font-size:1.1rem;letter-spacing:-.02em}
    .ticket-method{text-align:center;color:#4a5568;font-size:.72rem;margin-top:12px;padding-top:10px;border-top:1px dashed #aab4bf}
    .ticket-hours{font-size:.7rem;color:#4a5568;text-align:center;line-height:1.6;margin-top:16px}
    .ticket-footer{margin-top:14px;padding-top:10px;border-top:1px dashed #aab4bf;text-align:center}
    .ticket-footer p{margin:0;color:#4a5568;font-size:.7rem;line-height:1.5}
    .ticket-actions{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:18px}
    .ticket-actions button{min-height:42px;border-radius:10px;padding:0 10px;font-size:.72rem}
    .ticket-actions .btn-primary{background:#1c7fdb}.ticket-actions .btn-success{background:#1c8c67}.ticket-actions .btn-warning{background:#d98a0e}.ticket-actions .btn-secondary{background:transparent;color:var(--text);border:1px solid var(--line)}
    @media (max-width: 560px){.ticket-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.ticket-paper{padding-left:12px;padding-right:12px}}
</style>
@endpush
@section('content')
<div class="ticket-shell">
    <div class="ticket-paper" aria-label="Comprobante de venta">
        <div class="ticket-logo">
            <div class="ticket-logo-mark">
                <img src="{{ route('brand.logo') }}?v={{ now()->timestamp }}" alt="{{ config('business.name', 'Studio & Progetto') }}" loading="eager">
            </div>
        </div>
        <div class="ticket-brand">
            <h2>{{ config('business.name', 'Studio & Progetto') }}</h2>
            <p>RUC: {{ config('business.ruc', '10756263157') }}</p>
            <p>{{ config('business.address', 'Calles Las Gemas 626 Urb. Santa Inés') }}</p>
            <p>{{ config('business.district', 'Trujillo - La Libertad') }}</p>
            <p>Tel.: {{ config('business.phone', '+51 939275406') }}</p>
        </div>
        <div class="ticket-divider"></div>
        <div class="ticket-meta">
            <strong>Ticket #{{ $sale->id_venta }}</strong><br>
            Cliente: {{ $sale->cliente?->nombre_cliente ?? 'Venta genérica' }}<br>
            Atendido por: {{ $ticketUser }}<br>
            Fecha: {{ $saleDate->format('Y-m-d H:i:s') }}
        </div>
        <div class="ticket-divider"></div>
        <table class="ticket-table">
            <thead>
                <tr><th>Cant.</th><th>Producto/Servicio</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
                @foreach($sale->detalles as $line)
                    <tr>
                        <td>{{ $line->cantidad }}</td>
                        <td>{{ $line->producto?->nombre ?? $line->servicio?->nombre ?? 'Concepto sin nombre' }}</td>
                        <td>{{ $currency }} {{ $line->importe_neto ?? $line->precio_unitario }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="ticket-totals">
            <div class="ticket-total-line"><span>Subtotal:</span><strong>{{ $currency }} {{ $sale->total_venta }}</strong></div>
            <div class="ticket-total-line" style="font-size:1.05rem;font-weight:700;justify-content:space-between;margin-top:8px"><span>TOTAL A PAGAR:</span><strong>{{ $currency }} {{ $sale->total_venta }}</strong></div>
        </div>
        <div class="ticket-method">
            Método de pago: {{ ucfirst($sale->metodo_pago ?? 'efectivo') }}
            @if(in_array((string) ($sale->metodo_pago ?? ''), ['efectivo', 'mixto'], true) && $sale->monto_recibido !== null)
                <br>Recibido: {{ $currency }} {{ number_format((float) $sale->monto_recibido, 2, '.', ',') }}
            @endif
            @if(in_array((string) ($sale->metodo_pago ?? ''), ['efectivo', 'mixto'], true) && $sale->cambio_entregado !== null)
                <br>Vuelto: {{ $currency }} {{ number_format((float) $sale->cambio_entregado, 2, '.', ',') }}
            @endif
        </div>
        <div class="ticket-hours">
            Horario de atención:<br>
            {{ config('business.opening_hours.weekdays', 'Lun - Vie: 8:00 - 20:30') }}<br>
            {{ config('business.opening_hours.weekend', 'Sáb: 8:00 - 20:30') }}
        </div>
        <div class="ticket-footer">
            <p>Gracias por su compra.</p>
        </div>
        <div class="ticket-divider"></div>
        <div class="ticket-footer">
            <p>Política de devolución:</p>
            <p>Los productos comprados tienen un plazo de {{ config('business.return_policy_days', 4) }} días para devolución.</p>
            <p>Compra desde: {{ $saleDate->addDay()->format('d/m/Y') }}</p>
            <p>Hasta: {{ $saleDate->copy()->addDays(config('business.return_policy_days', 4) - 1)->format('d/m/Y') }}</p>
        </div>
        <div class="ticket-actions">
            <button type="button" class="btn btn-primary" data-print>Imprimir Ticket</button>
            <button type="button" class="btn btn-success" onclick="window.print()">Guardar en PDF</button>
            <button type="button" class="btn btn-warning" onclick="window.location='{{ route('sales.pos') }}'">Volver al POS</button>
            <button type="button" class="btn btn-secondary" onclick="window.location='{{ route('sales.history') }}'">Ver Historial</button>
        </div>
    </div>
</div>
@endsection
