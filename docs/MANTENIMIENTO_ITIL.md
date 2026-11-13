# Operación y mejora continua

Procedimientos inspirados en prácticas ITIL; no son certificación ni evidencia de madurez organizacional.

| Práctica | Procedimiento/evidencia | Pendiente operativo |
|---|---|---|
| Incidentes | Registrar hora, ruta, operación, impacto y logs privados sin secretos. | Responsable y canal de atención. |
| Problemas | Relacionar repetición, causa, corrección y regresión; histórico sin moneda es problema abierto. | Conciliación con fuentes comerciales. |
| Cambios | Alcance, revisión, respaldo, prueba aislada, ventana y reversión. | Aprobador y calendario comercial. |
| Configuración | Rutas/esquema, lock y versiones; secretos fuera de Git. | Inventario de equipo/dominio/certificados. |
| Seguridad | Revisar cuentas, roles, parches y auditoría; pruebas CSRF y privilegios disponibles. | Revisión independiente y TLS externo. |
| Continuidad | Respaldo/checksum y restauración aislada verificada. | Copia externa cifrada, retención, RPO/RTO. |
| Capacidad | Revisar disco, servicios, bloqueos y consultas lentas. | Alertas y medición; SLA no establecido. |
| Conocimiento | Manuales, pruebas y diagramas versionados. | Capacitación y aceptación de usuarios. |
| Mejora | Riesgo, dueño, prioridad, fecha y comprobación. | Seguimiento de conciliación. |

Rutina propuesta: diaria acceso/disco/respaldos e incidencias; semanal checksums/permisos/errores repetidos; mensual y después de esquema restauración aislada y regresión. Las frecuencias son propuestas, no ejecuciones acreditadas. Pérdida de integridad o exposición: suspender escritura, preservar evidencia y activar recuperación revisada. Flujo bloqueado: documentar alternativa autorizada y corregir aislado. Presentación sin interrupción: programar mejora. Acordar tiempos con responsables; no deducirlos de ensayos locales.
