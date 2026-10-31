
CREATE TABLE IF NOT EXISTS usuario (
    id BIGSERIAL PRIMARY KEY,
    nombres VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL CHECK (rol IN ('director', 'asistente', 'hiperadmin')),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    permiso_matricular BOOLEAN NOT NULL DEFAULT FALSE,
    permiso_cobrar BOOLEAN NOT NULL DEFAULT FALSE,
    permiso_editar BOOLEAN NOT NULL DEFAULT FALSE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS estudiante (
    id BIGSERIAL PRIMARY KEY,
    dni VARCHAR(8) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE estudiante ADD COLUMN IF NOT EXISTS estado_academico VARCHAR(20) NOT NULL DEFAULT 'Activo';
ALTER TABLE estudiante DROP CONSTRAINT IF EXISTS estudiante_estado_academico_check;
ALTER TABLE estudiante
    ADD CONSTRAINT estudiante_estado_academico_check
    CHECK (estado_academico IN ('Activo', 'Retirado', 'Trasladado'));
ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_rol_check;
ALTER TABLE usuario
    ADD CONSTRAINT usuario_rol_check
    CHECK (rol IN ('director', 'asistente', 'hiperadmin'));

CREATE TABLE IF NOT EXISTS nivel (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS anio (
    id BIGSERIAL PRIMARY KEY,
    anio SMALLINT NOT NULL UNIQUE CHECK (anio BETWEEN 2020 AND 2100),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS grado (
    id BIGSERIAL PRIMARY KEY,
    nivel_id BIGINT NOT NULL REFERENCES nivel(id) ON DELETE CASCADE,
    numero SMALLINT NOT NULL CHECK (numero BETWEEN 1 AND 6),
    nombre VARCHAR(30) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (nivel_id, numero)
);
CREATE TABLE IF NOT EXISTS seccion (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(10) NOT NULL UNIQUE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS grado_seccion (
    id BIGSERIAL PRIMARY KEY,
    grado_id BIGINT NOT NULL REFERENCES grado(id) ON DELETE CASCADE,
    seccion_id BIGINT NOT NULL REFERENCES seccion(id) ON DELETE CASCADE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (grado_id, seccion_id)
);

CREATE TABLE IF NOT EXISTS matricula (
    id BIGSERIAL PRIMARY KEY,
    estudiante_id BIGINT REFERENCES estudiante(id) ON DELETE CASCADE,
    anio_id BIGINT REFERENCES anio(id) ON DELETE RESTRICT,
    nivel_id BIGINT REFERENCES nivel(id) ON DELETE RESTRICT,
    grado_seccion_id BIGINT REFERENCES grado_seccion(id) ON DELETE RESTRICT,
    apoderado_principal_id BIGINT,
    es_estudiante_nuevo BOOLEAN NOT NULL DEFAULT TRUE,
    registrado_por BIGINT REFERENCES usuario(id) ON DELETE SET NULL,
    nombres VARCHAR(100),
    apellido_paterno VARCHAR(100),
    apellido_materno VARCHAR(100),
    dni VARCHAR(8),
    nivel VARCHAR(30),
    estado VARCHAR(20) NOT NULL DEFAULT 'Activa',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE matricula ADD COLUMN IF NOT EXISTS anio_id BIGINT;
ALTER TABLE matricula ADD COLUMN IF NOT EXISTS grado_seccion_id BIGINT;
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname IN ('matricula_anio_id_fkey', 'matriculas_anio_id_fkey')
    ) THEN
        ALTER TABLE matricula
            ADD CONSTRAINT matricula_anio_id_fkey
            FOREIGN KEY (anio_id) REFERENCES anio(id) ON DELETE RESTRICT;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'matricula_grado_seccion_id_fkey'
    ) THEN
        ALTER TABLE matricula
            ADD CONSTRAINT matricula_grado_seccion_id_fkey
            FOREIGN KEY (grado_seccion_id) REFERENCES grado_seccion(id) ON DELETE RESTRICT;
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS matricula_estudiante_anio_unique
    ON matricula (estudiante_id, anio_id)
    WHERE estudiante_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS matricula_estudiante_anio_id_unique
    ON matricula (estudiante_id, anio_id)
    WHERE estudiante_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS concepto_pago (
    id BIGSERIAL PRIMARY KEY,
    descripcion VARCHAR(80) NOT NULL UNIQUE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    monto_default NUMERIC(10, 2) CHECK (monto_default >= 0),
    monto_total NUMERIC(10, 2) CHECK (monto_total >= 0),
    tipo_cobro VARCHAR(20) NOT NULL DEFAULT 'estudiante'
        CHECK (tipo_cobro IN ('estudiante', 'familia')),
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE concepto_pago ADD COLUMN IF NOT EXISTS tipo_cobro VARCHAR(20) NOT NULL DEFAULT 'estudiante';
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'concepto_pago_tipo_cobro_check'
    ) THEN
        ALTER TABLE concepto_pago
            ADD CONSTRAINT concepto_pago_tipo_cobro_check
            CHECK (tipo_cobro IN ('estudiante', 'familia'));
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS pago (
    id BIGSERIAL PRIMARY KEY,
    matricula_id BIGINT REFERENCES matricula(id) ON DELETE RESTRICT,
    concepto_id BIGINT REFERENCES concepto_pago(id) ON DELETE RESTRICT,
    monto NUMERIC(10, 2) NOT NULL CHECK (monto >= 0),
    abono_hoy NUMERIC(10, 2) NOT NULL DEFAULT 0 CHECK (abono_hoy >= 0),
    entregado_por VARCHAR(150),
    monto_total NUMERIC(10, 2),
    monto_pagado NUMERIC(10, 2) NOT NULL DEFAULT 0,
    saldo_restante NUMERIC(10, 2) NOT NULL DEFAULT 0,
    nro_recibo VARCHAR(30),
    talonario VARCHAR(30),
    estado_pago VARCHAR(20) NOT NULL DEFAULT 'pagado' CHECK (estado_pago IN ('pagado', 'pendiente', 'anulado')),
    fecha_pago DATE NOT NULL,
    registrado_por BIGINT REFERENCES usuario(id) ON DELETE SET NULL,
    responsable VARCHAR(150),
    concepto VARCHAR(80),
    estado VARCHAR(20) DEFAULT 'Pagado',
    observacion VARCHAR(250),
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS auditoria_movimiento (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT REFERENCES usuario(id) ON DELETE SET NULL,
    accion VARCHAR(100) NOT NULL,
    detalles TEXT,
    ip_origen INET,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permiso_usuario (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    permiso VARCHAR(50) NOT NULL,
    otorgado BOOLEAN NOT NULL DEFAULT FALSE,
    otorgado_por BIGINT REFERENCES usuario(id) ON DELETE SET NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

CREATE TABLE IF NOT EXISTS responsable (
    id BIGSERIAL PRIMARY KEY,
    dni VARCHAR(8) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100),
    direccion TEXT,
    celular VARCHAR(20),
    correo VARCHAR(150),
    vinculo VARCHAR(60),
    celular_actual VARCHAR(20),
    celular_padre VARCHAR(20),
    celular_madre VARCHAR(20),
    numero_tarjeta VARCHAR(30) UNIQUE,
    nro_tarjeta_apafa VARCHAR(30) UNIQUE,
    cuota_apafa NUMERIC(10, 2) DEFAULT 0 CHECK (cuota_apafa >= 0),
    nombre_padre_adicional VARCHAR(150),
    creado_en TIMESTAMP,
    actualizado_en TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE pago ADD COLUMN IF NOT EXISTS responsable_id BIGINT REFERENCES responsable(id) ON DELETE SET NULL;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS concepto_id BIGINT REFERENCES concepto_pago(id) ON DELETE SET NULL;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS monto_total NUMERIC(10, 2);
ALTER TABLE pago ADD COLUMN IF NOT EXISTS monto_pagado NUMERIC(10, 2) NOT NULL DEFAULT 0;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS saldo_restante NUMERIC(10, 2) NOT NULL DEFAULT 0;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS abono_hoy NUMERIC(10, 2) NOT NULL DEFAULT 0;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS entregado_por VARCHAR(150);
ALTER TABLE pago ADD COLUMN IF NOT EXISTS matricula_id BIGINT REFERENCES matricula(id) ON DELETE RESTRICT;
ALTER TABLE concepto_pago ADD COLUMN IF NOT EXISTS monto_total NUMERIC(10, 2);
ALTER TABLE concepto_pago ADD COLUMN IF NOT EXISTS monto_default NUMERIC(10, 2);
ALTER TABLE matricula ADD COLUMN IF NOT EXISTS apoderado_principal_id BIGINT;
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'matricula_apoderado_principal_id_fkey') THEN
        ALTER TABLE matricula ADD CONSTRAINT matricula_apoderado_principal_id_fkey
            FOREIGN KEY (apoderado_principal_id) REFERENCES responsable(id) ON DELETE SET NULL;
    END IF;
END $$;
UPDATE pago SET monto_total = monto, monto_pagado = monto, saldo_restante = 0 WHERE monto_total IS NULL AND monto_pagado = 0;
UPDATE pago SET abono_hoy = monto WHERE abono_hoy = 0 AND monto > 0;

ALTER TABLE responsable ADD COLUMN IF NOT EXISTS vinculo VARCHAR(60);
ALTER TABLE responsable ADD COLUMN IF NOT EXISTS celular_actual VARCHAR(20);
ALTER TABLE responsable ADD COLUMN IF NOT EXISTS celular_padre VARCHAR(20);
ALTER TABLE responsable ADD COLUMN IF NOT EXISTS celular_madre VARCHAR(20);
ALTER TABLE responsable ADD COLUMN IF NOT EXISTS numero_tarjeta VARCHAR(30);
ALTER TABLE responsable ADD COLUMN IF NOT EXISTS nro_tarjeta_apafa VARCHAR(30);
ALTER TABLE responsable ADD COLUMN IF NOT EXISTS cuota_apafa NUMERIC(10, 2) DEFAULT 0;
ALTER TABLE responsable ADD COLUMN IF NOT EXISTS nombre_padre_adicional VARCHAR(150);
CREATE UNIQUE INDEX IF NOT EXISTS responsable_numero_tarjeta_unique ON responsable (numero_tarjeta) WHERE numero_tarjeta IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS responsable_nro_tarjeta_apafa_unique ON responsable (nro_tarjeta_apafa) WHERE nro_tarjeta_apafa IS NOT NULL;

CREATE TABLE IF NOT EXISTS cuenta_pago (
    id BIGSERIAL PRIMARY KEY,
    matricula_id BIGINT NOT NULL REFERENCES matricula(id) ON DELETE CASCADE,
    responsable_id BIGINT REFERENCES responsable(id) ON DELETE SET NULL,
    concepto_id BIGINT NOT NULL REFERENCES concepto_pago(id) ON DELETE RESTRICT,
    monto_total NUMERIC(10, 2) NOT NULL CHECK (monto_total > 0),
    monto_pagado NUMERIC(10, 2) NOT NULL DEFAULT 0 CHECK (monto_pagado >= 0),
    saldo_restante NUMERIC(10, 2) NOT NULL CHECK (saldo_restante >= 0),
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE cuenta_pago ADD COLUMN IF NOT EXISTS matricula_id BIGINT REFERENCES matricula(id) ON DELETE CASCADE;
ALTER TABLE cuenta_pago ALTER COLUMN responsable_id DROP NOT NULL;
ALTER TABLE cuenta_pago DROP CONSTRAINT IF EXISTS cuenta_pago_responsable_id_concepto_id_key;
CREATE UNIQUE INDEX IF NOT EXISTS cuenta_pago_matricula_concepto_unique
    ON cuenta_pago (matricula_id, concepto_id) WHERE matricula_id IS NOT NULL;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS cuenta_pago_id BIGINT REFERENCES cuenta_pago(id) ON DELETE SET NULL;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'estudiante' AND column_name = 'padre_id') THEN
        ALTER TABLE estudiante DROP CONSTRAINT IF EXISTS estudiantes_padre_id_fkey;
        ALTER TABLE estudiante DROP COLUMN padre_id;
    END IF;
    IF to_regclass('public.apoderado') IS NOT NULL THEN
        DROP TABLE apoderado;
    END IF;
    IF to_regclass('public.padre_apoderado') IS NOT NULL THEN
        DROP TABLE padre_apoderado;
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS estudiante_responsable (
    id BIGSERIAL PRIMARY KEY,
    estudiante_id BIGINT NOT NULL REFERENCES estudiante(id) ON DELETE CASCADE,
    responsable_id BIGINT NOT NULL REFERENCES responsable(id) ON DELETE CASCADE,
    parentesco VARCHAR(60) NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('padre', 'madre', 'otro', 'emergencia')),
    es_apoderado BOOLEAN NOT NULL DEFAULT FALSE,
    creado_en TIMESTAMP,
    actualizado_en TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (estudiante_id, responsable_id)
);

DO $$
BEGIN
    ALTER TABLE estudiante_responsable DROP CONSTRAINT IF EXISTS estudiante_responsable_tipo_check;
    ALTER TABLE estudiante_responsable
        ADD CONSTRAINT estudiante_responsable_tipo_check
        CHECK (tipo IN ('padre', 'madre', 'otro', 'emergencia'));
END $$;

CREATE INDEX IF NOT EXISTS estudiante_responsable_estudiante_tipo_idx
    ON estudiante_responsable (estudiante_id, tipo);
CREATE UNIQUE INDEX IF NOT EXISTS estudiante_unico_padre_idx
    ON estudiante_responsable (estudiante_id) WHERE tipo = 'padre';
CREATE UNIQUE INDEX IF NOT EXISTS estudiante_unica_madre_idx
    ON estudiante_responsable (estudiante_id) WHERE tipo = 'madre';
CREATE UNIQUE INDEX IF NOT EXISTS estudiante_unico_apoderado_idx
    ON estudiante_responsable (estudiante_id) WHERE es_apoderado = TRUE;

UPDATE matricula m
SET apoderado_principal_id = (
    SELECT ar.responsable_id
    FROM estudiante_responsable ar
    WHERE ar.estudiante_id = m.estudiante_id
    ORDER BY ar.es_apoderado DESC,
             CASE ar.tipo WHEN 'padre' THEN 1 WHEN 'madre' THEN 2 ELSE 3 END,
             ar.responsable_id
    LIMIT 1
)
WHERE m.apoderado_principal_id IS NULL AND m.estudiante_id IS NOT NULL;

INSERT INTO nivel (nombre) VALUES ('Inicial'), ('Primaria'), ('Secundaria')
ON CONFLICT (nombre) DO NOTHING;
INSERT INTO grado (nivel_id, numero, nombre)
SELECT n.id, g.numero, g.nombre
FROM nivel n
JOIN (VALUES
    ('Inicial', 3, '3 años'), ('Inicial', 4, '4 años'), ('Inicial', 5, '5 años'),
    ('Primaria', 1, '1ro'), ('Primaria', 2, '2do'), ('Primaria', 3, '3ro'),
    ('Primaria', 4, '4to'), ('Primaria', 5, '5to'), ('Primaria', 6, '6to'),
    ('Secundaria', 1, '1ro'), ('Secundaria', 2, '2do'), ('Secundaria', 3, '3ro'),
    ('Secundaria', 4, '4to'), ('Secundaria', 5, '5to')
) AS g(nivel, numero, nombre) ON g.nivel = n.nombre
ON CONFLICT (nivel_id, numero) DO NOTHING;
INSERT INTO seccion (nombre) VALUES ('A'), ('B'), ('C')
ON CONFLICT (nombre) DO NOTHING;
INSERT INTO grado_seccion (grado_id, seccion_id)
SELECT g.id, s.id FROM grado g CROSS JOIN seccion s
ON CONFLICT (grado_id, seccion_id) DO NOTHING;
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'matricula' AND column_name = 'grado_id') THEN
        EXECUTE 'UPDATE matricula m SET grado_seccion_id = gs.id FROM grado_seccion gs WHERE m.grado_seccion_id IS NULL AND gs.grado_id = m.grado_id AND gs.seccion_id = m.seccion_id';
        ALTER TABLE matricula DROP CONSTRAINT IF EXISTS matricula_grado_id_fkey;
        ALTER TABLE matricula DROP CONSTRAINT IF EXISTS matricula_seccion_id_fkey;
        DROP INDEX IF EXISTS matricula_grado_id_index;
        DROP INDEX IF EXISTS matricula_seccion_id_index;
        ALTER TABLE matricula DROP COLUMN IF EXISTS grado_id, DROP COLUMN IF EXISTS seccion_id, DROP COLUMN IF EXISTS grado, DROP COLUMN IF EXISTS seccion;
    END IF;
END $$;
INSERT INTO anio (anio)
SELECT year_value
FROM generate_series(2020, EXTRACT(YEAR FROM CURRENT_DATE)::integer + 1) AS year_value
ON CONFLICT (anio) DO NOTHING;
INSERT INTO concepto_pago (descripcion, monto_total, monto_default, activo) VALUES
    ('Cuota APAFA', NULL, NULL, TRUE), ('Internet', 5.00, 5.00, TRUE), ('Multa', NULL, NULL, TRUE),
    ('Apoyo a la gestión', 30.00, 30.00, TRUE), ('Otro', NULL, NULL, TRUE),
    ('Nuevo Estudiante', 150.00, 150.00, TRUE), ('Extraordinaria', 50.00, 50.00, TRUE)
ON CONFLICT (descripcion) DO UPDATE
SET monto_total = EXCLUDED.monto_total, monto_default = EXCLUDED.monto_default, activo = TRUE;
UPDATE concepto_pago SET activo = FALSE WHERE LOWER(descripcion) = 'qaliwarma';

