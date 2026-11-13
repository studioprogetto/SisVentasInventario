<div data-lines>
<fieldset class="line card" data-line><legend>Producto o servicio</legend>
<label>Buscar por nombre o código<input type="search" data-item-search autocomplete="off" placeholder="Escribe al menos dos caracteres"></label>
<label>Resultado<select data-item-result required><option value="">Selecciona un resultado</option></select></label>
<input type="hidden" name="{{ $prefix }}[0][{{ $prefix==='carrito' ? 'id' : 'id_producto' }}]" data-item-id>
@if($prefix==='carrito')<input type="hidden" name="carrito[0][tipo]" value="producto" data-item-type>@endif
<label>Cantidad<input type="number" name="{{ $prefix }}[0][cantidad]" min="1" max="100000" value="1" required></label>
@if($prefix==='carrito')<label>Almacén<select name="carrito[0][id_almacen]" data-warehouse>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->nombre }}</option>@endforeach</select></label>@endif
@if($prefix==='productos' && ($costs ?? false))<label>Costo unitario<input type="number" name="productos[0][costo_unitario]" min="0" step="0.01" required></label>@endif
<p data-price role="status"></p><button type="button" data-remove-line>Quitar línea</button>
</fieldset>
</div><button type="button" data-add-line>Agregar línea</button>
