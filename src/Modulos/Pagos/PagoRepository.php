<?php
declare(strict_types=1);

namespace App\Modulos\Pagos;

use App\Core\Database;
use App\Core\Pagina;
use App\Core\Paginacion;
use App\Soporte\Dinero;

final class PagoRepository
{
    /** Apoderado de la matrícula: el principal o, si no hay, el primer responsable (apoderado > padre > madre > otro). */
    private const APODERADO_MATRICULA = "
        LEFT JOIN LATERAL (
            SELECT ar.responsable_id FROM estudiante_responsable ar
            WHERE ar.estudiante_id = m.estudiante_id
            ORDER BY ar.es_apoderado DESC, CASE ar.tipo WHEN 'padre' THEN 1 WHEN 'madre' THEN 2 ELSE 3 END
            LIMIT 1
        ) respaldo ON TRUE
        JOIN responsable r ON r.id = COALESCE(m.apoderado_principal_id, respaldo.responsable_id)";

    public function __construct(private Database $db)
    {
    }

    public function listar(string $busqueda, Paginacion $paginacion): Pagina
    {
        $parametros = [];
        $donde = '';
        if ($busqueda !== '') {
            $donde = " WHERE CONCAT_WS(' ', a.dni, a.nombres, a.apellido_paterno, a.apellido_materno, p.responsable, p.concepto, p.entregado_por) ILIKE :busqueda";
            $parametros['busqueda'] = '%' . $busqueda . '%';
        }

        return $this->db->paginar(
            "SELECT p.id, p.matricula_id, p.concepto_id, p.cuenta_pago_id, p.concepto, p.fecha_pago,
                    p.monto, p.abono_hoy, p.entregado_por, p.estado, p.observacion,
                    COALESCE(cuenta.monto_total, p.monto_total, p.monto) AS total_cuenta,
                    COALESCE(cuenta.monto_pagado, p.monto_pagado, p.monto) AS pagado_cuenta,
                    COALESCE(cuenta.saldo_restante, p.saldo_restante, 0) AS saldo_cuenta,
                    CONCAT_WS(' ', a.nombres, a.apellido_paterno, a.apellido_materno) AS estudiante,
                    COALESCE(CONCAT_WS(' ', r.nombres, r.apellido_paterno, r.apellido_materno), p.responsable) AS apoderado
             FROM pago p
             LEFT JOIN matricula m ON m.id = p.matricula_id
             LEFT JOIN estudiante a ON a.id = m.estudiante_id
             LEFT JOIN responsable r ON r.id = COALESCE(p.responsable_id, m.apoderado_principal_id)
             LEFT JOIN cuenta_pago cuenta ON cuenta.id = p.cuenta_pago_id"
            . $donde . ' ORDER BY p.fecha_pago DESC, p.id DESC',
            $parametros,
            $paginacion
        )->mapear(static function (array $pago): array {
            $pago['tiene_deuda'] = Dinero::aCentimos($pago['saldo_cuenta']) > 0;
            return $pago;
        });
    }

    /** Estudiantes con matrícula activa (la más reciente de cada uno). */
    public function buscarEstudiantes(string $termino): array
    {
        return $this->db->filas(
            "SELECT DISTINCT ON (m.estudiante_id)
                    m.id AS matricula_id, a.id AS estudiante_id, a.dni,
                    CONCAT_WS(' ', a.nombres, a.apellido_paterno, a.apellido_materno) AS nombre
             FROM matricula m
             JOIN estudiante a ON a.id = m.estudiante_id
             LEFT JOIN anio an ON an.id = m.anio_id
             WHERE m.estado = 'Activa'
               AND CONCAT_WS(' ', a.dni, a.nombres, a.apellido_paterno, a.apellido_materno) ILIKE :termino
             ORDER BY m.estudiante_id, an.anio DESC NULLS LAST, m.id DESC
             LIMIT 20",
            ['termino' => '%' . $termino . '%']
        );
    }

    public function apoderadoDeMatricula(int $matriculaId): ?array
    {
        return $this->db->fila(
            "SELECT r.id, r.dni, CONCAT_WS(' ', r.nombres, r.apellido_paterno, r.apellido_materno) AS nombre
             FROM matricula m " . self::APODERADO_MATRICULA . "
             WHERE m.id = :id AND m.estado = 'Activa'",
            ['id' => $matriculaId]
        );
    }

    /** Matrícula activa con su apoderado, lista para cobrar. */
    public function matriculaParaCobro(int $matriculaId): ?array
    {
        return $this->db->fila(
            "SELECT m.id, m.anio_id, r.id AS apoderado_id,
                    CONCAT_WS(' ', a.nombres, a.apellido_paterno, a.apellido_materno) AS estudiante,
                    CONCAT_WS(' ', r.nombres, r.apellido_paterno, r.apellido_materno) AS apoderado
             FROM matricula m
             JOIN estudiante a ON a.id = m.estudiante_id " . self::APODERADO_MATRICULA . "
             WHERE m.id = :id AND m.estado = 'Activa'",
            ['id' => $matriculaId]
        );
    }

