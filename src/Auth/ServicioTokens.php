<?php
declare(strict_types=1);

namespace App\Auth;

use App\Core\Config;
use App\Core\Database;

/**
 * Tokens de acceso (Bearer). En la base solo se guarda el hash SHA-256 del token,
 * así una filtración de la tabla no permite suplantar a nadie.
 * La vigencia se renueva con el uso (APP_TOKEN_HORAS, 12 h por defecto).
 */
final class ServicioTokens
{
    public function __construct(private Database $db, private Config $config)
    {
    }

    /** @return array{token: string, expira_en: string} */
    public function emitir(int $usuarioId, string $dispositivo): array
    {
        $this->db->afectar('DELETE FROM api_token WHERE expira_en < NOW()');

        $token = bin2hex(random_bytes(32));
        $expiraEn = $this->db->valor(
            "INSERT INTO api_token (usuario_id, token_hash, dispositivo, expira_en)
             VALUES (:usuario_id, :hash, :dispositivo, NOW() + make_interval(hours => :horas))
             RETURNING to_char(expira_en, 'YYYY-MM-DD\"T\"HH24:MI:SS')",
            [
                'usuario_id' => $usuarioId,
                'hash' => hash('sha256', $token),
                'dispositivo' => mb_substr($dispositivo, 0, 150),
                'horas' => $this->horas(),
            ]
        );
        return ['token' => $token, 'expira_en' => (string) $expiraEn];
    }

    public function resolver(string $token): ?UsuarioAutenticado
    {
        $fila = $this->db->fila(
            "SELECT t.id AS token_id, u.id, u.nombres, u.correo, LOWER(u.rol) AS rol, u.foto,
                    t.ultimo_uso_en < NOW() - INTERVAL '5 minutes' AS renovar
             FROM api_token t
             JOIN usuario u ON u.id = t.usuario_id
             WHERE t.token_hash = :hash AND t.expira_en > NOW() AND u.estado = TRUE",
            ['hash' => hash('sha256', $token)]
        );
        if ($fila === null) {
            return null;
        }

        if ($fila['renovar'] === true || $fila['renovar'] === 't' || $fila['renovar'] === 1) {
            $this->db->afectar(
                'UPDATE api_token SET ultimo_uso_en = NOW(), expira_en = NOW() + make_interval(hours => :horas) WHERE id = :id',
                ['horas' => $this->horas(), 'id' => (int) $fila['token_id']]
            );
        }

        return new UsuarioAutenticado(
            (int) $fila['id'],
            (string) $fila['nombres'],
            (string) $fila['correo'],
            (string) $fila['rol'],
            $this->permisosDe((int) $fila['id'], (string) $fila['rol']),
            $fila['foto'] === null ? null : (string) $fila['foto']
        );
    }

    /** @return list<string> */
    public function permisosDe(int $usuarioId, string $rol): array
    {
        if (in_array($rol, Rol::ADMINISTRADORES, true)) {
            return Permiso::TODOS;
        }
        return array_map('strval', $this->db->columna(
            'SELECT p.codigo
             FROM usuario_permisos up
             JOIN permisos p ON p.id = up.permiso_id
             WHERE up.usuario_id = :usuario_id AND p.activo = TRUE
             ORDER BY p.codigo',
            ['usuario_id' => $usuarioId]
        ));
    }

    public function revocar(string $token): void
    {
        $this->db->afectar('DELETE FROM api_token WHERE token_hash = :hash', ['hash' => hash('sha256', $token)]);
    }

    /** Cierra todas las sesiones de un usuario, salvo la del token indicado. */
    public function revocarTodos(int $usuarioId, ?string $exceptoToken = null): void
    {
        $this->db->afectar(
            'DELETE FROM api_token WHERE usuario_id = :usuario_id AND token_hash IS DISTINCT FROM :excepto',
            ['usuario_id' => $usuarioId, 'excepto' => $exceptoToken === null ? null : hash('sha256', $exceptoToken)]
        );
    }

    private function horas(): int
    {
        return max(1, $this->config->entero('APP_TOKEN_HORAS', 12));
    }
}
