<div class="form-grid">
    <label>Nombre del producto<input name="nombre" value="{{ old('nombre', $product?->nombre) }}" maxlength="255" required></label>
    <label>Código de barras<input name="codigo_barra" value="{{ old('codigo_barra', $product?->codigo_barra) }}" maxlength="100"></label>
    <label>Descripción<textarea name="descripcion" maxlength="10000">{{ old('descripcion', $product?->descripcion) }}</textarea></label>
    <label>Categoría<select name="id_categoria"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id_categoria }}" @selected((string) old('id_categoria', $product?->id_categoria) === (string) $category->id_categoria)>{{ $category->nombre }}</option>@endforeach</select></label>
    <label>Almacén predeterminado<select name="id_almacen" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string) old('id_almacen', $product?->id_almacen) === (string) $warehouse->id)>{{ $warehouse->nombre }}</option>@endforeach</select></label>
    <label>Proveedor preferido<select name="id_proveedor_preferido"><option value="">Sin proveedor preferido</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id_proveedor }}" @selected((string) old('id_proveedor_preferido', $product?->id_proveedor_preferido) === (string) $supplier->id_proveedor)>{{ $supplier->nombre_proveedor }}</option>@endforeach</select></label>
    <label>Precio de compra<input name="precio_compra" type="number" min="0" step="0.01" value="{{ old('precio_compra', $product?->precio_compra ?? 0) }}" required></label>
    <label>Precio de venta<input name="precio_venta" type="number" min="0" step="0.01" value="{{ old('precio_venta', $product?->precio_venta ?? 0) }}" required></label>
    <label>Stock actual <small>Calculado desde existencias por almacén</small><input type="number" value="{{ (int) ($product?->stock_total ?? 0) }}" readonly aria-readonly="true"></label>
    <label>Stock mínimo<input name="stock_minimo" type="number" min="0" value="{{ old('stock_minimo', $product?->stock_minimo ?? 0) }}" required></label>
    @if(!$product)<label>Stock inicial <small>Se registra en el almacén seleccionado</small><input name="stock_inicial" type="number" min="0" value="{{ old('stock_inicial', 0) }}"></label>@endif
    <label>Imagen local <small>JPG, PNG, WEBP o GIF · máximo 2 MB</small><input type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"></label>
    <label>Imagen por enlace <small>Si se completa, se usará esta URL</small><input name="imagen_url" type="url" value="{{ old('imagen_url', $product?->imagen_url) }}" maxlength="511" placeholder="https://ejemplo.com/imagen.jpg"></label>
    <input type="hidden" name="publicado" value="0"><label><input type="checkbox" name="publicado" value="1" @checked(old('publicado', $product?->publicado))> Publicar en el catálogo</label>
</div>
<button type="submit" class="btn btn-success">{{ $product ? 'Actualizar producto' : 'Guardar producto' }}</button>