    /** Tarifa del concepto activo y, si existe, la cuenta de esa matrícula. */
    public function saldo(int $matriculaId, int $conceptoId): ?array
    {
        return $this->db->fila(
            'SELECT concepto.monto_default, cuenta.id AS cuenta_id,
                    cuenta.monto_total, cuenta.monto_pagado, cuenta.saldo_restante
             FROM concepto_pago AS concepto
             LEFT JOIN cuenta_pago AS cuenta
               ON cuenta.concepto_id = concepto.id AND cuenta.matricula_id = :matricula_id
             WHERE concepto.id = :concepto_id AND concepto.activo = TRUE',
            ['matricula_id' => $matriculaId, 'concepto_id' => $conceptoId]
        );
    }

    /** Cuenta compartida de un concepto "familia": una por apoderado y año (bloquea al apoderado y la cuenta). */
    public function cuentaFamiliar(int $responsableId, int $conceptoId, int $anioId): ?array
    {
        $this->db->ejecutar('SELECT id FROM responsable WHERE id = :id FOR UPDATE', ['id' => $responsableId]);
        return $this->db->fila(
            'SELECT cuenta.*
             FROM cuenta_pago cuenta
             JOIN matricula deuda ON deuda.id = cuenta.matricula_id
             WHERE cuenta.responsable_id = :responsable_id
               AND cuenta.concepto_id = :concepto_id
               AND deuda.anio_id = :anio_id
             ORDER BY cuenta.id
             LIMIT 1
             FOR UPDATE OF cuenta',
            ['responsable_id' => $responsableId, 'concepto_id' => $conceptoId, 'anio_id' => $anioId]
        );
    }

    public function cuentaDeMatricula(int $matriculaId, int $conceptoId): ?array
    {
        return $this->db->fila(
            'SELECT * FROM cuenta_pago WHERE matricula_id = :matricula_id AND concepto_id = :concepto_id FOR UPDATE',
            ['matricula_id' => $matriculaId, 'concepto_id' => $conceptoId]
        );
    }

    /** Abre la cuenta (si otra petición la abrió primero, no hace nada) y la devuelve bloqueada. */
    public function abrirCuenta(int $matriculaId, int $responsableId, int $conceptoId, int $tarifaCentimos): ?array
    {
        $tarifa = Dinero::texto($tarifaCentimos);
        $this->db->ejecutar(
            'INSERT INTO cuenta_pago (matricula_id, responsable_id, concepto_id, monto_total, monto_pagado, saldo_restante)
             VALUES (:matricula_id, :responsable_id, :concepto_id, :monto_total, 0, :saldo_inicial)
             ON CONFLICT (matricula_id, concepto_id) WHERE matricula_id IS NOT NULL DO NOTHING',
            [
                'matricula_id' => $matriculaId,
                'responsable_id' => $responsableId,
                'concepto_id' => $conceptoId,
                'monto_total' => $tarifa,
                'saldo_inicial' => $tarifa,
            ]
        );
        return $this->cuentaDeMatricula($matriculaId, $conceptoId);
    }

    public function actualizarCuenta(int $cuentaId, int $pagadoCentimos, int $saldoCentimos): void
    {
        $this->db->ejecutar(
            'UPDATE cuenta_pago SET monto_pagado = :pagado, saldo_restante = :saldo, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id',
            ['pagado' => Dinero::texto($pagadoCentimos), 'saldo' => Dinero::texto($saldoCentimos), 'id' => $cuentaId]
        );
    }

    public function insertarPago(array $datos): int
    {
        return $this->db->insertar(
            'INSERT INTO pago (matricula_id, responsable_id, concepto_id, cuenta_pago_id, responsable, concepto,
                               monto, monto_total, monto_pagado, saldo_restante, abono_hoy, entregado_por,
                               fecha_pago, estado, estado_pago, observacion, registrado_por)
             VALUES (:matricula_id, :responsable_id, :concepto_id, :cuenta_pago_id, :responsable, :concepto,
                     :monto, :monto_total, :monto_pagado, :saldo_restante, :abono_hoy, :entregado_por,
                     :fecha_pago, :estado, :estado_pago, :observacion, :registrado_por)
             RETURNING id',
            $datos
        );
    }

    /** Al cancelar la deuda, todos los abonos de la cuenta quedan como pagados. */
    public function marcarCuentaPagada(int $cuentaId): void
    {
        $this->db->ejecutar(
            "UPDATE pago SET estado = 'Pagado', estado_pago = 'pagado' WHERE cuenta_pago_id = :id",
            ['id' => $cuentaId]
        );
    }
}
