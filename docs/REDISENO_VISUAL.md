# Rediseño visual — 13/11/2026

Se recuperó la identidad del MVC anterior: logo, barra lateral azul y navegación por módulos. El diseño se implementa en Blade y CSS propios, sin reactivar Bootstrap, scripts antiguos ni dependencias de CDN.

## Organización

- `laravel/resources/views/layouts/app.blade.php`: estructura administrativa.
- `layouts/sidebar.blade.php` y `layouts/topbar.blade.php`: navegación y cuenta.
- `components/icon.blade.php`: iconos SVG locales.
- `components/nav-link.blade.php`: enlace con estado activo y aria-current.
- `components/stat-card.blade.php`: indicadores reutilizables.
- `components/theme-toggle.blade.php`: control de apariencia compartido.
- `dashboard/index.blade.php`: métricas, actividad, resúmenes y alertas.
- `laravel/public/css/app.css`: variables semánticas, superficies neomórficas, formularios, tablas, móvil e impresión.
- `laravel/public/js/theme.js`: preferencia persistente y detección de tema del sistema.

## Apariencia y comportamiento

Tema claro gris azulado con sombras dobles y controles hundidos; tema oscuro de base exacta `#1a2a44`, bordes visibles, texto claro y acentos diferenciados. El selector de sol/luna está en barra superior, login y catálogo. La preferencia se conserva localmente; sin selección sigue al sistema. Si el almacenamiento está bloqueado, el control sigue funcionando durante la visita.

El dashboard distingue acumulados históricos del periodo seleccionado, conserva importes separados por moneda y muestra advertencias históricas. No hay porcentajes de crecimiento ni datos simulados. La gráfica tiene tabla alternativa accesible.

En móvil el menú se oculta, abre con botón, se cierra con Escape o fondo y devuelve foco al control. Los enlaces ocultos no reciben foco; el menú abierto conserva navegación por teclado. Tablas anchas desplazan su propio contenedor. Se respeta movimiento reducido y existe enlace para saltar al contenido. La impresión omite controles y sombras.

## Verificación

`tools/theme-qa.py` comprueba 17 rutas, cinco anchos y dos temas (170 combinaciones), preferencia tras recarga, menú y foco. Evidencia en `validation/redesign/results.json` y capturas de dashboard/inventario/venta. `tools/browser-qa.py` mantiene validación de venta con cotización y exportaciones. La suite funcional conserva 42 pruebas y 470 aserciones.

Estas comprobaciones no son certificación formal de accesibilidad. Las modificaciones son de presentación; no cambian stock, permisos ni reglas financieras. Las capturas de ensayo contienen datos sintéticos de una base aislada.
