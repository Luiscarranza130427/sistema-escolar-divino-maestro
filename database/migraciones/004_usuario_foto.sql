-- Foto de perfil del usuario: nombre del archivo guardado en public/uploads/perfiles/.
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS foto VARCHAR(100);
