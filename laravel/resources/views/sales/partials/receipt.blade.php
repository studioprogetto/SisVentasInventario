@php
    $currency = $sale->moneda_codigo ?? $sale->usuario?->moneda ?? config('business.display_currency', 'S/');
    $ticketUser = $sale->usuario?->nombre_completo ?? 'Usuario del sistema';
    $saleDate = \Illuminate\Support\Carbon::parse($sale->fecha_venta);
@endphp
        <div class="ticket-logo">
            <div class="ticket-logo-mark">
                <img src="{{ route('brand.logo') }}" alt="{{ config('business.name', 'Studio & Progetto') }}" loading="eager">
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
            <div class="ticket-total-line" style="font-size:1.05rem;font-weight:700;justify-content:space-between;margin-top:8px"><span>TOTAL DE LA VENTA:</span><strong>{{ $currency }} {{ $sale->total_venta }}</strong></div>
        </div>
        @if((float)$sale->saldo_utilizado > 0)
        <div class="ticket-total-line"><span>Saldo a favor aplicado</span><strong>{{ $currency }} {{ number_format($sale->saldo_utilizado,2) }}</strong></div>
        <div class="ticket-total-line"><span>Diferencia cobrada</span><strong>{{ $currency }} {{ number_format($sale->total_venta - $sale->saldo_utilizado,2) }}</strong></div>
        @endif
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
            <strong>Política de cambios · 48 horas</strong>
            <p>Revisa tu producto al comprar. Si presenta una falla, solicita el cambio con este ticket hasta el {{ $saleDate->copy()->addDays(2)->format('d/m/Y H:i') }}.</p>
            <p>Puedes elegir otro producto o mantener saldo a favor en tu cuenta para otra compra. Si el nuevo importe es mayor, pagas la diferencia. No se entrega dinero en efectivo.</p>
        </div>
