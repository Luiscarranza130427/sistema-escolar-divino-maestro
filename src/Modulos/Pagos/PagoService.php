<?php
declare(strict_types=1);

namespace App\Modulos\Pagos;

use App\Auth\Contexto;
use App\Core\Database;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;
use App\Soporte\Dinero;
use RuntimeException;

/**
 * Abonos por concepto. Cada matrícula tiene una cuenta por concepto; los conceptos "familia"
 * comparten una sola cuenta por apoderado y año. Todos los cálculos se hacen en céntimos.
 */
final class PagoService
{
    public function __construct(
        private Database $db,
        private PagoRepository $pagos,
        private ConceptoRepository $conceptos,
        private Contexto $contexto,
        private Auditor $auditor
    ) {
    }

    public function apoderado(int $matriculaId): array
    {
        return $this->pagos->apoderadoDeMatricula($matriculaId)
            ?? throw HttpException::noEncontrado('La matrícula no está activa o no tiene apoderado.');
    }

    /** Saldo de un concepto para una matrícula (sin cuenta abierta, el saldo es la tarifa). */
    public function saldo(int $matriculaId, int $conceptoId): array
    {
        $cuenta = $this->pagos->saldo($matriculaId, $conceptoId);
        if ($cuenta === null || $cuenta['monto_default'] === null) {
            throw HttpException::regla('Este concepto no tiene una tarifa configurada.', 'concepto_id');
        }

        $existe = $cuenta['cuenta_id'] !== null;
        $saldo = Dinero::aCentimos($existe ? $cuenta['saldo_restante'] : $cuenta['monto_default']);
        return [
            'monto_total' => Dinero::decimal(Dinero::aCentimos($cuenta['monto_total'] ?? $cuenta['monto_default'])),
            'monto_pagado' => Dinero::decimal(Dinero::aCentimos($cuenta['monto_pagado'])),
            'saldo_restante' => Dinero::decimal($saldo),
            'cuenta_existente' => $existe,
            'deuda_pendiente' => $existe && $saldo > 0,
        ];
    }

    /**
     * @param array{matricula_id: int, concepto_id: int, abono: int, entregado_por: string, fecha_pago: string, observacion: ?string} $datos abono en céntimos
     * @return array{id: int, saldo_restante: float, cancelado: bool}
     */
    public function registrar(array $datos): array
    {
        return $this->db->transaccion(function () use ($datos): array {
            $matricula = $this->pagos->matriculaParaCobro($datos['matricula_id'])
                ?? throw HttpException::regla('La matrícula no está activa o no tiene apoderado.', 'matricula_id');
            $concepto = $this->conceptos->activo($datos['concepto_id']);
            $tarifa = Dinero::aCentimos($concepto['monto_default'] ?? null);
            if ($concepto === null || $tarifa <= 0) {
                throw HttpException::regla('El concepto no está activo o no tiene una tarifa válida.', 'concepto_id');
            }

            $cuenta = null;
            if ($concepto['tipo_cobro'] === 'familia') {
                $cuenta = $this->pagos->cuentaFamiliar((int) $matricula['apoderado_id'], $datos['concepto_id'], (int) $matricula['anio_id']);
            }
            $cuenta ??= $this->pagos->cuentaDeMatricula($datos['matricula_id'], $datos['concepto_id'])
                ?? $this->pagos->abrirCuenta($datos['matricula_id'], (int) $matricula['apoderado_id'], $datos['concepto_id'], $tarifa)
                ?? throw new RuntimeException('No se pudo abrir o recuperar la cuenta de pago.');

            $total = Dinero::aCentimos($cuenta['monto_total']);
            $saldo = Dinero::aCentimos($cuenta['saldo_restante']);
            if ($datos['abono'] > $saldo) {
                throw HttpException::regla('El abono supera el saldo pendiente de este concepto (S/ ' . Dinero::texto($saldo) . ').', 'abono_hoy');
            }

            $pagado = Dinero::aCentimos($cuenta['monto_pagado']) + $datos['abono'];
            $nuevoSaldo = $saldo - $datos['abono'];
            $cancelado = $nuevoSaldo === 0;
            $this->pagos->actualizarCuenta((int) $cuenta['id'], $pagado, $nuevoSaldo);

            $abono = Dinero::texto($datos['abono']);
            $id = $this->pagos->insertarPago([
                'matricula_id' => (int) $cuenta['matricula_id'],
                'responsable_id' => (int) ($cuenta['responsable_id'] ?? $matricula['apoderado_id']),
                'concepto_id' => $datos['concepto_id'],
                'cuenta_pago_id' => (int) $cuenta['id'],
                'responsable' => $matricula['apoderado'],
                'concepto' => $concepto['descripcion'],
                'monto' => $abono,
                'monto_total' => Dinero::texto($total),
                'monto_pagado' => Dinero::texto($pagado),
                'saldo_restante' => Dinero::texto($nuevoSaldo),
                'abono_hoy' => $abono,
                'entregado_por' => $datos['entregado_por'],
                'fecha_pago' => $datos['fecha_pago'],
                'estado' => $cancelado ? 'Pagado' : 'Deuda',
                'estado_pago' => $cancelado ? 'pagado' : 'pendiente',
                'observacion' => $datos['observacion'],
                'registrado_por' => $this->contexto->usuarioId(),
            ]);
            if ($cancelado) {
                $this->pagos->marcarCuentaPagada((int) $cuenta['id']);
            }

            $this->auditor->registrar(
                'crear_pago',
                "Abono estudiante {$matricula['estudiante']}, apoderado {$matricula['apoderado']}, concepto {$concepto['descripcion']}: S/ {$abono}. Saldo: S/ " . Dinero::texto($nuevoSaldo) . '.'
            );
            return ['id' => $id, 'saldo_restante' => Dinero::decimal($nuevoSaldo), 'cancelado' => $cancelado];
        });
    }
}
