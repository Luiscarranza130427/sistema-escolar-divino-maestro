<?php
declare(strict_types=1);

namespace App\Modulos\Auditoria;

use App\Auth\Contexto;
use App\Core\Database;

/** Registra en auditoria_movimiento las acciones del usuario autenticado. */
final class Auditor
{
    public function __construct(private Database $db, private Contexto $contexto)
    {
    }

    public function registrar(string $accion, string $detalles, ?int $usuarioId = null): void
    {
        $usuarioId ??= $this->contexto->usuarioId();
        if ($usuarioId === null) {
            return;
        }
        $this->db->afectar(
            'INSERT INTO auditoria_movimiento (usuario_id, accion, detalles, ip_origen)
             VALUES (:usuario_id, :accion, :detalles, :ip_origen)',
            ['usuario_id' => $usuarioId, 'accion' => $accion, 'detalles' => $detalles, 'ip_origen' => $this->contexto->ip]
        );
    }
}
