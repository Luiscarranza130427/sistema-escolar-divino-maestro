<?php
declare(strict_types=1);

namespace App\Modulos\Matriculas;

use App\Core\Database;
use App\Core\Pagina;
use App\Core\Paginacion;
use App\Soporte\FiltroAcademico;

final class MatriculaRepository
{
    public function __construct(private Database $db)
    {
    }

    public function listar(string $busqueda, ?int $anio, FiltroAcademico $filtro, Paginacion $paginacion): Pagina
    {
        $parametros = [];
        $condiciones = $filtro->condiciones($parametros);
        if ($busqueda !== '') {
            $condiciones[] = "CONCAT_WS(' ', e.dni, e.nombres, e.apellido_paterno, e.apellido_materno) ILIKE :busqueda";
            $parametros['busqueda'] = '%' . $busqueda . '%';
        }
        if ($anio !== null) {
            $condiciones[] = 'a.anio = :anio';
            $parametros['anio'] = $anio;
        }

        return $this->db->paginar(
            'SELECT m.id, m.estado, m.estudiante_id, m.apoderado_principal_id, a.anio, m.grado_seccion_id,
                    e.dni, e.nombres, e.apellido_paterno, e.apellido_materno,
                    n.nombre AS nivel, g.numero AS grado, g.nombre AS grado_nombre, s.nombre AS seccion, m.creado_en
             FROM matricula m
             JOIN anio a ON a.id = m.anio_id
             JOIN estudiante e ON e.id = m.estudiante_id
             JOIN nivel n ON n.id = m.nivel_id
             JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
             JOIN grado g ON g.id = gs.grado_id
             JOIN seccion s ON s.id = gs.seccion_id '
            . ($condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '')
            . ' ORDER BY a.anio DESC, m.id DESC',
            $parametros,
            $paginacion
        );
    }

    public function existeEnAnio(int $estudianteId, int $anioId): bool
    {
        return $this->db->valor(
            'SELECT 1 FROM matricula WHERE estudiante_id = :estudiante_id AND anio_id = :anio_id',
            ['estudiante_id' => $estudianteId, 'anio_id' => $anioId]
        ) !== null;
    }

    public function compartenResponsable(int $estudianteId, int $responsableId): bool
    {
        return $this->db->valor(
            'SELECT 1 FROM estudiante_responsable WHERE estudiante_id = :estudiante_id AND responsable_id = :responsable_id',
            ['estudiante_id' => $estudianteId, 'responsable_id' => $responsableId]
        ) !== null;
    }

    /** @param array{estudiante_id: int, anio_id: int, nivel_id: int, grado_seccion_id: int, apoderado_principal_id: ?int, registrado_por: ?int} $datos */
    public function crear(array $datos): int
    {
        return $this->db->insertar(
            'INSERT INTO matricula (estudiante_id, anio_id, nivel_id, grado_seccion_id, apoderado_principal_id, registrado_por)
             VALUES (:estudiante_id, :anio_id, :nivel_id, :grado_seccion_id, :apoderado_principal_id, :registrado_por)
             RETURNING id',
            $datos
        );
    }

}
