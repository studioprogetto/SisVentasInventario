<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ((float) $this->input('descuento_manual', 0) <= 0 || $this->user()->can('ventas_aplicar_descuento'));
    }

    public function rules(): array
    {
        $key = ['idempotency_key' => ['required', 'string', 'regex:/^[a-zA-Z0-9_-]{16,64}$/D']];
        $sale = [
            'carrito' => ['required', 'array', 'min:1', 'max:200'], 'carrito.*.id' => ['required', 'integer', 'min:1'],
            'carrito.*.tipo' => ['required', Rule::in(['producto', 'servicio'])], 'carrito.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'carrito.*.id_almacen' => ['nullable', 'integer', 'exists:almacenes,id'], 'id_cliente' => ['nullable', 'integer', 'exists:clientes,id_cliente'],
            'id_moneda' => ['nullable', 'integer', 'exists:tipo_moneda,id'], 'descuento_manual' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'metodo_pago' => ['required', Rule::in(config('business.payment_methods'))], 'monto_recibido' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'observacion' => ['nullable', 'string', 'max:255'], 'usar_saldo' => ['nullable', 'boolean'],
            'accepted_total' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
        ];
        $products = ['productos' => ['required', 'array', 'min:1', 'max:200'], 'productos.*.id_producto' => ['required', 'integer', 'distinct', 'exists:productos,id_producto'], 'productos.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000']];
        $return = ['id_venta_original' => ['required', 'integer', 'exists:ventas,id_venta'], 'productos' => ['required', 'array', 'min:1', 'max:200'],
            'productos.*.id_detalle_venta' => ['required', 'integer', 'distinct'], 'productos.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'], 'observacion' => ['required', 'string', 'max:255']];
        $return['destino'] = ['sometimes', Rule::in(['saldo', 'cambio'])];

        $return += [
            'id_cliente' => ['nullable','integer','exists:clientes,id_cliente'],
            'condicion' => ['sometimes', Rule::in(['defectuoso','conforme'])],
            'reemplazo' => ['required_if:destino,cambio','nullable','integer','exists:productos,id_producto'],
            'metodo_pago' => ['sometimes',Rule::in(config('business.payment_methods'))],
        ];
        return match ($this->route()->getName()) {
            'sales.store' => $key + $sale,
            'sales.quote' => $sale,
            'returns.request', 'sales.return' => $key + $return,
            'sales.exchange' => $key + $return + $sale,
            'purchases.store' => $key + $products + ['id_proveedor' => ['required', 'integer', 'exists:proveedores,id_proveedor'], 'id_almacen' => ['required', 'integer', 'exists:almacenes,id'], 'id_moneda' => ['nullable', 'integer', 'exists:tipo_moneda,id'], 'productos.*.costo_unitario' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999']],
            'transfers.store' => $key + $products + ['origen' => ['required', 'integer', 'exists:almacenes,id'], 'destino' => ['required', 'integer', 'different:origen', 'exists:almacenes,id']],
            'transfers.transition' => ['estado' => ['required', Rule::in(['enviada', 'recibida', 'cancelada'])]],
            'inventory.adjust' => $key + ['id_producto' => ['required', 'integer', 'exists:productos,id_producto'], 'id_almacen' => ['required', 'integer', 'exists:almacenes,id'], 'delta' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'], 'motivo' => ['required', 'string', 'min:5', 'max:255']],
            'cash.open' => $key + ['observacion'=>['nullable','string','max:255'],'monto_inicial' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999']],
            'cash.close' => $key + ['observacion'=>['nullable','string','max:255'],'monto_final_real' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'], 'confirmar_legacy' => ['sometimes', 'accepted']],
            'cash.movement' => $key + ['monto' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999'], 'tipo_movimiento' => ['required', Rule::in(['ingreso', 'egreso'])], 'descripcion' => ['required', 'string', 'min:3', 'max:255']],
            default => [],
        };
    }
}
