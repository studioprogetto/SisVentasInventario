# Manual de usuario

El POS se actualizó a catálogo visual con filtros por almacén, búsqueda por nombre/código/DNI y tarjeta de estrellas. Consulta [POS.md](POS.md) para el flujo vigente; el total ahora se calcula automáticamente antes de finalizar.

Entrar al acceso administrativo del informe con la cuenta existente. El menú depende de permisos. Cerrar sesión al terminar. Perfil permite preferencias y contraseña; el logo PNG requiere administración.

| Pantalla | Operación |
|---|---|
| Inicio | Resumen autorizado. |
| Venta | Buscar producto/servicio, filtrar categoría, cliente, moneda y pago; cotizar y confirmar total. |
| Historial | Consultar ticket y tramitar devolución/cambio con motivo. |
| Productos | Datos, foto, precio, código, publicación, estado, etiquetas y kardex. Ajustes requieren permiso y motivo. |
| Servicios | Prestaciones sin stock físico. |
| Categorías | Organización y publicación. |
| Almacenes | Ubicaciones; no desactivar con stock o tránsito. |
| Transferencias | Crear pendiente, enviar, recibir o cancelar. |
| Compras | Proveedor, almacén, moneda y costos; recibir incorpora stock. |
| Proveedores | Datos y estado. |
| Clientes | Datos, selección en venta y saldos identificados. |
| Caja | Abrir turno propio, movimientos con motivo, arqueo y cierre. |
| Reportes | Informe, filtros y HTML/PDF/XLSX/CSV. |
| Usuarios | Cuentas y roles dentro del nivel autorizado. |
| Tipo de cambio | Tasa vigente; no modifica operaciones anteriores. |
| Monedas | Monedas y estado; base en uso protegida. |

Abrir caja antes de vender. La cotización no registra venta: confirmar sólo tras revisar el total. Si cambia, cotizar de nuevo. Precios/descuentos se verifican en servidor. Crédito sólo se consume en moneda identificada y la diferencia usa el pago elegido.

Para devolver, seleccionar detalle original, cantidad y motivo. No superar cantidad pendiente. Se devuelve neto original con descuento proporcional; productos reingresan a su almacén y servicios no generan stock. El cambio registra devolución y nueva venta en una transacción. Documentos antiguos sin datos suficientes requieren conciliación.

Al cerrar declarar efectivo contado; electrónicos no son efectivo. Si hay documentos antiguos se exige confirmar revisión, sin alterar su moneda. Un fallo de respaldo posterior no deshace el cierre: revisar el aviso y repetir desde Caja/Respaldos con permiso.

Publicar producto y categoría activos para mostrarlos. Precio, imagen y estado cambian al consultar de nuevo; costo/stock exacto no son públicos. Ante error corregir validación o actualizar disponibilidad. Si una respuesta se corta, revisar historial antes de iniciar otra operación. No ocultar descuadres con documentos nuevos: registrar incidencia con hora e identificador, sin contraseñas ni información personal innecesaria.
