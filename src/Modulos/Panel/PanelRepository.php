<?php
declare(strict_types=1);

namespace App\Modulos\Panel;

use App\Core\Database;
use App\Soporte\Dinero;

final class PanelRepository
{
    public function __construct(private Database $db)
    {
    }

    /** @return array{estudiantes_activos: int, ingresos_mes: float, deudas_pendientes: int} */
    public function metricas(): array
    {
        $fila = $this->db->fila(
            "SELECT
                (SELECT COUNT(DISTINCT estudiante_id) FROM matricula WHERE estado = 'Activa') AS estudiantes_activos,
                (SELECT COALESCE(SUM(monto), 0) FROM pago
                  WHERE fecha_pago >= date_trunc('month', CURRENT_DATE)::date
                    AND fecha_pago < (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month')::date) AS ingresos_mes,
                (SELECT COUNT(*) FROM cuenta_pago WHERE saldo_restante > 0) AS deudas_pendientes"
        ) ?? [];
        return [
            'estudiantes_activos' => (int) ($fila['estudiantes_activos'] ?? 0),
            'ingresos_mes' => Dinero::decimal(Dinero::aCentimos($fila['ingresos_mes'] ?? 0)),
            'deudas_pendientes' => (int) ($fila['deudas_pendientes'] ?? 0),
        ];
    }

    /**
     * Datos para los gráficos del panel: ingresos de los últimos 6 meses, alumnos por nivel y grado,
     * avance de cobranza, ingresos del año por concepto y los últimos pagos.
     * @return array<string, mixed>
     */
    public function analiticas(): array
    {
        $ingresos = $this->db->filas(
            "SELECT to_char(mes, 'YYYY-MM') AS mes, COALESCE(SUM(p.monto), 0) AS total, COUNT(p.id) AS pagos
             FROM generate_series(date_trunc('month', CURRENT_DATE) - INTERVAL '5 months', date_trunc('month', CURRENT_DATE), INTERVAL '1 month') AS mes
             LEFT JOIN pago p ON p.fecha_pago >= mes::date AND p.fecha_pago < (mes + INTERVAL '1 month')::date
             GROUP BY mes ORDER BY mes"
        );
        $porGrado = $this->db->filas(
            "SELECT n.nombre AS nivel, g.numero AS grado, g.nombre AS grado_nombre, COUNT(DISTINCT m.estudiante_id) AS total
             FROM matricula m
             JOIN nivel n ON n.id = m.nivel_id
             JOIN grado_seccion gs ON gs.id = m.grado_seccion_id
             JOIN grado g ON g.id = gs.grado_id
             WHERE m.estado = 'Activa'
             GROUP BY n.id, n.nombre, g.numero, g.nombre
             ORDER BY n.id, g.numero"
        );
        $cobranza = $this->db->fila(
            'SELECT COALESCE(SUM(monto_total), 0) AS total, COALESCE(SUM(monto_pagado), 0) AS pagado,
                    COALESCE(SUM(saldo_restante), 0) AS saldo
             FROM cuenta_pago'
        ) ?? [];
        $porConcepto = $this->db->filas(
            "SELECT concepto, SUM(monto) AS total, COUNT(*) AS pagos
             FROM pago
             WHERE fecha_pago >= date_trunc('year', CURRENT_DATE)::date
             GROUP BY concepto ORDER BY SUM(monto) DESC LIMIT 6"
        );
        $recientes = $this->db->filas(
            'SELECT id, responsable, concepto, monto, fecha_pago FROM pago ORDER BY fecha_pago DESC, id DESC LIMIT 5'
        );

        $dinero = static fn (mixed $v): float => Dinero::decimal(Dinero::aCentimos($v ?? 0));
        $niveles = [];
        foreach ($porGrado as $fila) {
            $niveles[$fila['nivel']] = ($niveles[$fila['nivel']] ?? 0) + (int) $fila['total'];
        }

        return [
            'ingresos_mensuales' => array_map(static fn (array $f): array => ['mes' => $f['mes'], 'total' => $dinero($f['total']), 'pagos' => (int) $f['pagos']], $ingresos),
            'por_nivel' => array_map(static fn (string $n, int $t): array => ['nivel' => $n, 'total' => $t], array_keys($niveles), array_values($niveles)),
            'por_grado' => array_map(static fn (array $f): array => ['nivel' => $f['nivel'], 'grado' => $f['grado_nombre'], 'total' => (int) $f['total']], $porGrado),
            'cobranza' => ['total' => $dinero($cobranza['total'] ?? 0), 'pagado' => $dinero($cobranza['pagado'] ?? 0), 'saldo' => $dinero($cobranza['saldo'] ?? 0)],
            'por_concepto' => array_map(static fn (array $f): array => ['concepto' => $f['concepto'], 'total' => $dinero($f['total']), 'pagos' => (int) $f['pagos']], $porConcepto),
            'recientes' => array_map(static fn (array $f): array => [
                'id' => (int) $f['id'], 'responsable' => $f['responsable'], 'concepto' => $f['concepto'],
                'monto' => $dinero($f['monto']), 'fecha_pago' => $f['fecha_pago'],
            ], $recientes),
        ];
    }
}
