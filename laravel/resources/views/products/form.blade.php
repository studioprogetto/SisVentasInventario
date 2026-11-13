<div class="form-grid">
<label>Nombre<input name="nombre" value="{{ $product?->nombre ?? old('nombre') }}" maxlength="255" required></label>
<label>Código de barras<input name="codigo_barra" value="{{ $product?->codigo_barra }}" maxlength="100"></label>
<label>Descripción<textarea name="descripcion" maxlength="10000">{{ $product?->descripcion }}</textarea></label>
<label>Categoría<select name="id_categoria"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id_categoria }}" @selected($product?->id_categoria===$category->id_categoria)>{{ $category->nombre }}</option>@endforeach</select></label>
<label>Almacén predeterminado<select name="id_almacen" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($product?->id_almacen===$warehouse->id)>{{ $warehouse->nombre }}</option>@endforeach</select></label>
<label>Proveedor preferido<select name="id_proveedor_preferido"><option value="">Sin proveedor preferido</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id_proveedor }}" @selected($product?->id_proveedor_preferido===$supplier->id_proveedor)>{{ $supplier->nombre_proveedor }}</option>@endforeach</select></label>
<label>Costo en moneda principal<input name="precio_compra" type="number" min="0" step="0.01" value="{{ $product?->precio_compra ?? 0 }}" required></label>
<label>Precio en moneda principal<input name="precio_venta" type="number" min="0" step="0.01" value="{{ $product?->precio_venta ?? 0 }}" required></label>
<label>Stock mínimo<input name="stock_minimo" type="number" min="0" value="{{ $product?->stock_minimo ?? 0 }}" required></label>
@if(!$product)<label>Stock inicial<input name="stock_inicial" type="number" min="0" value="0"></label>@endif
<label>Fotografía<input type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"></label>
<label>O URL de fotografía<input name="imagen_url" type="url" value="{{ $product?->imagen_url }}" maxlength="511"></label>
<input type="hidden" name="publicado" value="0"><label><input type="checkbox" name="publicado" value="1" @checked($product?->publicado)> Publicar en el catálogo</label>
</div><button type="submit" class="btn btn-success">Guardar producto</button>
