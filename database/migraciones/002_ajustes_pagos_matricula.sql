-- Cambios de estructura y datos que antes se ejecutaban en CADA carga de
-- pagos.php, matriculas.php y ver_padre.php. Ahora se aplican una sola vez
-- con: php database/migrar.php

CREATE TABLE IF NOT EXISTS pago (
    id SERIAL PRIMARY KEY,
    responsable VARCHAR(150) NOT NULL,
    concepto VARCHAR(80) NOT NULL,
    monto NUMERIC(10, 2) NOT NULL,
    fecha_pago DATE NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pagado',
    observacion VARCHAR(250),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
ALTER TABLE concepto_pago ADD COLUMN IF NOT EXISTS monto_total NUMERIC(10, 2);
ALTER TABLE concepto_pago ADD COLUMN IF NOT EXISTS monto_default NUMERIC(10, 2);
ALTER TABLE concepto_pago ADD COLUMN IF NOT EXISTS tipo_cobro VARCHAR(20) NOT NULL DEFAULT 'estudiante';
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'concepto_pago_tipo_cobro_check') THEN
        ALTER TABLE concepto_pago
            ADD CONSTRAINT concepto_pago_tipo_cobro_check
            CHECK (tipo_cobro IN ('estudiante', 'familia'));
    END IF;
END $$;
UPDATE concepto_pago SET monto_default = monto_total WHERE monto_default IS NULL AND monto_total IS NOT NULL;

ALTER TABLE pago ADD COLUMN IF NOT EXISTS responsable_id BIGINT REFERENCES responsable(id) ON DELETE SET NULL;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS concepto_id BIGINT REFERENCES concepto_pago(id) ON DELETE SET NULL;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS matricula_id BIGINT REFERENCES matricula(id) ON DELETE RESTRICT;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS monto_total NUMERIC(10, 2);
ALTER TABLE pago ADD COLUMN IF NOT EXISTS monto_pagado NUMERIC(10, 2) NOT NULL DEFAULT 0;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS saldo_restante NUMERIC(10, 2) NOT NULL DEFAULT 0;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS abono_hoy NUMERIC(10, 2) NOT NULL DEFAULT 0;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS entregado_por VARCHAR(150);
UPDATE pago SET monto_total = monto, monto_pagado = monto, saldo_restante = 0 WHERE monto_total IS NULL AND monto_pagado = 0;
UPDATE pago SET abono_hoy = monto WHERE abono_hoy = 0 AND monto > 0;

ALTER TABLE matricula ADD COLUMN IF NOT EXISTS apoderado_principal_id BIGINT;
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'matricula_apoderado_principal_id_fkey') THEN
        ALTER TABLE matricula ADD CONSTRAINT matricula_apoderado_principal_id_fkey
            FOREIGN KEY (apoderado_principal_id) REFERENCES responsable(id) ON DELETE SET NULL;
    END IF;
END $$;
UPDATE matricula m
SET apoderado_principal_id = (
    SELECT ar.responsable_id FROM estudiante_responsable ar
    WHERE ar.estudiante_id = m.estudiante_id
    ORDER BY ar.es_apoderado DESC,
             CASE ar.tipo WHEN 'padre' THEN 1 WHEN 'madre' THEN 2 ELSE 3 END,
             ar.responsable_id
    LIMIT 1
)
WHERE m.apoderado_principal_id IS NULL AND m.estudiante_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS cuenta_pago (
    id BIGSERIAL PRIMARY KEY,
    matricula_id BIGINT REFERENCES matricula(id) ON DELETE CASCADE,
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

-- Ajuste de datos puntual que pagos.php repetía en cada visita.
UPDATE concepto_pago SET activo = FALSE WHERE LOWER(descripcion) = 'qaliwarma';
