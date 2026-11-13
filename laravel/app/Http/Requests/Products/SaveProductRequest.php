<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'productos_editar' : 'productos_crear');
    }

    public function rules(): array
    {
        return ['nombre' => ['required', 'string', 'max:255'], 'descripcion' => ['nullable', 'string', 'max:10000'],
            'id_categoria' => ['nullable', 'integer', Rule::exists('categorias', 'id_categoria')->where('activo', 1)],
            'id_almacen' => ['required', 'integer', Rule::exists('almacenes', 'id')->where('activo', 1)],
            'id_proveedor_preferido' => ['nullable', 'integer', 'exists:proveedores,id_proveedor'],
            'codigo_barra' => ['nullable', 'string', 'max:100', Rule::unique('productos', 'codigo_barra')->ignore($this->route('id'), 'id_producto')],
            'precio_compra' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'], 'precio_venta' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:100000'], 'stock_inicial' => [$this->route('id') ? 'prohibited' : 'nullable', 'integer', 'min:0', 'max:100000'],
            'publicado' => ['required', 'boolean'], 'imagen_url' => ['nullable', 'url:http,https', 'max:511'], 'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048']];
    }
}
