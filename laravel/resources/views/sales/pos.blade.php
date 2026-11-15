@extends('layouts.app')
@section('title','Punto de venta — '.config('business.name', 'Studio & Progetto'))
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pos.css') }}?v=2">@endpush
@section('content')
<aside data-client-notice class="client-notice" role="status" hidden></aside>
<form method="POST" action="{{ route('sales.store') }}" data-pos data-items-url="{{ route('pos.items') }}" data-client-url="{{ route('clients.search') }}" data-client-detail-url="{{ url('pos/clientes') }}" data-quote-url="{{ route('sales.quote') }}">@csrf
    <input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}"><input type="hidden" name="accepted_total" value=""><input type="hidden" name="id_cliente" value=""><input type="hidden" name="id_moneda" value="{{ $selectedCurrency }}">
    <div class="pos-layout">
        <section class="pos-catalog panel">
            <div class="pos-panel-title"><x-icon name="cart" />
                <h2>Punto de venta</h2><span class="pos-live">Catálogo en vivo</span>
            </div>
            <div class="pos-catalog-controls"><label for="pos-search">Buscar producto o servicio</label>
                <div class="pos-search-row"><input id="pos-search" type="search" maxlength="100" autocomplete="off" placeholder="Nombre o código de barras…" data-pos-search><button type="button" data-pos-search-now aria-label="Buscar productos"><x-icon name="arrow" /></button></div>
                <p class="pos-search-hint">Se actualiza 300 ms después de escribir. Enter busca al instante.</p>
            </div>
            <p class="pos-results-status" data-pos-status role="status" aria-live="polite">{{ $catalog['total'] }} resultados · precios en {{ $catalog['currency'] }}</p>
            <div class="pos-products" data-pos-products aria-label="Resultados de productos">
                @forelse($catalog['data'] as $item)
                <article class="pos-product">
                    <div class="pos-product-image">@if($item['imagen'])<img src="{{ $item['imagen'] }}" alt="" loading="lazy">@else<span>✦</span>@endif</div>
                    <h3>{{ $item['nombre'] }}</h3>
                    <p>{{ $item['almacen'] }}</p><small>{{ $item['stock'] === null ? 'Servicio' : $item['stock'].' disponibles' }}</small>
                    <div class="pos-product-footer"><strong>{{ $catalog['currency'] }} {{ $item['precio_venta'] }}</strong><button type="button" class="pos-add" data-first-item="{{ json_encode($item) }}" @disabled($item['stock'] !==null && $item['stock'] < 1) aria-label="Agregar {{ $item['nombre'] }}">+</button></div>
                </article>
                @empty<p>No hay artículos activos para vender.</p>@endforelse
            </div>
            <div class="pos-pagination">
                <div class="pos-page-buttons"><button type="button" data-pos-prev aria-label="Página anterior">‹</button><span data-pos-page data-last-page="{{ $catalog['last_page'] }}" data-currency="{{ $catalog['currency'] }}">1 / {{ $catalog['last_page'] }}</span><button type="button" data-pos-next aria-label="Página siguiente">›</button></div><small>{{ config('business.pagination') }} por página</small>
            </div>
        </section>
        <section class="pos-summary panel" id="checkout-sheet" aria-label="Resumen de venta"><button class="sheet-close btn btn-secondary" type="button" data-dismiss-cart>Cerrar carrito</button>
            <div class="pos-panel-title"><x-icon name="file" />
                <h2>Resumen de venta</h2><span class="badge" data-pos-count>0 artículos</span>
            </div>
            <div class="pos-summary-body">
                @include('sales.partials.pos-client')
                <div class="pos-cart-wrap">
                    <table class="pos-cart">
                        <thead>
                            <tr>
                                <th>Producto / servicio</th>
                                <th>Cant.</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                                <th><span class="sr-only">Quitar</span></th>
                            </tr>
                        </thead>
                        <tbody data-pos-cart></tbody>
                    </table>
                    <div class="pos-empty" data-pos-empty><span><x-icon name="cart" /></span><strong>Tu carrito está vacío</strong>
                        <p>Busca y agrega productos para continuar.</p>
                    </div>
                </div>
                <div class="pos-payment-grid">
                    <label>Método de pago<select name="metodo_pago">@foreach(config('business.payment_methods') as $method)<option value="{{ $method }}">{{ ucfirst($method === 'mixto' ? 'Mixto' : $method) }}</option>@endforeach</select></label>
                    <div class="pos-cash-fields" data-pos-cash-fields hidden>
                        <label>Monto recibido (S/)<input type="number" step="0.01" min="0" name="monto_recibido" value="0" inputmode="decimal" data-pos-received></label>
                        <div class="pos-cash-change" data-pos-payment-status>Vuelto: S/ 0.00</div>
                    </div>
                </div>
                <details class="pos-options">
                    <summary>Descuento, saldo y observaciones</summary>
                    <div class="pos-payment-grid">@can('ventas_aplicar_descuento')<label>Descuento autorizado<input type="number" step="0.01" min="0" max="99999999" name="descuento_manual" value="0"></label>@endcan<label><input type="checkbox" name="usar_saldo" value="1"> Usar saldo en esta moneda</label><label>Observaciones<input name="observacion" maxlength="255"></label></div>
                </details>
                <div class="pos-breakdown" data-pos-breakdown hidden></div>
                <div class="pos-total"><span>TOTAL</span><strong data-pos-total>—</strong></div>
                <p class="pos-quote-status" data-pos-quote-status role="status" aria-live="polite">Agrega un producto para calcular el total.</p>
                <button type="button" class="btn btn-secondary pos-requote" data-pos-quote>Actualizar total</button><button type="submit" class="btn btn-success pos-checkout" data-confirm-sale disabled><x-icon name="shield" />Finalizar venta</button>
                <p class="financial-note">El total incluye los descuentos aplicables. Confirma después de revisar el resumen.</p>
            </div>
        </section>
    </div>
    <div class="cart-dock"><span>Total <strong data-dock-total>—</strong></span><button type="button" class="btn" data-show-cart aria-controls="checkout-sheet" aria-expanded="false">Ver carrito</button></div>
</form>
<script src="{{ asset('js/pos.js') }}?v={{ filemtime(public_path('js/pos.js')) }}" defer></script>
@endsection