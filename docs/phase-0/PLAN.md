> Antecedente histórico: el estado vigente está en [INFORME_FINAL.md](../INFORME_FINAL.md). Las referencias a migración pendiente o MVC activo describen una etapa anterior.

# Fase 0 — transición arquitectónica

Orden vigente: 0 arquitectura Laravel; 1 backend/BD; 2 administración; 3 sitio público; 4 auditoría integral.

La auditoría previa es la línea base y no altera este orden. El sistema original es MVC PHP, no Laravel. Se migra incrementalmente a `laravel/`, sin sustituir el punto de entrada actual ni modificar datos de negocio. La convivencia es transitoria; una base nueva no equivale a declarar todos los módulos migrados.

## Decisiones

- Laravel 12 por compatibilidad con PHP 8.2 de XAMPP; Laravel 13 requiere PHP 8.3. No se actualiza XAMPP. Laravel 12 recibe seguridad hasta febrero de 2027: planificar actualización de PHP/framework antes de ese límite.
- Composer independiente y lock nuevo en laravel/; no alterar vendor ni el manifest del sistema existente.
- Modelos Eloquent mapeados a las tablas/PK españolas existentes; sin tabla users paralela ni recreación de datos.
- Auth, autorización, validación y HTTP nativos. No ejecutar controladores antiguos dentro de Laravel ni crear un router catch-all que eluda middleware.
- Servicios solo para coordinación real; no repositorio e interfaz por cada entidad.
- Pruebas con SQLite en memoria y esquema derivado de la BD existente; jamás RefreshDatabase sobre la BD comercial.
- Configuración de infraestructura en .env ignorado, parámetros estables en config y valores comerciales editables permanecen en BD.
- Proteger configuraciones, respaldos y herramientas mediante .htaccess locales; no modificar Apache, puertos o credenciales del servidor.

## Línea base / antipatrones

18 controladores, 19 modelos, 32 vistas y 108 declaraciones de métodos públicos. Falta framework, DI, middleware, requests y políticas centrales. Hay SQL en controladores, clases monolíticas, superglobales, require relativos, duplicación de reportes, secretos embebidos y estados incompatibles. Detalle: informe previo de 26 hallazgos.

## Puerta de cierre

La fase permanece abierta hasta migrar responsabilidades y probar compatibilidad de los flujos existentes. No basta con instalar Laravel, mover carpetas o aprobar lint. No avanzar a cambios funcionales, rediseño administrativo o integración pública como si esta fase estuviese cerrada.
