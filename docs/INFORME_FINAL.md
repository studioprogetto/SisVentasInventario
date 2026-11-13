# Implementación y auditoría final

Fecha: 13/11/2026. Este informe sustituye los estados provisionales de `phase-0`, conservados como antecedentes. Se ejecutaron las fases en el orden solicitado.

Actualización posterior: POS visual por almacenes, búsqueda con espera de 3.000 ms y fidelidad integrada; detalles y evidencia vigentes en [POS.md](POS.md). La suite ampliada contiene 47 pruebas y 531 aserciones en SQLite y MariaDB; los conteos de 42 en la tabla siguiente corresponden al cierre inicial. La última revisión detectó seis diferencias de stock y tres ventas posteriores sin fotografías monetarias; el detalle y su bloqueo preventivo están en POS.md. Los conteos originales que siguen son la fotografía del cierre inicial, no una garantía del estado posterior a nuevas escrituras.

## Despliegue

Laravel es el runtime administrativo activo. El antiguo `public/index.php` redirige a Laravel; el MVC se conserva como archivo de reversión, protegido del acceso HTTP. No se recreó la base comercial ni se sustituyó XAMPP.

- Administración: http://localhost/ProyectoWeb/mi_sistema_mvc/laravel/public/auth/login
- Catálogo: http://localhost/ProyectoWeb/mi_sistema_mvc/laravel/public/catalogo
- Portada integrada: http://localhost/ProyectoWeb/

| Fase | Trabajo implementado |
|---|---|
| 0. Arquitectura | Laravel 12, modelos del esquema existente, controladores HTTP, FormRequests, servicios de negocio, permisos y dependencias Composer. |
| 1. Backend/datos | Migraciones aditivas, claves foráneas, decimales, transacciones, bloqueos, idempotencia, auditoría, existencias por almacén y fotografías financieras de operaciones nuevas. |
| 2. Administración | 16 pantallas; ventas, compras, transferencias, caja, devoluciones, cambios, maestros, usuarios, monedas, reportes, etiquetas, perfil y respaldos privados. |
| 3. Público | Categorías/productos/imágenes/publicación desde administración; catálogo HTML/API, sitemap y portada mediante vistas SQL con lector restringido. |
| 4. Validación | SQLite, MariaDB real, concurrencia entre procesos, navegación responsive, exportaciones, restauración aislada y documentación. |

## Integridad y seguridad

Se conservan 1.378 productos, 5.425 ventas, 8 compras, 4 usuarios y 48 clientes. Diferencias entre stock de productos y existencias: cero; existencias negativas: cero. No se introdujeron ventas, clientes ni usuarios ficticios en negocio. Los ensayos usan bases separadas `_test`.

Las operaciones nuevas calculan precios en servidor y dinero en centavos, congelan moneda/tasa/costo y devuelven el neto original proporcional. Compra y transferencia aplican stock una sola vez, con tránsito explícito. El kardex parte del saldo de corte: no inventa movimientos históricos.

Autenticación conserva hashes, revoca acceso de cuentas inactivas y consulta permisos vigentes. Se protege el último administrador. CSRF, validaciones, autorización, límites de login, escape HTML y CSP cubren los flujos nuevos. Descuentos, devoluciones, ajustes y respaldos requieren permisos específicos. Los archivos subidos se validan y almacenan con nombres internos; los respaldos son privados.

El usuario SQL público sólo consulta tres vistas; se verificó que no puede leer usuarios ni actualizar productos. El API no expone costos, proveedores ni cantidades internas. Los cambios publicados se reflejan en la siguiente consulta, sin conexión push.

## Evidencias

| Verificación | Resultado / archivo relativo a docs |
|---|---|
| SQLite | 42 pruebas, 470 aserciones; `validation/tests-sqlite.xml` |
| MariaDB real | 42 pruebas, 470 aserciones; `phase-0/tests-mariadb.xml` |
| Concurrencia, dos procesos | Siete casos aprobados; `validation/concurrency.json` |
| Administración y catálogo | 17 rutas × cinco anchos: 85 combinaciones sin errores JS ni desbordamiento global; `validation/browser-results.json` |
| Portada real | Cinco anchos aprobados; `validation/public-home.json` |
| Integridad y privilegios | `validation/live-read-check.json` |
| Restauración | Respaldo restaurado, conteos comparados y base temporal retirada; `validation/restore.json` |
| HTTP desplegado | Once comprobaciones de acceso y protección; `validation/http.json` |

Pint aprobado, PHPStan nivel 5 sin errores y sintaxis PHP comprobada en 108 archivos de aplicación, rutas, migraciones y herramientas.

Se ensayó venta en navegador, cotización y exportaciones PDF/XLSX con datos sintéticos. Se verificaron texto/páginas del PDF y celdas numéricas de XLSX. Las capturas están en `validation/responsive/`. Esto no constituye certificación universal de accesibilidad, auditoría externa de penetración ni prueba de carga sostenida. No se promete SLA sin medición.

## Excepciones históricas y aceptación

**Las 5.425 ventas originales no tienen moneda/costo congelados; tres saldos antiguos tienen estado o moneda sin definir.** Se conservaron sin inventar datos. No se calcula rentabilidad histórica con costos actuales; devoluciones que necesitan fotografías inexistentes se rechazan y saldos indeterminados no se gastan automáticamente.

Existe un turno anterior con nueve ventas antiguas: su cierre exige revisión explícita del cajero y auditoría. No se cerró durante las pruebas ni se rellenó su moneda. Conciliar requiere contrastar documentos, extractos y arqueos, identificar netos/monedas/saldos y aprobar una corrección trazable con respaldo. Sin esas fuentes no puede certificarse exactitud financiera histórica.

La instalación sigue local XAMPP/HTTP. Para publicación externa faltan dominio real, TLS, respaldo fuera del equipo y objetivos de recuperación acordados. No se añadió facturación tributaria, pasarela de pago ni recuperación por correo; el administrador puede restablecer contraseñas. Las páginas institucionales ajenas a portada/catálogo no tienen la misma cobertura de navegación.

La implementación técnica de las fases está entregada; conciliación histórica y aceptación de responsables de negocio permanecen abiertas. Consultar `MANUAL_USUARIO.md`, `MANUAL_TECNICO.md`, `MANTENIMIENTO_ITIL.md`, inventario real `architecture/routes-current.json`, metadatos `architecture/schema-current.json` y diagramas `architecture/DIAGRAMAS.md`.
