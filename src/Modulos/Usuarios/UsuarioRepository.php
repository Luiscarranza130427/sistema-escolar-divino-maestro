<?php
declare(strict_types=1);

namespace App\Modulos\Usuarios;

use App\Auth\Rol;
use App\Core\Database;
use App\Soporte\Booleano;

final class UsuarioRepository
{
    public function __construct(private Database $db)
    {
    }

    /** @return array<string, mixed>|null incluye password_hash */
    public function buscarActivoPorCorreo(string $correo): ?array
    {
        return $this->db->fila(
            'SELECT id, nombres, correo, LOWER(rol) AS rol, password_hash, foto
             FROM usuario WHERE LOWER(correo) = LOWER(:correo) AND estado = TRUE',
            ['correo' => $correo]
        );
    }

    public function hashDe(int $id): ?string
    {
        $hash = $this->db->valor('SELECT password_hash FROM usuario WHERE id = :id', ['id' => $id]);
        return $hash === null ? null : (string) $hash;
    }

    public function actualizarHash(int $id, string $hash): void
    {
        $this->db->afectar(
            'UPDATE usuario SET password_hash = :hash, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id',
            ['hash' => $hash, 'id' => $id]
        );
    }

    public function correoRegistrado(string $correo): bool
    {
        return $this->db->valor('SELECT 1 FROM usuario WHERE LOWER(correo) = LOWER(:correo)', ['correo' => $correo]) !== null;
    }

    public function correoDeOtro(string $correo, int $id): bool
    {
        return $this->db->valor('SELECT 1 FROM usuario WHERE LOWER(correo) = LOWER(:correo) AND id <> :id', ['correo' => $correo, 'id' => $id]) !== null;
    }

    public function actualizarPerfil(int $id, string $nombres, string $correo): void
    {
        $this->db->afectar(
            'UPDATE usuario SET nombres = :nombres, correo = :correo, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id',
            ['nombres' => $nombres, 'correo' => $correo, 'id' => $id]
        );
    }

    /** @return array{nombres: string, correo: string, foto: ?string}|null */
    public function perfilDe(int $id): ?array
    {
        return $this->db->fila('SELECT nombres, correo, foto FROM usuario WHERE id = :id', ['id' => $id]);
    }

    public function fotoDe(int $id): ?string
    {
        $foto = $this->db->valor('SELECT foto FROM usuario WHERE id = :id', ['id' => $id]);
        return $foto === null ? null : (string) $foto;
    }

    public function actualizarFoto(int $id, ?string $foto): void
    {
        $this->db->afectar('UPDATE usuario SET foto = :foto, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id', ['foto' => $foto, 'id' => $id]);
    }

    public function crear(string $nombres, string $correo, string $hash, string $rol): int
    {
        return $this->db->insertar(
            'INSERT INTO usuario (nombres, correo, password_hash, rol) VALUES (:nombres, :correo, :hash, :rol) RETURNING id',
            ['nombres' => $nombres, 'correo' => $correo, 'hash' => $hash, 'rol' => $rol]
        );
    }

    /** @return list<array{id: int, nombres: string}> */
    public function listarParaFiltro(): array
    {
        return $this->db->filas('SELECT id, nombres, LOWER(rol) AS rol FROM usuario ORDER BY nombres');
    }

    /** @return array<string, mixed>|null */
    public function buscarAsistente(int $id): ?array
    {
        return $this->db->fila(
            'SELECT id, nombres, correo FROM usuario WHERE id = :id AND rol = :rol',
            ['id' => $id, 'rol' => Rol::ASISTENTE]
        );
    }

    /** Asistentes con sus permisos asignados. */
    public function listarAsistentes(): array
    {
        $asistentes = $this->db->filas(
            'SELECT id, nombres, correo, estado FROM usuario WHERE rol = :rol ORDER BY nombres',
            ['rol' => Rol::ASISTENTE]
        );
        $porUsuario = [];
        foreach ($this->db->filas(
            'SELECT up.usuario_id, p.id, p.codigo, p.nombre
             FROM usuario_permisos up JOIN permisos p ON p.id = up.permiso_id
             ORDER BY p.nombre'
        ) as $asignacion) {
            $usuarioId = (int) $asignacion['usuario_id'];
            unset($asignacion['usuario_id']);
            $porUsuario[$usuarioId][] = $asignacion;
        }

        return array_map(static function (array $asistente) use ($porUsuario): array {
            $asistente['estado'] = Booleano::desdeBd($asistente['estado']);
            $asistente['permisos'] = $porUsuario[(int) $asistente['id']] ?? [];
            return $asistente;
        }, $asistentes);
    }

    public function permisosActivos(): array
    {
        return $this->db->filas('SELECT id, codigo, nombre, descripcion FROM permisos WHERE activo = TRUE ORDER BY nombre');
    }

    /** @param list<int> $permisos */
    public function sincronizarPermisos(int $usuarioId, array $permisos, ?int $otorgadoPor): void
    {
        $this->db->afectar('DELETE FROM usuario_permisos WHERE usuario_id = :usuario_id', ['usuario_id' => $usuarioId]);
        foreach ($permisos as $permisoId) {
            $this->db->afectar(
                'INSERT INTO usuario_permisos (usuario_id, permiso_id, otorgado_por) VALUES (:usuario_id, :permiso_id, :otorgado_por)',
                ['usuario_id' => $usuarioId, 'permiso_id' => $permisoId, 'otorgado_por' => $otorgadoPor]
            );
        }
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->db->afectar(
            'UPDATE usuario SET estado = :estado, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id',
            ['estado' => $activo, 'id' => $id]
        );
    }
}
