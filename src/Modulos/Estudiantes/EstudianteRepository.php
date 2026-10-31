<?php
declare(strict_types=1);

namespace App\Modulos\Estudiantes;

use App\Core\Database;
use App\Core\Pagina;
use App\Core\Paginacion;
use App\Soporte\Booleano;
use App\Soporte\FiltroAcademico;

final class EstudianteRepository
{
    /** Última matrícula de un estudiante (alias e = estudiante). */
    private const ULTIMA_MATRICULA = '
        SELECT m.id, an.anio, n.nombre AS nivel, g.nombre AS grado, s.nombre AS seccion
        FROM matricula m
        LEFT JOIN anio an ON an.id = m.anio_id
        LEFT JOIN nivel n ON n.id = m.nivel_id
        LEFT JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
        LEFT JOIN grado g ON g.id = gs.grado_id
        LEFT JOIN seccion s ON s.id = gs.seccion_id
        WHERE m.estudiante_id = e.id
        ORDER BY an.anio DESC NULLS LAST, m.id DESC
        LIMIT 1';

    public function __construct(private Database $db)
    {
    }

    public function buscar(int $id): ?array
    {
        return $this->db->fila(
            'SELECT id, dni, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, estado_academico, creado_en, actualizado_en
             FROM estudiante WHERE id = :id',
            ['id' => $id]
        );
    }

