<?php
declare(strict_types=1);

namespace App\Modulos\Catalogos;

use App\Core\Database;

/** Años, niveles, grados y secciones habilitados. */
final class CatalogoRepository
{
    public function __construct(private Database $db)
    {
    }

    public function anios(): array
    {
        return $this->db->filas('SELECT id, anio FROM anio WHERE activo = TRUE ORDER BY anio DESC');
    }

    public function anioActivo(int $anio): ?int
    {
        $id = $this->db->valor('SELECT id FROM anio WHERE anio = :anio AND activo = TRUE', ['anio' => $anio]);
        return $id === null ? null : (int) $id;
    }

    public function niveles(): array
    {
        return $this->db->filas('SELECT id, nombre FROM nivel ORDER BY id');
    }

    public function grados(?int $nivelId): array
    {
        return $this->db->filas(
            'SELECT g.id, g.nombre, g.numero, g.nivel_id, n.nombre AS nivel
             FROM grado g JOIN nivel n ON n.id = g.nivel_id
             WHERE g.activo = TRUE AND (CAST(:nivel_id AS bigint) IS NULL OR g.nivel_id = :nivel_id_filtro)
             ORDER BY n.id, g.numero',
            ['nivel_id' => $nivelId, 'nivel_id_filtro' => $nivelId]
        );
    }

    public function secciones(?int $gradoId): array
    {
        return $this->db->filas(
            'SELECT gs.id AS grado_seccion_id, gs.grado_id, g.nombre AS grado, s.id AS seccion_id, s.nombre AS seccion
             FROM grado_seccion gs
             JOIN grado g ON g.id = gs.grado_id
             JOIN seccion s ON s.id = gs.seccion_id
             WHERE gs.activo = TRUE AND s.activo = TRUE
               AND (CAST(:grado_id AS bigint) IS NULL OR gs.grado_id = :grado_id_filtro)
             ORDER BY gs.grado_id, s.nombre',
            ['grado_id' => $gradoId, 'grado_id_filtro' => $gradoId]
        );
    }

    /** Grado y sección habilitados, con el nivel al que pertenecen. */
    public function gradoSeccionActiva(int $id): ?array
    {
        return $this->db->fila(
            'SELECT gs.id, g.nivel_id
             FROM grado_seccion gs
             JOIN grado g ON g.id = gs.grado_id
             JOIN seccion s ON s.id = gs.seccion_id
             WHERE gs.id = :id AND gs.activo = TRUE AND g.activo = TRUE AND s.activo = TRUE',
            ['id' => $id]
        );
    }
}
