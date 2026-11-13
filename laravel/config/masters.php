<?php

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Servicio;
use App\Models\TipoMoneda;

return [
    'cliente' => ['title' => 'Clientes', 'model' => Cliente::class, 'permission' => 'clientes', 'fields' => [
        'nombre_cliente' => ['label' => 'Nombre', 'rules' => 'required|string|max:150'],
        'documento_identidad' => ['label' => 'Documento', 'rules' => 'nullable|string|max:20', 'unique' => true],
        'telefono' => ['label' => 'Teléfono', 'rules' => 'nullable|string|max:20'],
        'email' => ['label' => 'Correo', 'rules' => 'nullable|email|max:100'], 'direccion' => ['label' => 'Dirección', 'rules' => 'nullable|string|max:2000'],
    ]],
    'proveedor' => ['title' => 'Proveedores', 'model' => Proveedor::class, 'permission' => 'proveedores', 'fields' => [
        'nombre_proveedor' => ['label' => 'Nombre', 'rules' => 'required|string|max:150'], 'ruc' => ['label' => 'RUC', 'rules' => 'nullable|digits:11', 'unique' => true],
        'telefono' => ['label' => 'Teléfono', 'rules' => 'nullable|string|max:20'], 'email' => ['label' => 'Correo', 'rules' => 'nullable|email|max:100'], 'direccion' => ['label' => 'Dirección', 'rules' => 'nullable|string|max:2000'],
    ]],
    'almacen' => ['title' => 'Almacenes', 'model' => Almacen::class, 'permission' => 'almacenes', 'all_permission' => 'almacenes_gestionar', 'fields' => [
        'nombre' => ['label' => 'Nombre', 'rules' => 'required|string|max:100'], 'ubicacion' => ['label' => 'Ubicación', 'rules' => 'nullable|string|max:255'],
    ]],
    'servicio' => ['title' => 'Servicios', 'model' => Servicio::class, 'permission' => 'servicios', 'all_permission' => 'servicios_gestionar', 'fields' => [
        'nombre' => ['label' => 'Nombre', 'rules' => 'required|string|max:150'], 'descripcion' => ['label' => 'Descripción', 'rules' => 'nullable|string|max:5000'], 'precio_venta' => ['label' => 'Precio', 'rules' => 'required|decimal:0,2|numeric|min:0.01|max:99999999'],
    ]],
    'tipomoneda' => ['title' => 'Monedas', 'model' => TipoMoneda::class, 'permission' => 'tipomoneda', 'all_permission' => 'tipomoneda_gestionar', 'fields' => [
        'descripcion' => ['label' => 'Nombre', 'rules' => 'required|string|max:50'], 'simbolo' => ['label' => 'Símbolo', 'rules' => 'required|string|max:5'], 'codigo' => ['label' => 'Código ISO', 'rules' => 'required|regex:/^[A-Z]{3}$/', 'unique' => true],
    ]],
];
