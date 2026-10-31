-- Tablas de cursos, periodos académicos y calificaciones.
-- Antes no estaban en schema.sql (solo existían en la base de datos) y
-- calificaciones.php agregaba la columna actualizado_en en cada carga de página.
-- Estructura deducida del código que las usaba.

CREATE TABLE IF NOT EXISTS curso (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    grado VARCHAR(50) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS periodo_academico (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (fecha_inicio <= fecha_fin)
);

CREATE TABLE IF NOT EXISTS calificacion (
    id BIGSERIAL PRIMARY KEY,
    matricula_id BIGINT NOT NULL REFERENCES matricula(id) ON DELETE CASCADE,
    curso_id BIGINT NOT NULL REFERENCES curso(id) ON DELETE RESTRICT,
    periodo_id BIGINT NOT NULL REFERENCES periodo_academico(id) ON DELETE RESTRICT,
    nota NUMERIC(4, 2) NOT NULL CHECK (nota >= 0 AND nota <= 20),
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (matricula_id, curso_id, periodo_id)
);

ALTER TABLE calificacion
    ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
