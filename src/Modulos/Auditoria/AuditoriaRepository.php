<?php
declare(strict_types=1);

namespace App\Modulos\Auditoria;

use App\Core\Database;
use App\Core\Pagina;
use App\Core\Paginacion;

final class AuditoriaRepository
{
    /** Patrones de acción por tipo de operación. */
    private const TIPOS = [
        'INSERT' => "(LOWER(a.accion) LIKE 'crear%' OR LOWER(a.accion) LIKE 'registr%' OR LOWER(a.accion) LIKE '%creado')",
        'UPDATE' => "(LOWER(a.accion) LIKE 'editar%' OR LOWER(a.accion) LIKE 'actualizar%' OR LOWER(a.accion) LIKE 'cambio%' OR LOWER(a.accion) LIKE 'acceso%')",
        'DELETE' => "LOWER(a.accion) LIKE 'eliminar%'",
    ];

    public function __construct(private Database $db)
    {
    }

    /** @param array{fecha_inicio: ?string, fecha_fin: ?string, usuario_id: ?int, tipo_accion: ?string} $filtros */
    public function listar(array $filtros, Paginacion $paginacion): Pagina
    {
        $condiciones = [];
        $parametros = [];
        if ($filtros['fecha_inicio'] !== null) {
            $condiciones[] = 'a.creado_en >= CAST(:fecha_inicio AS date)';
            $parametros['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if ($filtros['fecha_fin'] !== null) {
            $condiciones[] = "a.creado_en < CAST(:fecha_fin AS date) + INTERVAL '1 day'";
            $parametros['fecha_fin'] = $filtros['fecha_fin'];
        }
        if ($filtros['usuario_id'] !== null) {
            $condiciones[] = 'a.usuario_id = :usuario_id';
            $parametros['usuario_id'] = $filtros['usuario_id'];
        }
        if ($filtros['tipo_accion'] !== null) {
            $condiciones[] = self::TIPOS[$filtros['tipo_accion']];
        }

        return $this->db->paginar(
            'SELECT a.id, a.creado_en, a.accion, a.detalles, a.ip_origen, a.usuario_id, u.nombres AS usuario
             FROM auditoria_movimiento a
             LEFT JOIN usuario u ON u.id = a.usuario_id '
            . ($condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '')
            . ' ORDER BY a.id DESC',
            $parametros,
            $paginacion
        );
    }

    /** Movimientos por usuario sin contar inicios de sesión. */
    public function resumenPorUsuario(): array
    {
        return array_map(static fn (array $f): array => ['total' => (int) $f['total']] + $f, $this->db->filas(
            "SELECT a.usuario_id, COALESCE(u.nombres, 'Usuario eliminado') AS usuario, COUNT(*) AS total
             FROM auditoria_movimiento a
             LEFT JOIN usuario u ON u.id = a.usuario_id
             WHERE LOWER(a.accion) <> 'inicio_sesion'
             GROUP BY a.usuario_id, u.nombres
             ORDER BY total DESC, usuario"
        ));
    }

    public function eliminar(int $id): bool
    {
        return $this->db->afectar('DELETE FROM auditoria_movimiento WHERE id = :id', ['id' => $id]) === 1;
    }
}
