<?php
declare(strict_types=1);

namespace App\Modulos\Academico;

use App\Core\Database;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;

final class AcademicoService
{
    public function __construct(private Database $db, private AcademicoRepository $academico, private Auditor $auditor)
    {
    }

    public function actualizarCurso(int $id, array $datos): void
    {
        if (!$this->academico->actualizarCurso($id, $datos)) {
            throw HttpException::noEncontrado('No se encontró el curso activo.');
        }
    }

    public function desactivarCurso(int $id): void
    {
        if (!$this->academico->desactivarCurso($id)) {
            throw HttpException::noEncontrado('El curso no existe o ya no está activo.');
        }
    }

    public function actualizarPeriodo(int $id, array $datos): void
    {
        if (!$this->academico->actualizarPeriodo($id, $datos)) {
            throw HttpException::noEncontrado('No se encontró el periodo académico activo.');
        }
    }

    public function desactivarPeriodo(int $id): void
    {
        if (!$this->academico->desactivarPeriodo($id)) {
            throw HttpException::noEncontrado('El periodo no existe o ya no está activo.');
        }
    }

    public function calificaciones(int $cursoId, int $periodoId, ?int $gradoSeccionId): array
    {
        $this->exigirCursoYPeriodo($cursoId, $periodoId);
        return $this->academico->calificaciones($cursoId, $periodoId, $gradoSeccionId);
    }

    /**
     * @param array<int, string> $notas matricula_id => nota ya validada (0 a 20, hasta 2 decimales)
     * @return int notas guardadas
     */
    public function guardarCalificaciones(int $cursoId, int $periodoId, array $notas): int
    {
        $this->exigirCursoYPeriodo($cursoId, $periodoId);
        $inactivas = array_diff(array_keys($notas), $this->academico->matriculasActivas(array_keys($notas)));
        if ($inactivas !== []) {
            throw HttpException::regla('Matrículas no activas o inexistentes: ' . implode(', ', $inactivas) . '.', 'notas');
        }

        $this->db->transaccion(function () use ($cursoId, $periodoId, $notas): void {
            foreach ($notas as $matriculaId => $nota) {
                $this->academico->guardarNota($matriculaId, $cursoId, $periodoId, $nota);
            }
            $this->auditor->registrar('registrar_calificaciones', 'Registró ' . count($notas) . " notas del curso ID {$cursoId}, periodo ID {$periodoId}.");
        });
        return count($notas);
    }

    private function exigirCursoYPeriodo(int $cursoId, int $periodoId): void
    {
        if (!$this->academico->cursoYPeriodoActivos($cursoId, $periodoId)) {
            throw HttpException::regla('Selecciona un curso y un periodo académico activos.', 'curso_id');
        }
    }
}
