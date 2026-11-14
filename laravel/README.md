# Studio & Progetto — Laravel

Runtime administrativo activo. Estado vigente: [informe final](../docs/INFORME_FINAL.md), [manual técnico](../docs/MANUAL_TECNICO.md), [manual de usuario](../docs/MANUAL_USUARIO.md).

Acceso: http://localhost/ProyectoWeb/mi_sistema_mvc/laravel/public/auth/login

PHP 8.2.12, Laravel 12.72, MariaDB 10.4.32. Dependencias desde composer.lock; configuración privada en .env. Escritura local habilitada, debug apagado. APP_READ_ONLY=true bloquea mutaciones durante mantenimiento. No hay cuenta demo comercial.

Pruebas: php vendor/phpunit/phpunit/phpunit; php vendor/laravel/pint/builds/pint --test; php vendor/phpstan/phpstan/phpstan analyse --memory-limit=512M. Ensayo MariaDB separado: ../tools/test-mariadb.php.

Actualizar negocio exclusivamente con artisan system:upgrade --database-name=tienda_sistema, con respaldo y permiso transitorio. No fresh/rollback/seed sobre negocio. Históricos sin moneda/costo y saldos sin identificar requieren conciliación documental.

Interfaz de nuevos módulos: extender layouts.app y conservar los estilos compartidos de responsive.css. Los listados deben usar tablas semánticas con thead, th y tbody para que responsive.js transforme cada fila en un acordeón sin duplicar acciones en móvil. Usar form-grid para campos, x-create-dialog para altas y contenedores con altura definida para Chart.js (maintainAspectRatio: false). Verificar móvil, tablet vertical y escritorio; mantener accesibles los controles sin desplazamiento horizontal.
