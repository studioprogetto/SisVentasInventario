-- Índices recomendados para acelerar búsquedas de productos y clientes.
-- Ejecutar una sola vez sobre la base de datos de producción.

CREATE INDEX idx_productos_activo_nombre
    ON productos (activo, nombre);

CREATE INDEX idx_productos_activo_codigo_barra
    ON productos (activo, codigo_barra);

CREATE INDEX idx_productos_activo_categoria
    ON productos (activo, id_categoria);

CREATE INDEX idx_clientes_activo_nombre
    ON clientes (activo, nombre_cliente);

CREATE INDEX idx_clientes_activo_documento
    ON clientes (activo, documento_identidad);

CREATE INDEX idx_ventas_estado_fecha
    ON ventas (estado, fecha_venta);
