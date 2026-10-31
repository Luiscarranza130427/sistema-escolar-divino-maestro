-- Tokens de acceso de la API (autenticación Bearer). Solo se guarda el hash SHA-256 del token.
CREATE TABLE IF NOT EXISTS api_token (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    dispositivo VARCHAR(150),
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en TIMESTAMP NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_api_token_usuario ON api_token (usuario_id);
CREATE INDEX IF NOT EXISTS idx_api_token_expira ON api_token (expira_en);
