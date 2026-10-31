# Instrucciones para agentes de IA

Sistema escolar de la I.E. Divino Maestro: API REST en PHP 8.2+ (sin framework, por capas) + PostgreSQL, y SPA en React 19 + Vite + TypeScript dentro de `frontend/`. La guía completa para personas está en `README.md`; el detalle técnico y los endpoints, en `ARQUITECTURA.md`.

## Levantar el proyecto

1. Requisitos: `php -v` ≥ 8.2 con `pdo_pgsql`, `mbstring` y `gd` (`php -m`); Composer 2; PostgreSQL ≥ 15; Node ≥ 20.
2. `composer install` en la raíz y `npm install` en `frontend/`.
3. Si falta `.env`, cópialo de `.env.example` y pide al usuario los datos de PostgreSQL. No inventes credenciales ni las escribas en archivos versionados.
4. Crea la base indicada en `DB_NAME` y ejecuta `php bin/migrar.php` (idempotente). Alternativa: `psql -d <DB_NAME> -f database/sistema_escolar.sql`.
5. Usuario inicial: `php bin/crear-usuario.php correo@colegio.pe "Nombres" director` (imprime la contraseña generada).
6. Servidores (procesos que no terminan, ejecútalos en segundo plano):
   - API: `composer servir` → http://127.0.0.1:8000
   - Frontend: `npm run dev` en `frontend/` → http://localhost:5173 (Vite reenvía `/api` y `/uploads` a la API).
7. Verificación:
   - `curl -i http://127.0.0.1:8000/api/v1/auth/yo` responde 401.
   - `composer test` pasa.
   - `npm run typecheck` en `frontend/` no muestra errores.

## Reglas

- No subir ni mostrar: `.env`, `doc-exel/` (datos personales de estudiantes), `database/respaldo/`, `public/uploads/`.
- Cambios de base de datos: nuevo archivo `database/migraciones/NNN_descripcion.sql` idempotente (`IF NOT EXISTS`). Nunca editar una migración ya aplicada.
- API: rutas en `config/rutas.php`; cada módulo en `src/Modulos/<Modulo>/` con Controller → Service → Repository. Respuestas `{ "data": ... }` o `{ "error": { "mensaje", "campos" } }`.
- Frontend: textos en español; componentes base en `frontend/src/components/ui/`; confirmaciones y avisos solo con `frontend/src/lib/alertas.ts` (SweetAlert2); datos remotos con TanStack Query (`useQuery` / `useAccion`).
- Antes de terminar un cambio: `composer test` y `npm run typecheck` (y `npm run build` si tocaste el frontend).
