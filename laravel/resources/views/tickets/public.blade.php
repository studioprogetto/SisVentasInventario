<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Tickets · {{ config('business.name') }}</title>
<link rel="stylesheet" href="{{ asset('css/tickets.css') }}">
@if($mode === 'receipt')
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/receipt.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-records.css') }}">
<link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
<link rel="stylesheet" href="{{ asset('css/lectura.css') }}">
<link rel="stylesheet" href="{{ asset('css/scrollbars.css') }}">
@endif
@if($mode === 'screen')<script defer src="{{ asset('js/vendor/qrcode.js') }}"></script>@endif
<script defer src="{{ asset('js/tickets.js') }}"></script></head>
<body class="{{ $mode === 'receipt' ? 'admin-app public-receipt' : 'customer-ticket' }}"><main id="content">
@unless($mode === 'receipt')<header><strong>{{ config('business.name') }}</strong></header>@endunless
@switch($mode)
@case('screen')
<h1>Recibe tu ticket</h1><div data-publication="{{ $stateUrl }}"><div data-code></div><p data-status role="status">Esperando a caja…</p></div>@break
@case('portal')
<h1>Portal de tickets</h1>@if($hasTicket)<a class="ticket-action" href="{{ route('tickets.receipt') }}">Ver mi último ticket</a>@else<p>Pide tu ticket en caja. Escanea el QR que te muestre el personal y pulsa «Recibir mi ticket».</p>@endif @break
@case('confirm')
<h1>Tu ticket está listo</h1><p>Ábrelo en este teléfono para guardarlo o imprimirlo.</p>
<form method="POST" action="{{ route('tickets.claim',$token) }}">@csrf<button class="ticket-action">Recibir mi ticket</button></form>@break
@case('invalid')
<h1>Entrega no disponible</h1><p>El enlace ha caducado o ya se utilizó. Si recibiste el ticket en este dispositivo, abre el portal. También puedes pedir otro QR en caja.</p><a href="{{ route('tickets.portal') }}">Abrir el portal</a>@break
@case('receipt')
<div class="ticket-shell"><div class="ticket-paper" aria-label="Comprobante de venta">
    @include('sales.partials.receipt')
</div></div>
<div class="public-ticket-actions">
@foreach($documents as $document)<p><a href="{{ route('tickets.document',$document->id) }}">Descargar {{ $document->tipo }} {{ $document->numero }} ({{ strtoupper($document->extension) }})</a></p>@endforeach
<nav><a class="ticket-action" href="{{ route('tickets.pdf') }}">Descargar PDF</a> <button type="button" data-print-receipt>Imprimir / guardar como PDF</button></nav></div>@break
@endswitch
</main></body></html>
