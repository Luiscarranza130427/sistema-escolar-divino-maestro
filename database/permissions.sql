CREATE TABLE IF NOT EXISTS permiso_usuario (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    permiso VARCHAR(50) NOT NULL,
    otorgado BOOLEAN NOT NULL DEFAULT FALSE,
    otorgado_por INTEGER REFERENCES usuario(id) ON DELETE SET NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (usuario_id, permiso)
);

CREATE TABLE IF NOT EXISTS permisos (
    id BIGSERIAL PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuario_permisos (
    usuario_id BIGINT NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    permiso_id BIGINT NOT NULL REFERENCES permisos(id) ON DELETE CASCADE,
    otorgado_por BIGINT REFERENCES usuario(id) ON DELETE SET NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, permiso_id)
);

INSERT INTO permisos (codigo, nombre, descripcion) VALUES
    ('p_editar', 'Editar responsables', 'Crear y actualizar padres y apoderados.'),
    ('p_matricular', 'Gestionar matrículas', 'Registrar y actualizar matrículas.'),
    ('p_cobrar', 'Gestionar pagos', 'Registrar y administrar pagos escolares.'),
    ('p_auditoria', 'Gestionar auditoría', 'Eliminar registros de auditoría.')
ON CONFLICT (codigo) DO NOTHING;

INSERT INTO usuario_permisos (usuario_id, permiso_id, otorgado_por, creado_en)
SELECT asignacion.usuario_id, permiso.id, asignacion.otorgado_por, asignacion.creado_en
FROM permiso_usuario AS asignacion
JOIN permisos AS permiso ON permiso.codigo = asignacion.permiso
WHERE asignacion.otorgado = TRUE
ON CONFLICT (usuario_id, permiso_id) DO NOTHING;
