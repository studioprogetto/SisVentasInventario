# POS visual y operativo

Actualización: 13/11/2026. Acceso: `/ProyectoWeb/mi_sistema_mvc/laravel/public/venta`.

## Operación

El catálogo presenta imagen, nombre, precio en la moneda seleccionada, almacén y disponibilidad. Las pestañas filtran almacenes activos; Servicios muestra prestaciones sin inventario. Un producto presente en dos almacenes aparece por separado y cada línea del carrito conserva su origen. No se filtra por categoría.

Si el stock total difiere de la suma por almacenes, la tarjeta exige conciliación y no permite agregarlo. El servidor también bloquea cotización/venta de ese producto sin reescribir existencias ni conceder sellos.

La búsqueda por nombre o código espera **300 ms desde la última escritura**. No recarga la página ni consulta indefinidamente si no cambia el texto. Enter o el botón busca inmediatamente. Cambiar almacén/página/moneda también consulta al momento. La paginación limita los resultados a 8, 12 o 24. Peticiones anteriores se cancelan para evitar respuestas desactualizadas.

Agregar con +, modificar cantidad o quitar con ×. El total se cotiza automáticamente en servidor. Se muestran subtotal, descuento de fidelidad, descuento autorizado, saldo aplicado y por cobrar. Finalizar sólo se habilita con una cotización vigente. El servidor vuelve a comprobar total, caja, permisos y existencias al registrar, dentro de una transacción idempotente. Una cotización no reserva stock.

## Clientes y fidelidad

Buscar nombre o DNI/documento, esperar 300 ms o pulsar Enter, y seleccionar el resultado. La tarjeta muestra documento, ventas completadas, saldo pendiente identificado en la moneda elegida y doce estrellas. Quitar cliente vuelve a venta genérica.

Se usan los sellos registrados; no se reconstruyen contando ventas antiguas. Una compra con productos suma un sello: sexta compra 5%, duodécima 10%, después reinicia tarjeta. Una venta sólo de servicios no suma sellos. Estrellas doradas: acumuladas; verde: próxima compra; grises: pendientes. El texto indica si la próxima compra con productos alcanza descuento. El descuento efectivo figura en la cotización del servidor.

Saldo sin moneda definida o con estado antiguo indeterminado no se gasta. Saldo identificado puede aplicarse al activar su opción; se separa el monto restante. La tarjeta no suma monedas diferentes.

## Implementación y evidencia

- Vista `laravel/resources/views/sales/pos.blade.php` y parcial `sales/partials/pos-client.blade.php`.
- CSS específico `laravel/public/css/pos.css`, JavaScript `laravel/public/js/pos.js`.
- `PosController` y `PosQuery`: catálogo paginado por existencias, imagen interna y ficha autorizada.
- GET `pos/items`: term, almacen, tipo, page, per_page, id_moneda; GET `pos/clientes/{id}`: id_moneda; GET `pos/imagenes/{id}`. Exigen sesión activa y permiso ventas_crear; los datos personales no son públicos.
- `SaleQuote` devuelve precios/subtotales por línea sin exponer costo. No se aceptan precios enviados por navegador.

Las imágenes reutilizan archivos privados nuevos, archivos MVC, imagen_url, URLs antiguas en imagen_path y fotografías por código/nombre. Si no existe fotografía se muestra un marcador explícito; no se inventa una imagen del producto.

En la revisión de negocio de las 15:07 se observaron 5.428 ventas, tres más que en el cierre anterior, y seis diferencias de stock, sin existencias negativas. Las tres ventas nuevas carecen de moneda y no hay auditorías nativas asociadas; su origen exacto no se atribuye sin evidencia. Sus importes/productos no corresponden a las ventas sintéticas del navegador, que se verificaron en base aislada. No se modificaron esos registros comerciales. No operar simultáneamente con un escritor antiguo: conciliar los seis productos con sus documentos antes de habilitarlos en el POS nuevo.

`PosTest` verifica permisos, almacenes, precio, fotos privadas, búsqueda por documento, servicio sin stock, fidelidad, crédito identificado y venta desde dos almacenes. `tools/pos-qa.py` verifica espera real de 300 ms, cancelación de búsquedas intermedias, selección por DNI, cinco estrellas/5% real, total, filtro, diez combinaciones tamaño/tema y cierre de venta sintética. Evidencia: `validation/pos/results.json` y capturas. Toda venta de ensayo se realiza en una base separada `_test`.

Validación final: 47 pruebas y 531 aserciones aprobadas tanto en SQLite como MariaDB; Pint aprobado y PHPStan nivel 5 sin errores. La regresión de navegador de 85 rutas/tamaños también pasó con la nueva interfaz de venta. El control de integridad comercial queda observado por las seis diferencias, aunque las pruebas aisladas pasan; no se presenta como aprobado.
