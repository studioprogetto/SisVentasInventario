@extends('layouts.app')
@section('title', 'Cambio de producto')
@section('content')
<section class="exchange-page">
    <h1>Cambio de producto o saldo a favor</h1>
    <form class="panel exchange-lookup" method="GET" action="{{ route('sales.exchanges') }}">
        <label>Número de ticket<input name="ticket" type="number" min="1" required value="{{ request('ticket') }}"></label>
        <button class="btn btn-primary">Buscar venta</button>
    </form>
    @if($sale)
        <header><h2>Ticket #{{ $sale->id_venta }}</h2><p>{{ $sale->cliente?->nombre_cliente ?? 'Cliente sin registrar' }} · {{ $sale->fecha_venta }}</p></header>
        @if($lines->isEmpty())
            <p role="status">Esta venta no tiene artículos disponibles para cambio dentro del plazo de 48 horas.</p>
        @else
            <p>Selecciona el artículo e indica la cantidad y el motivo.</p>
            @foreach($lines as $line)
                <details class="panel exchange-line" @if($lines->count() === 1) open @endif>
                    <summary>{{ $line->producto?->nombre }} · {{ $line->disponibles }} disponibles para cambio</summary>
                    <form method="POST" action="{{ route(auth()->user()->can('ventas_devolver') ? 'sales.return' : 'returns.request') }}" class="form-grid" data-confirm="¿Confirmas la solicitud de cambio o saldo?">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::random(24) }}">
                        <input type="hidden" name="id_venta_original" value="{{ $sale->id_venta }}">
                        <input type="hidden" name="productos[0][id_detalle_venta]" value="{{ $line->id_detalle_venta }}">
                        <label>{{ $line->producto?->nombre ?? $line->servicio?->nombre ?? 'Concepto sin nombre' }} · compradas: {{ $line->cantidad }}
                            <input type="number" name="productos[0][cantidad]" min="1" max="{{ $line->disponibles }}" value="1" required>
                        </label>
                        @unless($sale->id_cliente)
                        <label>Cliente titular (obligatorio para guardar saldo)<select name="id_cliente"><option value="">Sin registrar · solo cambio inmediato</option>@foreach($holders as $holder)<option value="{{ $holder->id_cliente }}">{{ $holder->nombre_cliente }}</option>@endforeach</select></label>
                        <a href="{{ route('masters.index','cliente') }}">Registrar cliente</a>
                        @endunless
                        <label>Resolución<select name="destino"><option value="saldo">Guardar saldo a favor</option><option value="cambio">Cambiar por otro producto</option></select></label>
                        <label>Estado del artículo<select name="condicion"><option value="defectuoso">Defectuoso · retirar de la venta</option><option value="conforme">Sin uso y apto para la venta</option></select></label>
                        <label>Producto de reemplazo (solo para cambio)<select name="reemplazo"><option value="">Seleccionar producto</option>@foreach($replacementProducts as $product)<option value="{{ $product->id_producto }}">{{ $product->nombre }}</option>@endforeach</select></label>
                        <label>Pago de la diferencia<select name="metodo_pago">@foreach(['efectivo','yape','plin','transferencia'] as $method)<option>{{ $method }}</option>@endforeach</select></label>
                        <label>Motivo
                            <textarea name="observacion" maxlength="255" required placeholder="Describe el motivo del cambio o saldo"></textarea>
                        </label>
                        <button type="submit" class="btn btn-success">{{ auth()->user()->can('ventas_devolver') ? 'Confirmar cambio o saldo' : 'Solicitar aprobación' }}</button>
                    </form>
                </details>
            @endforeach
        @endif
    @elseif(request()->filled('ticket'))
        <p role="status">No se encontró una venta con ese número de ticket.</p>
    @endif
</section>
@endsection
