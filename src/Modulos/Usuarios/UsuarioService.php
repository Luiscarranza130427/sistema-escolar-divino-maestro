<?php
declare(strict_types=1);

namespace App\Modulos\Usuarios;

use App\Auth\Contexto;
use App\Auth\Rol;
use App\Auth\ServicioTokens;
use App\Core\Database;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;

/** Alta de asistentes, asignación de permisos y control de acceso (solo dirección). */
final class UsuarioService
{
    public function __construct(
        private Database $db,
        private UsuarioRepository $usuarios,
        private ServicioTokens $tokens,
        private Contexto $contexto,
        private Auditor $auditor
    ) {
    }

    /** @param array{nombres: string, correo: string, password: string, permisos: list<int>} $datos */
    public function crearAsistente(array $datos): int
    {
        $this->verificarPermisos($datos['permisos']);
        $id = $this->db->transaccion(function () use ($datos): int {
            if ($this->usuarios->correoRegistrado($datos['correo'])) {
                throw HttpException::conflicto('Ya existe una cuenta con ese correo.');
            }
            $id = $this->usuarios->crear($datos['nombres'], $datos['correo'], password_hash($datos['password'], PASSWORD_DEFAULT), Rol::ASISTENTE);
            $this->usuarios->sincronizarPermisos($id, $datos['permisos'], $this->contexto->usuarioId());
            return $id;
        });
        $this->auditor->registrar('asistente_creado', "Asistente creado: {$datos['nombres']} ({$datos['correo']}).");
        return $id;
    }

    /** @param list<int> $permisos */
    public function actualizarPermisos(int $id, array $permisos): void
    {
        $asistente = $this->asistente($id);
        $this->verificarPermisos($permisos);
        $this->db->transaccion(fn () => $this->usuarios->sincronizarPermisos($id, $permisos, $this->contexto->usuarioId()));
        $this->auditor->registrar('cambio_permisos', "Permisos actualizados para {$asistente['nombres']} ({$asistente['correo']}).");
    }

    /** Al revocar se cierran todas las sesiones abiertas del asistente. */
    public function cambiarAcceso(int $id, bool $activo): void
    {
        $asistente = $this->asistente($id);
        $this->db->transaccion(function () use ($id, $activo): void {
            $this->usuarios->cambiarEstado($id, $activo);
            if (!$activo) {
                $this->tokens->revocarTodos($id);
            }
        });
        $this->auditor->registrar(
            $activo ? 'acceso_restaurado' : 'acceso_revocado',
            ($activo ? 'Acceso restaurado' : 'Acceso revocado') . " para {$asistente['nombres']} ({$asistente['correo']})."
        );
    }

    /** @return array<string, mixed> */
    private function asistente(int $id): array
    {
        return $this->usuarios->buscarAsistente($id) ?? throw HttpException::noEncontrado('Asistente no encontrado.');
    }

    /** @param list<int> $permisos */
    private function verificarPermisos(array $permisos): void
    {
        $validos = array_map('intval', array_column($this->usuarios->permisosActivos(), 'id'));
        if (array_diff($permisos, $validos) !== []) {
            throw HttpException::regla('Selecciona permisos válidos.', 'permisos');
        }
    }
}