    /** Estudiantes con su última matrícula, filtrados por estado, texto y nivel/grado/sección. */
    public function listar(string $estado, string $busqueda, FiltroAcademico $filtro, Paginacion $paginacion): Pagina
    {
        $parametros = ['estado_academico' => $estado];
        $condiciones = ['e.estado_academico = :estado_academico', ...$filtro->condiciones($parametros)];
        if ($busqueda !== '') {
            $condiciones[] = "CONCAT_WS(' ', e.dni, e.nombres, e.apellido_paterno, e.apellido_materno) ILIKE :busqueda";
            $parametros['busqueda'] = '%' . $busqueda . '%';
        }

        return $this->db->paginar(
            'SELECT * FROM (
                SELECT DISTINCT ON (e.id)
                       e.id, e.dni, e.nombres, e.apellido_paterno, e.apellido_materno, e.estado_academico,
                       m.id AS matricula_id, n.nombre AS nivel, g.nombre AS grado, s.nombre AS seccion,
                       an.anio, m.estado AS estado_matricula
                FROM matricula m
                JOIN estudiante e ON e.id = m.estudiante_id
                JOIN nivel n ON n.id = m.nivel_id
                JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
                JOIN grado g ON g.id = gs.grado_id
                JOIN seccion s ON s.id = gs.seccion_id
                JOIN anio an ON an.id = m.anio_id
                WHERE ' . implode(' AND ', $condiciones) . '
                ORDER BY e.id, an.anio DESC, m.id DESC
             ) ultimos
             ORDER BY apellido_paterno, apellido_materno, nombres, id',
            $parametros,
            $paginacion
        );
    }

    /** Búsqueda rápida por DNI o nombre. */
    public function buscarPorTexto(string $termino, int $limite = 15): array
    {
        return $this->db->filas(
            "SELECT id, dni, nombres, apellido_paterno, apellido_materno
             FROM estudiante
             WHERE CONCAT_WS(' ', dni, nombres, apellido_paterno, apellido_materno) ILIKE :termino
             ORDER BY apellido_paterno, nombres
             LIMIT :limite",
            ['termino' => '%' . $termino . '%', 'limite' => $limite]
        );
    }

    public function responsables(int $id): array
    {
        return array_map(static fn (array $f): array => Booleano::normalizar($f, ['es_apoderado']), $this->db->filas(
            "SELECT er.id AS relacion_id, er.tipo, er.parentesco, er.es_apoderado, r.id, r.dni, r.nombres,
                    r.apellido_paterno, r.apellido_materno, COALESCE(NULLIF(r.celular_actual, ''), r.celular) AS celular,
                    r.correo, r.direccion
             FROM estudiante_responsable er
             JOIN responsable r ON r.id = er.responsable_id
             WHERE er.estudiante_id = :id
             ORDER BY CASE er.tipo WHEN 'padre' THEN 1 WHEN 'madre' THEN 2 ELSE 3 END, r.apellido_paterno",
            ['id' => $id]
        ));
    }

    public function matriculas(int $id): array
    {
        return $this->db->filas(
            'SELECT m.id, m.estado, a.anio, n.nombre AS nivel, g.nombre AS grado, s.nombre AS seccion, m.grado_seccion_id
             FROM matricula m
             LEFT JOIN anio a ON a.id = m.anio_id
             LEFT JOIN nivel n ON n.id = m.nivel_id
             LEFT JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
             LEFT JOIN grado g ON g.id = gs.grado_id
             LEFT JOIN seccion s ON s.id = gs.seccion_id
             WHERE m.estudiante_id = :id
             ORDER BY a.anio DESC NULLS LAST, m.id DESC',
            ['id' => $id]
        );
    }

    /** Estudiantes que comparten al menos un responsable. */
    public function hermanos(int $id): array
    {
        return $this->db->filas(
            'SELECT DISTINCT e.id, e.nombres, e.apellido_paterno, e.apellido_materno, ultima.nivel, ultima.grado
             FROM estudiante_responsable familia
             JOIN estudiante_responsable familia_hermano
               ON familia_hermano.responsable_id = familia.responsable_id
              AND familia_hermano.estudiante_id <> familia.estudiante_id
             JOIN estudiante e ON e.id = familia_hermano.estudiante_id
             LEFT JOIN LATERAL (' . self::ULTIMA_MATRICULA . ') ultima ON TRUE
             WHERE familia.estudiante_id = :id
             ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres',
            ['id' => $id]
        );
    }

    public function pagos(int $id): array
    {
        return $this->db->filas(
            "SELECT p.id, p.matricula_id, p.fecha_pago, p.monto, p.nro_recibo, p.observacion,
                    COALESCE(NULLIF(p.concepto, ''), cp.descripcion, '-') AS concepto,
                    COALESCE(NULLIF(p.estado, ''), p.estado_pago, 'Pagado') AS estado
             FROM pago p
             JOIN matricula m ON m.id = p.matricula_id
             LEFT JOIN concepto_pago cp ON cp.id = p.concepto_id
             WHERE m.estudiante_id = :id
             ORDER BY p.fecha_pago DESC, p.id DESC",
            ['id' => $id]
        );
    }

    /** Última matrícula con los IDs para precargar un formulario. */
    public function ultimaMatricula(int $id): ?array
    {
        return $this->db->fila(
            'SELECT m.id, m.apoderado_principal_id, COALESCE(m.nivel_id, g.nivel_id) AS nivel_id,
                    gs.grado_id, g.numero AS grado_numero, gs.seccion_id, gs.id AS grado_seccion_id, a.anio
             FROM matricula m
             LEFT JOIN anio a ON a.id = m.anio_id
             LEFT JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
             LEFT JOIN grado g ON g.id = gs.grado_id
             WHERE m.estudiante_id = :id
             ORDER BY a.anio DESC NULLS LAST, m.id DESC
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * Contacto del estudiante de los tipos indicados, priorizando al apoderado.
     * @param list<string> $tipos
     */
    public function contactoPorTipos(int $id, array $tipos, ?int $apoderadoId): ?array
    {
        $fila = $this->db->fila(
            "SELECT er.id AS relacion_id, r.id, r.dni, r.nombres, r.apellido_paterno, r.apellido_materno,
                    COALESCE(NULLIF(r.celular_actual, ''), r.celular) AS celular, er.parentesco, er.es_apoderado
             FROM estudiante_responsable er
             JOIN responsable r ON r.id = er.responsable_id
             WHERE er.estudiante_id = :id AND er.tipo = ANY(CAST(:tipos AS text[]))
             ORDER BY (r.id = CAST(:apoderado_id AS bigint)) DESC NULLS LAST, er.es_apoderado DESC, er.id
             LIMIT 1",
            ['id' => $id, 'tipos' => '{' . implode(',', $tipos) . '}', 'apoderado_id' => $apoderadoId]
        );
        return $fila === null ? null : Booleano::normalizar($fila, ['es_apoderado']);
    }

    /** @param array{dni: string, nombres: string, apellido_paterno: string, apellido_materno: string} $datos */
    public function crear(array $datos): int
    {
        return $this->db->insertar(
            'INSERT INTO estudiante (dni, nombres, apellido_paterno, apellido_materno)
             VALUES (:dni, :nombres, :apellido_paterno, :apellido_materno)
             RETURNING id',
            $datos
        );
    }

    public function dniRegistrado(string $dni, int $exceptoId = 0): bool
    {
        return $this->db->valor('SELECT 1 FROM estudiante WHERE dni = :dni AND id <> :id', ['dni' => $dni, 'id' => $exceptoId]) !== null;
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $id, array $datos): bool
    {
        return $this->db->afectar(
            'UPDATE estudiante
             SET dni = :dni, nombres = :nombres, apellido_paterno = :apellido_paterno,
                 apellido_materno = :apellido_materno, fecha_nacimiento = :fecha_nacimiento,
                 actualizado_en = CURRENT_TIMESTAMP
             WHERE id = :id',
            $datos + ['id' => $id]
        ) === 1;
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        return $this->db->afectar(
            'UPDATE estudiante SET estado_academico = :estado, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id',
            ['estado' => $estado, 'id' => $id]
        ) === 1;
    }

    /** Bloquea la fila y devuelve el nombre completo (para auditar), o null si no existe. */
    public function bloquearNombre(int $id): ?string
    {
        $nombre = $this->db->valor(
            "SELECT CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno) FROM estudiante WHERE id = :id FOR UPDATE",
            ['id' => $id]
        );
        return $nombre === null ? null : (string) $nombre;
    }

    public function tieneDependencias(int $id): bool
    {
        return $this->db->valor(
            'SELECT 1 FROM matricula m
             WHERE m.estudiante_id = :id
               AND (EXISTS (SELECT 1 FROM pago p WHERE p.matricula_id = m.id)
                 OR EXISTS (SELECT 1 FROM calificacion c WHERE c.matricula_id = m.id))
             LIMIT 1',
            ['id' => $id]
        ) !== null;
    }

    public function eliminar(int $id): void
    {
        $this->db->afectar('DELETE FROM estudiante WHERE id = :id', ['id' => $id]);
    }
}
