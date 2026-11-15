@extends('layouts.app')
@section('title','Centro de tickets')
@push('styles')<link rel="stylesheet" href="{{ asset('css/tickets.css') }}">@endpush
@push('scripts')
<script defer src="{{ asset('js/vendor/qrcode.js') }}"></script>
<script defer src="{{ asset('js/tickets.js') }}"></script>
@endpush
@section('content')
<section class="ticket-center">
    <header><h1>Centro de tickets</h1><p>{{ $today }} ventas hoy en tu caja · {{ $pending }} entregas en espera</p></header>
    @if(auth()->user()->isAdministrator())
    <form class="delivery-selector" method="GET" action="{{ route('tickets.center') }}"><label>Caja atendida por<select name="caja">@foreach($cashiers as $operator)<option value="{{ $operator->id_usuario }}" @selected($cashier == $operator->id_usuario)>{{ $operator->nombre_completo }}</option>@endforeach</select></label><button class="btn btn-secondary">Ver caja</button></form>
    @endif
    <div class="delivery-layout">
        <section class="panel delivery-queue">
            <header><h2>{{ $history ? 'Historial de entregas' : 'Cola de entregas' }}</h2>
                <a class="btn btn-secondary" href="{{ route('tickets.center',$history ? ['caja'=>$cashier] : ['historial'=>1,'caja'=>$cashier]) }}">{{ $history ? 'Volver a la cola' : 'Ver historial' }}</a>
            </header>
            @forelse($rows as $row)
                @php($state = $row->estado === 'activo' && $row->vence && now()->gte($row->vence) ? 'expirado' : $row->estado)
                <article class="delivery-row"><header><strong>Venta #{{ $row->venta }}</strong><span>{{ ucfirst($state) }}</span></header>
                    <p>{{ $row->fecha_venta }} · {{ $row->moneda_codigo }} {{ number_format($row->total_venta,2) }} · Turno {{ $row->turno ?? 'histórico' }} · {{ $row->consultas }} consultas</p>
                    <form method="POST" action="{{ route('tickets.manage',$row->venta) }}">@csrf
                        @if(in_array($state,['pendiente','activo','expirado','revocado','cerrado','entregado']))
                        <label>Duración del QR<select name="segundos">@foreach([10,30,60,120,0] as $ttl)<option value="{{ $ttl }}" @selected($ttl === (int)$row->segundos)>{{ $ttl ? $ttl.' segundos' : 'Hasta retirar' }}</option>@endforeach</select></label>
                        @endif
                        @if(in_array($state,['pendiente','expirado','revocado','cerrado']))<button name="accion" value="mostrar" class="btn btn-primary">Mostrar QR</button>@endif
                        @if($state === 'pausado')<button name="accion" value="reanudar" class="btn btn-primary">Reanudar</button>@endif
                        @if($state === 'activo')<button name="accion" value="pausar" class="btn btn-secondary">Pausar</button>@endif
                        @if(in_array($state,['activo','entregado']))<button name="accion" value="renovar" class="btn btn-primary">Renovar QR</button>@endif
                        @if(in_array($state,['activo','pausado','entregado']))<button name="accion" value="revocar" class="btn btn-danger">Revocar acceso</button>@endif
                        @if(!in_array($state,['cerrado','entregado']))<button name="accion" value="cerrar" class="btn btn-secondary">Cerrar entrega</button>@endif
                    </form>
                </article>
            @empty<p>{{ $history ? 'Todavía no hay entregas registradas.' : 'La cola está vacía. Puedes publicar un QR desde el historial de ventas.' }}</p>@endforelse
            {{ $rows->links('vendor.pagination.tailwind') }}
        </section>
        <aside class="panel delivery-display"><h2>QR de entrega</h2>
            <div data-publication="{{ route('tickets.state',['caja'=>$cashier]) }}"><div data-code></div><p data-status role="status">Consultando la caja…</p></div>
            <a class="btn btn-secondary" data-ticket-share hidden target="_blank" rel="noopener noreferrer">Compartir enlace por WhatsApp</a>
            <a class="btn btn-secondary" href="{{ route('tickets.screen',['caja'=>$cashier]) }}" target="_blank" rel="noopener">Abrir pantalla del cliente</a>
        </aside>
    </div>
    <details class="panel delivery-static"><summary>QR fijo para el mostrador</summary>
        <p>Este código abre el portal. El cliente puede volver a su ticket desde el celular donde lo recibió.</p>
        <div data-static-qr="{{ $portal }}"></div><button class="btn btn-secondary" type="button" data-print-static>Imprimir QR del mostrador</button>
    </details>
    @can('admin_gestionar_roles')
    <details class="panel"><summary>Vincular boleta o factura emitida</summary>
        <form class="form-grid" method="POST" enctype="multipart/form-data" action="{{ route('tickets.attachForm') }}">@csrf
            <label>Número de ticket<input type="number" name="venta" min="1" required></label>
            <label>Documento<select name="tipo"><option value="boleta">Boleta</option><option value="factura">Factura</option></select></label>
            <label>Serie y número<input name="numero" required placeholder="B001-123"></label>
            <label>Archivo emitido<input type="file" name="documento" accept=".pdf,.xml" required></label>
            <button class="btn btn-success">Vincular documento</button>
        </form>
    </details>@endcan
    <a class="btn btn-secondary" href="{{ route('sales.pos') }}">Volver al punto de venta</a>
    @if(in_array(parse_url($portal,PHP_URL_HOST),['localhost','127.0.0.1','::1']))<p role="status">La dirección configurada es local. Configura TICKET_PUBLIC_URL con la dirección HTTPS accesible desde los celulares antes de entregar el QR.</p>@endif
</section>
@endsection
