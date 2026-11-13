# Manual técnico

Runtime `laravel/`: Laravel 12.72, PHP 8.2.12, MariaDB 10.4.32. Http adapta peticiones; Services contiene negocio; Eloquent mapea claves originales. Money usa centavos y decimales para tasas. FPDF existente carga por Composer; Picqer genera Code128; XLSX usa XMLWriter/PharData.

`.env`, `config/local.php`, `config/catalog-local.php` son privados e ignorados. Debug apagado, escritura local habilitada, zona America/Lima y sesión SQL -05:00. `APP_READ_ONLY=true` bloquea mutaciones durante mantenimiento. No hay cuenta demo comercial. Instalar dependencias desde lock y generar clave propia en otra instalación.

Apache protege código y respaldos. El punto antiguo redirige y MVC archivado no procesa negocio. En la carpeta superior se modificaron index.php, productos.php, conexion.php, .htaccess, robots.txt; se añadieron js/infinite-slider.js, js/home-behavior.js, js/home-ui.js y css/home-fixes.css. Originales relevantes: `docs/phase-0/site-*`. Cambiar URL local de robots al establecer dominio.

## Actualización y recuperación

La base original de 26 tablas es inmutable para ensayos. Las migraciones aditivas 100000/110000/120000 están aplicadas. `migrate` normal se bloquea sobre negocio. Desde laravel ejecutar exclusivamente actualización controlada:

```powershell
C:\xampp\php\php.exe artisan system:upgrade --database-name=tienda_sistema
```

Exige nombre exacto, respaldo consistente con SHA y permiso transitorio de conexión/proceso. Nunca fresh, rollback ni seed sobre negocio. MariaDB DDL no garantiza reversión transaccional total.

Respaldos privados en storage/app/private/backups y descarga autorizada desde Caja. `tools/restore-check.php` restaura a base aleatoria nueva `_test`, compara conteos y elimina sólo su propia base. Para recuperar negocio: detener escritura, guardar evidencia y respaldo del estado fallido, restaurar primero en entorno separado, validar documentos/stock y acordar corte antes de cambiar conexión. No sobreescribir operaciones posteriores ni revertir sólo a MVC tras nuevos movimientos sin conciliación.

## Contratos

Inventario exacto de rutas y middleware: `architecture/routes-current.json`. Validaciones: `app/Http/Requests`. GET público `/api/catalogo/productos`, `/api/catalogo/productos/{slug}`, `/api/catalogo/categorias`; HTML `/catalogo`; imágenes de productos visibles por identificador. Campos permitidos en CatalogProductResource, publicación/actividad siempre comprobadas.

Mutaciones internas exigen sesión, CSRF, permisos y modo escritura; no son API externa de token. Con Accept application/json: validación 422, permiso 403, CSRF 419, límite 429, inexistente 404. HTML anónimo redirige al login.

Venta POST venta/guardar: idempotency_key de 16–64 caracteres alfanuméricos/guion, carrito con id/tipo producto o servicio/cantidad entera positiva y almacén opcional; cliente/moneda, descuento autorizado, saldo y accepted_total opcionales; método de pago permitido. venta/cotizar calcula sin escribir. Servidor fija precio/costo; misma clave y datos devuelve operación original, distinta carga se rechaza.

Devolución: venta/procesarDevolucion, clave, id_venta_original, productos con id_detalle_venta/cantidad, observacion y destino saldo/efectivo. Cambio añade carrito nuevo. Compra: proveedor/almacén/moneda y productos/cantidad/costo; compra/recibir/{id} incorpora stock una vez. Transferencia: origen/destino distintos, productos y transición enviada/recibida/cancelada válida. Ajuste: producto/almacén/delta no cero/motivo/clave. Caja: importes, clave y descripción; cierre histórico confirmar_legacy aceptado.

## Verificación

Desde laravel: PHPUnit, Pint --test y PHPStan analyse --memory-limit=512M. Desde raíz: tools/test-mariadb.php crea base vacía aislada; browser-server.php inicia ensayo local; browser-qa.py valida vistas/venta/exportaciones y public-qa.py portada real. concurrency-test.php usa configuración de ensayo local. final-read-check.php sólo consulta negocio y comprueba privilegios sin modificar filas. Credenciales sintéticas de ensayo no se publican.

Sin carga sostenida ni garantía de respuesta. Vigilar disco, consultas lentas, bloqueos y crecimiento de auditoría/kardex/imágenes/respaldos. Los reportes separan monedas y sólo calculan rentabilidad de costos congelados; saldo de corte no es kardex histórico completo.
