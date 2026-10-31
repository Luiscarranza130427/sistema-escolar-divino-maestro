<?php
declare(strict_types=1);

namespace App\Modulos\Academico;

use App\Core\Database;
use App\Soporte\Booleano;

/** Cursos, periodos académicos y calificaciones. Cursos y periodos se desactivan, no se borran. */
final class AcademicoRepository
{
    public function __construct(private Database $db)
    {
    }

    public function cursos(): array
    {
        return $this->db->filas('SELECT id, nombre, grado FROM curso WHERE activo = TRUE ORDER BY grado, nombre');
    }

    public function crearCurso(array $datos): int
    {
        return $this->db->insertar('INSERT INTO curso (nombre, grado, activo) VALUES (:nombre, :grado, TRUE) RETURNING id', $datos);
    }

    public function actualizarCurso(int $id, array $datos): bool
    {
        return $this->db->afectar(
            'UPDATE curso SET nombre = :nombre, grado = :grado WHERE id = :id AND activo = TRUE',
            $datos + ['id' => $id]
        ) === 1;
    }

    public function desactivarCurso(int $id): bool
    {
        return $this->db->afectar('UPDATE curso SET activo = FALSE WHERE id = :id AND activo = TRUE', ['id' => $id]) === 1;
    }

    public function periodos(): array
    {
        return $this->db->filas(
            'SELECT id, nombre, fecha_inicio, fecha_fin FROM periodo_academico WHERE activo = TRUE ORDER BY fecha_inicio DESC, nombre'
        );
    }

    public function crearPeriodo(array $datos): int
    {
        return $this->db->insertar(
            'INSERT INTO periodo_academico (nombre, fecha_inicio, fecha_fin, activo)
             VALUES (:nombre, :fecha_inicio, :fecha_fin, TRUE)
             RETURNING id',
            $datos
        );
    }

    public function actualizarPeriodo(int $id, array $datos): bool
    {
        return $this->db->afectar(
            'UPDATE periodo_academico SET nombre = :nombre, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin
             WHERE id = :id AND activo = TRUE',
            $datos + ['id' => $id]
        ) === 1;
    }

    public function desactivarPeriodo(int $id): bool
    {
        return $this->db->afectar('UPDATE periodo_academico SET activo = FALSE WHERE id = :id AND activo = TRUE', ['id' => $id]) === 1;
    }

    public function cursoYPeriodoActivos(int $cursoId, int $periodoId): bool
    {
        $estado = $this->db->fila(
            'SELECT (SELECT activo FROM curso WHERE id = :curso_id) AS curso_activo,
                    (SELECT activo FROM periodo_academico WHERE id = :periodo_id) AS periodo_activo',
            ['curso_id' => $cursoId, 'periodo_id' => $periodoId]
        );
        return Booleano::desdeBd($estado['curso_activo'] ?? null) && Booleano::desdeBd($estado['periodo_activo'] ?? null);
    }

    /** Estudiantes con matrícula activa y su nota del curso/periodo. */
    public function calificaciones(int $cursoId, int $periodoId, ?int $gradoSeccionId): array
    {
        $parametros = ['curso_id' => $cursoId, 'periodo_id' => $periodoId];
        $filtro = '';
        if ($gradoSeccionId !== null) {
            $filtro = ' AND m.grado_seccion_id = :grado_seccion_id';
            $parametros['grado_seccion_id'] = $gradoSeccionId;
        }
        return $this->db->filas(
            "SELECT m.id AS matricula_id, e.dni,
                    CONCAT_WS(' ', e.apellido_paterno, e.apellido_materno, e.nombres) AS estudiante, c.nota
             FROM matricula m
             JOIN estudiante e ON e.id = m.estudiante_id
             LEFT JOIN calificacion c ON c.matricula_id = m.id AND c.curso_id = :curso_id AND c.periodo_id = :periodo_id
             WHERE m.estado = 'Activa'" . $filtro . '
             ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres',
            $parametros
        );
    }

    /** @param list<int> $ids @return list<int> los que tienen matrícula activa */
    public function matriculasActivas(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marcadores = [];
        $parametros = [];
        foreach (array_values($ids) as $i => $id) {
            $marcadores[] = ":m{$i}";
            $parametros["m{$i}"] = $id;
        }
        return array_map('intval', $this->db->columna(
            "SELECT id FROM matricula WHERE estado = 'Activa' AND id IN (" . implode(', ', $marcadores) . ')',
            $parametros
        ));
    }

    public function guardarNota(int $matriculaId, int $cursoId, int $periodoId, string $nota): void
    {
        $this->db->ejecutar(
            'INSERT INTO calificacion (matricula_id, curso_id, periodo_id, nota)
             VALUES (:matricula_id, :curso_id, :periodo_id, :nota)
             ON CONFLICT (matricula_id, curso_id, periodo_id)
             DO UPDATE SET nota = EXCLUDED.nota, actualizado_en = CURRENT_TIMESTAMP',
            ['matricula_id' => $matriculaId, 'curso_id' => $cursoId, 'periodo_id' => $periodoId, 'nota' => $nota]
        );
    }
}
