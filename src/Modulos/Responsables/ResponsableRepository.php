<?php
declare(strict_types=1);

namespace App\Modulos\Responsables;

use App\Core\Database;
use App\Core\Pagina;
use App\Core\Paginacion;
use App\Soporte\Booleano;
use App\Soporte\FiltroAcademico;

final class ResponsableRepository
{
    /** Pagos que cuentan como realizados (excluye pendientes y anulados). */
    private const PAGO_VALIDO = "LOWER(COALESCE(NULLIF(p.estado, ''), p.estado_pago, 'pagado')) NOT IN ('pendiente', 'anulado')";

    public function __construct(private Database $db)
    {
    }

    public function buscar(int $id): ?array
    {
        return $this->db->fila(
            'SELECT id, dni, nombres, apellido_paterno, apellido_materno, nombre_padre_adicional, direccion,
                    celular, celular_actual, celular_padre, celular_madre, correo, nro_tarjeta_apafa, creado_en
             FROM responsable WHERE id = :id',
            ['id' => $id]
        );
    }

    public function buscarPorTexto(string $termino): array
    {
        return $this->db->filas(
            "SELECT id, dni, CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno) AS nombre
             FROM responsable
             WHERE CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno, dni) ILIKE :termino
             ORDER BY apellido_paterno, apellido_materno, nombres
             LIMIT 10",
            ['termino' => '%' . $termino . '%']
        );
    }

    private static function contactoLateral(string $tipos, string $extra = ''): string
    {
        return "SELECT er.id AS relacion_id, contacto.id AS responsable_id, contacto.dni,
                       contacto.nombres, contacto.apellido_paterno, contacto.apellido_materno,
                       contacto.celular, er.parentesco
                FROM estudiante_responsable er
                JOIN responsable contacto ON contacto.id = er.responsable_id
                WHERE er.estudiante_id = e.id AND er.tipo IN ({$tipos}){$extra}
                ORDER BY CASE er.tipo WHEN 'emergencia' THEN 0 ELSE 1 END, er.id
                LIMIT 1";
    }

    /** Una fila por vínculo responsable-estudiante, con padre, madre, emergencia, última matrícula y pagos. */
    public function listar(string $busqueda, FiltroAcademico $filtro, Paginacion $paginacion): Pagina
    {
        $parametros = [];
        $filtros = $filtro->condiciones($parametros);
        $filtroSql = $filtros ? ' AND ' . implode(' AND ', $filtros) : '';

        $condiciones = [];
        if ($busqueda !== '') {
            $condiciones[] = "CONCAT_WS(' ', r.dni, r.nombres, r.apellido_paterno, r.apellido_materno, e.dni, e.nombres, e.apellido_paterno, e.apellido_materno) ILIKE :busqueda";
            $parametros['busqueda'] = '%' . $busqueda . '%';
        }
        if (!$filtro->vacio()) {
            $condiciones[] = 'matricula.id IS NOT NULL';
        }

        $sql = 'SELECT r.id, r.dni, r.nombres, r.apellido_paterno, r.apellido_materno, r.direccion,
                       COALESCE(NULLIF(r.celular_actual, \'\'), r.celular) AS celular, r.nro_tarjeta_apafa,
                       er.id AS relacion_id, er.tipo AS relacion_tipo, er.parentesco, er.es_apoderado,
                       e.id AS estudiante_id, e.nombres AS estudiante_nombres,
                       e.apellido_paterno AS estudiante_apellido_paterno, e.apellido_materno AS estudiante_apellido_materno,
                       to_jsonb(padre) AS padre, to_jsonb(madre) AS madre, to_jsonb(emergencia) AS emergencia,
                       matricula.anio, matricula.nivel_id, matricula.nivel, matricula.grado_id, matricula.grado,
                       matricula.seccion_id, matricula.seccion,
                       COALESCE(pagos.cantidad, 0) AS pagos_cantidad, COALESCE(pagos.total, 0) AS pagos_total
                FROM responsable r
                LEFT JOIN estudiante_responsable er ON er.responsable_id = r.id
                LEFT JOIN estudiante e ON e.id = er.estudiante_id
                LEFT JOIN LATERAL (' . self::contactoLateral("'padre'") . ') padre ON TRUE
                LEFT JOIN LATERAL (' . self::contactoLateral("'madre'") . ') madre ON TRUE
                LEFT JOIN LATERAL (' . self::contactoLateral("'emergencia', 'otro'", ' AND contacto.id <> r.id') . ') emergencia ON TRUE
                LEFT JOIN LATERAL (
                    SELECT m.id, a.anio, n.id AS nivel_id, n.nombre AS nivel,
                           g.id AS grado_id, g.nombre AS grado, s.id AS seccion_id, s.nombre AS seccion
                    FROM matricula m
                    LEFT JOIN anio a ON a.id = m.anio_id
                    LEFT JOIN nivel n ON n.id = m.nivel_id
                    LEFT JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
                    LEFT JOIN grado g ON g.id = gs.grado_id
                    LEFT JOIN seccion s ON s.id = gs.seccion_id
                    WHERE m.estudiante_id = e.id' . $filtroSql . '
                    ORDER BY a.anio DESC NULLS LAST, m.id DESC
                    LIMIT 1
                ) matricula ON TRUE
                LEFT JOIN LATERAL (
                    SELECT COUNT(*) FILTER (WHERE ' . self::PAGO_VALIDO . ') AS cantidad,
                           COALESCE(SUM(p.monto) FILTER (WHERE ' . self::PAGO_VALIDO . '), 0) AS total
                    FROM pago p
                    WHERE EXISTS (
                        SELECT 1 FROM matricula pm
                        JOIN estudiante_responsable par ON par.estudiante_id = pm.estudiante_id
                        WHERE pm.id = p.matricula_id AND par.responsable_id = r.id
                    ) OR LOWER(TRIM(COALESCE(p.responsable, \'\'))) =
                         LOWER(TRIM(CONCAT_WS(\' \', r.nombres, r.apellido_paterno, r.apellido_materno)))
                ) pagos ON TRUE '
            . ($condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '')
            . ' ORDER BY r.id DESC, e.apellido_paterno, e.nombres';

        return $this->db->paginar($sql, $parametros, $paginacion)->mapear(static function (array $fila): array {
            foreach (['padre', 'madre', 'emergencia'] as $tipo) {
                $fila[$tipo] = $fila[$tipo] === null ? null : json_decode((string) $fila[$tipo], true);
            }
            $fila['pagos_cantidad'] = (int) $fila['pagos_cantidad'];
            return Booleano::normalizar($fila, ['es_apoderado']);
        });
    }

    public function estudiantesDe(int $id): array
    {
        return array_map(static fn (array $f): array => Booleano::normalizar($f, ['es_apoderado']), $this->db->filas(
            'SELECT e.id, e.dni, e.nombres, e.apellido_paterno, e.apellido_materno,
                    er.id AS relacion_id, er.tipo, er.parentesco, er.es_apoderado,
                    ultima.anio, ultima.nivel, ultima.grado, ultima.seccion
             FROM estudiante_responsable er
             JOIN estudiante e ON e.id = er.estudiante_id
             LEFT JOIN LATERAL (
                 SELECT an.anio, n.nombre AS nivel, g.nombre AS grado, s.nombre AS seccion
                 FROM matricula m
                 LEFT JOIN anio an ON an.id = m.anio_id
                 LEFT JOIN nivel n ON n.id = m.nivel_id
                 LEFT JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
                 LEFT JOIN grado g ON g.id = gs.grado_id
                 LEFT JOIN seccion s ON s.id = gs.seccion_id
                 WHERE m.estudiante_id = e.id
                 ORDER BY an.anio DESC NULLS LAST, m.id DESC
                 LIMIT 1
             ) ultima ON TRUE
             WHERE er.responsable_id = :id
             ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres',
            ['id' => $id]
        ));
    }

    /** Pagos hechos por el responsable o por cualquiera de sus estudiantes (incluye registros históricos por nombre). */
    public function pagosDe(int $id, string $nombreCompleto): array
    {
        return $this->db->filas(
            "SELECT DISTINCT p.id, p.fecha_pago, p.monto, p.nro_recibo, p.observacion,
                    COALESCE(NULLIF(p.concepto, ''), cp.descripcion, '-') AS concepto,
                    COALESCE(NULLIF(p.estado, ''), p.estado_pago, 'Pagado') AS estado,
                    e.id AS estudiante_id, e.nombres AS estudiante_nombres, e.apellido_paterno AS estudiante_apellido_paterno
             FROM pago p
             LEFT JOIN matricula m ON m.id = p.matricula_id
             LEFT JOIN estudiante e ON e.id = m.estudiante_id
             LEFT JOIN estudiante_responsable er ON er.estudiante_id = m.estudiante_id AND er.responsable_id = :vinculado_id
             LEFT JOIN concepto_pago cp ON cp.id = p.concepto_id
             WHERE p.responsable_id = :directo_id
                OR er.responsable_id IS NOT NULL
                OR LOWER(TRIM(COALESCE(p.responsable, ''))) = LOWER(TRIM(:nombre_historico))
             ORDER BY p.fecha_pago DESC, p.id DESC",
            ['vinculado_id' => $id, 'directo_id' => $id, 'nombre_historico' => $nombreCompleto]
        );
    }

    /** @param array<string, ?string> $datos */
    public function crear(array $datos): int
    {
        return $this->db->insertar(
            'INSERT INTO responsable (dni, nombres, apellido_paterno, apellido_materno, nombre_padre_adicional,
                                      direccion, celular, celular_actual, celular_padre, celular_madre, nro_tarjeta_apafa)
             VALUES (:dni, :nombres, :apellido_paterno, :apellido_materno, :nombre_padre_adicional,
                     :direccion, :celular, :celular_actual, :celular_padre, :celular_madre, :nro_tarjeta_apafa)
             RETURNING id',
            $datos + ['celular' => $datos['celular_actual']]
        );
    }

    public function existeDni(string $dni, int $exceptoId = 0): bool
    {
        return $this->db->valor('SELECT 1 FROM responsable WHERE dni = :dni AND id <> :id', ['dni' => $dni, 'id' => $exceptoId]) !== null;
    }

    /** Crea o actualiza por DNI un contacto y devuelve su ID. */
    public function guardarPorDni(array $contacto): int
    {
        return $this->db->insertar(
            'INSERT INTO responsable (dni, nombres, apellido_paterno, apellido_materno, celular, celular_actual)
             VALUES (:dni, :nombres, :apellido_paterno, :apellido_materno, :celular, :celular_actual)
             ON CONFLICT (dni) DO UPDATE
             SET nombres = EXCLUDED.nombres, apellido_paterno = EXCLUDED.apellido_paterno,
                 apellido_materno = EXCLUDED.apellido_materno,
                 celular = COALESCE(EXCLUDED.celular, responsable.celular),
                 celular_actual = COALESCE(EXCLUDED.celular_actual, responsable.celular_actual),
                 actualizado_en = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             RETURNING id',
            self::datosContacto($contacto)
        );
    }

    public function actualizarContacto(int $id, array $contacto): bool
    {
        return $this->db->afectar(
            'UPDATE responsable
             SET dni = :dni, nombres = :nombres, apellido_paterno = :apellido_paterno, apellido_materno = :apellido_materno,
                 celular = :celular, celular_actual = :celular_actual,
                 actualizado_en = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            self::datosContacto($contacto) + ['id' => $id]
        ) === 1;
    }

    /** @param array<string, ?string> $datos dni, nombres, apellido_paterno, apellido_materno, direccion, celular */
    public function actualizarPrincipal(int $id, array $datos): bool
    {
        return $this->db->afectar(
            'UPDATE responsable
             SET dni = :dni, nombres = :nombres, apellido_paterno = :apellido_paterno, apellido_materno = :apellido_materno,
                 direccion = :direccion, celular = :celular, celular_actual = :celular_actual,
                 actualizado_en = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            $datos + ['celular_actual' => $datos['celular'], 'id' => $id]
        ) === 1;
    }

    private static function datosContacto(array $c): array
    {
        return [
            'dni' => $c['dni'],
            'nombres' => $c['nombres'],
            'apellido_paterno' => $c['apellido_paterno'],
            'apellido_materno' => $c['apellido_materno'],
            'celular' => $c['celular'],
            'celular_actual' => $c['celular'],
        ];
    }

    // --- Vínculos estudiante-responsable ---

    public function tipoVinculo(int $estudianteId, int $responsableId): ?string
    {
        $tipo = $this->db->valor(
            'SELECT tipo FROM estudiante_responsable WHERE estudiante_id = :estudiante_id AND responsable_id = :responsable_id',
            ['estudiante_id' => $estudianteId, 'responsable_id' => $responsableId]
        );
        return $tipo === null ? null : (string) $tipo;
    }

    /** Crea o actualiza el vínculo. Si $esApoderado es null, se conserva el valor existente. */
    public function vincular(int $estudianteId, int $responsableId, string $tipo, string $parentesco, ?bool $esApoderado = null): void
    {
        $this->db->afectar(
            'INSERT INTO estudiante_responsable (estudiante_id, responsable_id, parentesco, tipo, es_apoderado)
             VALUES (:estudiante_id, :responsable_id, :parentesco, :tipo, COALESCE(CAST(:es_apoderado AS boolean), FALSE))
             ON CONFLICT (estudiante_id, responsable_id) DO UPDATE
             SET parentesco = EXCLUDED.parentesco, tipo = EXCLUDED.tipo,
                 es_apoderado = COALESCE(CAST(:es_apoderado_nuevo AS boolean), estudiante_responsable.es_apoderado),
                 actualizado_en = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP',
            [
                'estudiante_id' => $estudianteId,
                'responsable_id' => $responsableId,
                'parentesco' => $parentesco,
                'tipo' => $tipo,
                'es_apoderado' => $esApoderado,
                'es_apoderado_nuevo' => $esApoderado,
            ]
        );
    }

    /**
     * Cambia el parentesco de un vínculo existente, comprobando que pertenezca al responsable
     * (y opcionalmente al estudiante y a uno de los tipos indicados).
     * @param list<string> $tipos
     */
    public function actualizarParentesco(int $relacionId, int $responsableId, string $parentesco, ?int $estudianteId = null, array $tipos = []): bool
    {
        return $this->db->afectar(
            'UPDATE estudiante_responsable
             SET parentesco = :parentesco, actualizado_en = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :relacion_id AND responsable_id = :responsable_id
               AND (CAST(:estudiante_id AS bigint) IS NULL OR estudiante_id = :estudiante_id_filtro)
               AND (CAST(:tipos AS text[]) IS NULL OR tipo = ANY(CAST(:tipos_filtro AS text[])))',
            [
                'parentesco' => $parentesco,
                'relacion_id' => $relacionId,
                'responsable_id' => $responsableId,
                'estudiante_id' => $estudianteId,
                'estudiante_id_filtro' => $estudianteId,
                'tipos' => $tipos === [] ? null : '{' . implode(',', $tipos) . '}',
                'tipos_filtro' => $tipos === [] ? null : '{' . implode(',', $tipos) . '}',
            ]
        ) === 1;
    }

    public function quitarApoderados(int $estudianteId): void
    {
        $this->db->afectar('UPDATE estudiante_responsable SET es_apoderado = FALSE WHERE estudiante_id = :id', ['id' => $estudianteId]);
    }

    public function candidatosApoderado(int $estudianteId, int $exceptoResponsableId): array
    {
        return $this->db->filas(
            'SELECT r.id, r.nombres, r.apellido_paterno, r.apellido_materno, er.tipo, er.parentesco
             FROM estudiante_responsable er
             JOIN responsable r ON r.id = er.responsable_id
             WHERE er.estudiante_id = :estudiante_id AND er.responsable_id <> :responsable_id
             ORDER BY r.apellido_paterno, r.nombres',
            ['estudiante_id' => $estudianteId, 'responsable_id' => $exceptoResponsableId]
        );
    }

    public function nombreCompleto(int $id): ?string
    {
        $nombre = $this->db->valor(
            "SELECT CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno) FROM responsable WHERE id = :id FOR UPDATE",
            ['id' => $id]
        );
        return $nombre === null ? null : (string) $nombre;
    }

    public function bloquearEstudiante(int $estudianteId): bool
    {
        return $this->db->valor('SELECT id FROM estudiante WHERE id = :id FOR UPDATE', ['id' => $estudianteId]) !== null;
    }

    /** @return array{id: int, es_apoderado: bool}|null */
    public function vinculoBloqueado(int $estudianteId, int $responsableId): ?array
    {
        $fila = $this->db->fila(
            'SELECT id, es_apoderado FROM estudiante_responsable
             WHERE estudiante_id = :estudiante_id AND responsable_id = :responsable_id FOR UPDATE',
            ['estudiante_id' => $estudianteId, 'responsable_id' => $responsableId]
        );
        return $fila === null ? null : ['id' => (int) $fila['id'], 'es_apoderado' => Booleano::desdeBd($fila['es_apoderado'])];
    }

    public function cantidadResponsables(int $estudianteId): int
    {
        return (int) $this->db->valor('SELECT COUNT(*) FROM estudiante_responsable WHERE estudiante_id = :id', ['id' => $estudianteId]);
    }

    /** Traspasa el rol de apoderado (vínculo y matrículas) de un responsable a otro. */
    public function traspasarApoderado(int $estudianteId, int $anteriorId, int $nuevoId): void
    {
        $this->db->afectar(
            'UPDATE estudiante_responsable
             SET es_apoderado = (responsable_id = :nuevo_id), actualizado_en = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE estudiante_id = :estudiante_id AND responsable_id IN (:anterior_id, :nuevo_id_lista)',
            ['nuevo_id' => $nuevoId, 'estudiante_id' => $estudianteId, 'anterior_id' => $anteriorId, 'nuevo_id_lista' => $nuevoId]
        );
        $this->db->afectar(
            'UPDATE matricula SET apoderado_principal_id = :nuevo_id
             WHERE estudiante_id = :estudiante_id AND apoderado_principal_id = :anterior_id',
            ['nuevo_id' => $nuevoId, 'estudiante_id' => $estudianteId, 'anterior_id' => $anteriorId]
        );
    }

    public function desvincular(int $estudianteId, int $responsableId): void
    {
        $this->db->afectar(
            'DELETE FROM estudiante_responsable WHERE estudiante_id = :estudiante_id AND responsable_id = :responsable_id',
            ['estudiante_id' => $estudianteId, 'responsable_id' => $responsableId]
        );
    }

    public function eliminar(int $id): void
    {
        $this->db->afectar('DELETE FROM responsable WHERE id = :id', ['id' => $id]);
    }

    /** Tiene estudiantes vinculados. */
    public function tieneEstudiantes(int $id): bool
    {
        return $this->db->valor('SELECT 1 FROM estudiante_responsable WHERE responsable_id = :id LIMIT 1', ['id' => $id]) !== null;
    }

    /** Figura en pagos, cuentas de pago o como apoderado principal de una matrícula. */
    public function tieneHistorial(int $id): bool
    {
        return $this->db->valor(
            'SELECT 1 WHERE EXISTS (SELECT 1 FROM pago WHERE responsable_id = :pago_id)
                         OR EXISTS (SELECT 1 FROM cuenta_pago WHERE responsable_id = :cuenta_id)
                         OR EXISTS (SELECT 1 FROM matricula WHERE apoderado_principal_id = :matricula_id)',
            ['pago_id' => $id, 'cuenta_id' => $id, 'matricula_id' => $id]
        ) !== null;
    }

    /** Borra al responsable solo si ya no tiene estudiantes ni historial de pagos o matrículas. */
    public function eliminarSiNoTieneVinculos(int $id): void
    {
        if (!$this->tieneEstudiantes($id) && !$this->tieneHistorial($id)) {
            $this->eliminar($id);
        }
    }
}
