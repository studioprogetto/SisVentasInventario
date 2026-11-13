from pathlib import Path
import json
import re

root = Path(__file__).resolve().parents[1]
schema = json.loads((root/'docs/architecture/schema-current.json').read_text(encoding='utf-8-sig'))
lines = ['# Diagramas de la implementación', '', 'Metadatos físicos extraídos del esquema desplegado; las relaciones son claves foráneas reales, no inferencias.', '', '```mermaid', 'erDiagram']
for table, definition in schema.items():
    ddl = definition['ddl']
    if not re.search(r'CREATE TABLE', ddl, re.I):
        continue
    lines.append('    '+table+' {')
    for col in definition['columns']:
        key = ' PK' if col['COLUMN_KEY'] == 'PRI' else ''
        lines.append('        '+col['DATA_TYPE']+' '+col['COLUMN_NAME']+key)
    lines.append('    }')
    for target in re.findall(r'REFERENCES\s+`([^`]+)`', ddl):
        lines.append('    '+target+' ||--o{ '+table+' : FK')
lines += ['```', '', '## Componentes y despliegue', '', '```mermaid', 'flowchart LR', '  Browser[Navegador] --> Apache[Apache local XAMPP]', '  Apache --> Laravel[Laravel: rutas y middleware]', '  Laravel --> HTTP[Controladores y FormRequests]', '  HTTP --> Services[Servicios transaccionales]', '  Services --> Models[Eloquent]', '  Models --> DB[(MariaDB)]', '  Services --> Private[Almacenamiento privado y respaldos]', '  Apache --> Home[Portada PHP]', '  Home --> Views[Vistas SQL públicas]', '  Views --> DB', '```', '', '## Actividad de venta', '', '```mermaid', 'flowchart TD', '  A[Autenticar y autorizar] --> B[Cotizar sin escribir]', '  B --> C[Confirmar total y clave]', '  C --> D[Transacción e idempotencia]', '  D --> E[Bloquear caja y existencias]', '  E --> F{Total y stock válidos}', '  F -->|Sí| G[Venta, detalles, saldo, kardex y auditoría]', '  G --> H[Commit y ticket]', '  F -->|No| I[Rollback y validación]', '```', '', '## Casos de uso', '', '```mermaid', 'flowchart LR', '  Publico[Visitante] --> Catalogo[Consultar catálogo publicado]', '  Cajero[Cajero autorizado] --> Venta[Vender y gestionar caja propia]', '  Cajero --> Ticket[Consultar tickets autorizados]', '  Operador[Operador autorizado] --> Compra[Compra y recepción]', '  Operador --> Transferencia[Transferir inventario]', '  Supervisor[Supervisor con permiso] --> Devolver[Devolución y cambio]', '  Admin[Administrador] --> Usuarios[Cuentas y permisos]', '  Admin --> Backup[Respaldo privado]', '  Admin --> Ajuste[Ajuste motivado]', '```', '', 'Los actores requieren permisos concretos por operación; no se crea una nueva jerarquía de roles con estos nombres descriptivos.']
(root/'docs/architecture/DIAGRAMAS.md').write_text('\n'.join(lines)+'\n', encoding='utf-8')
readme = root/'laravel/README.md'
readme.write_text('''# Studio & Progetto — Laravel

Runtime administrativo activo. Estado vigente: [informe final](../docs/INFORME_FINAL.md), [manual técnico](../docs/MANUAL_TECNICO.md), [manual de usuario](../docs/MANUAL_USUARIO.md).

Acceso: http://localhost/ProyectoWeb/mi_sistema_mvc/laravel/public/auth/login

PHP 8.2.12, Laravel 12.72, MariaDB 10.4.32. Dependencias desde composer.lock; configuración privada en .env. Escritura local habilitada, debug apagado. APP_READ_ONLY=true bloquea mutaciones durante mantenimiento. No hay cuenta demo comercial.

Pruebas: php vendor/phpunit/phpunit/phpunit; php vendor/laravel/pint/builds/pint --test; php vendor/phpstan/phpstan/phpstan analyse --memory-limit=512M. Ensayo MariaDB separado: ../tools/test-mariadb.php.

Actualizar negocio exclusivamente con artisan system:upgrade --database-name=tienda_sistema, con respaldo y permiso transitorio. No fresh/rollback/seed sobre negocio. Históricos sin moneda/costo y saldos sin identificar requieren conciliación documental.
''', encoding='utf-8')
for name in ['INFORME.md', 'PLAN.md']:
    path = root/'docs/phase-0'/name
    if path.exists():
        content = path.read_text(encoding='utf-8-sig')
        if not content.startswith('> Antecedente'):
            path.write_text('> Antecedente histórico: el estado vigente está en [INFORME_FINAL.md](../INFORME_FINAL.md). Las referencias a migración pendiente o MVC activo describen una etapa anterior.\n\n'+content, encoding='utf-8')
print('Documentación y diagramas actualizados; tablas/vistas inventariadas:', len(schema))
