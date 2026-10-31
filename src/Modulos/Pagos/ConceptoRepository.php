<?php
declare(strict_types=1);

namespace App\Modulos\Pagos;

use App\Core\Database;
use App\Soporte\Booleano;
use App\Soporte\Dinero;

final class ConceptoRepository
{
    public function __construct(private Database $db)
    {
    }

    public function listar(bool $todos): array
    {
        $sql = 'SELECT id, descripcion, monto_default, tipo_cobro, activo FROM concepto_pago';
        $sql .= $todos ? ' ORDER BY activo DESC, descripcion' : ' WHERE activo = TRUE ORDER BY descripcion';
        return array_map(static fn (array $fila) => Booleano::normalizar($fila, ['activo']), $this->db->filas($sql));
    }

    public function activo(int $id): ?array
    {
        return $this->db->fila(
            'SELECT id, descripcion, monto_default, tipo_cobro FROM concepto_pago WHERE id = :id AND activo = TRUE',
            ['id' => $id]
        );
    }

    public function duplicado(string $descripcion, int $exceptoId = 0): bool
    {
        return $this->db->valor(
            'SELECT 1 FROM concepto_pago WHERE LOWER(descripcion) = LOWER(:descripcion) AND id <> :id',
            ['descripcion' => $descripcion, 'id' => $exceptoId]
        ) !== null;
    }

    /** @param array{descripcion: string, monto: int, tipo_cobro: string} $datos monto en céntimos */
    public function crear(array $datos): int
    {
        return $this->db->insertar(
            'INSERT INTO concepto_pago (descripcion, monto_total, monto_default, tipo_cobro, activo)
             VALUES (:descripcion, :monto_total, :monto_default, :tipo_cobro, TRUE)
             RETURNING id',
            self::parametros($datos)
        );
    }

    public function actualizar(int $id, array $datos): bool
    {
        return $this->db->afectar(
            'UPDATE concepto_pago
             SET descripcion = :descripcion, monto_total = :monto_total, monto_default = :monto_default, tipo_cobro = :tipo_cobro
             WHERE id = :id',
            self::parametros($datos) + ['id' => $id]
        ) === 1;
    }

    public function cambiarEstado(int $id, bool $activo): bool
    {
        return $this->db->afectar('UPDATE concepto_pago SET activo = :activo WHERE id = :id', ['activo' => $activo, 'id' => $id]) === 1;
    }

    public function bloquearDescripcion(int $id): ?string
    {
        $descripcion = $this->db->valor('SELECT descripcion FROM concepto_pago WHERE id = :id FOR UPDATE', ['id' => $id]);
        return $descripcion === null ? null : (string) $descripcion;
    }

    public function enUso(int $id): bool
    {
        return $this->db->valor(
            'SELECT 1 WHERE EXISTS (SELECT 1 FROM pago WHERE concepto_id = :pago_concepto)
                         OR EXISTS (SELECT 1 FROM cuenta_pago WHERE concepto_id = :cuenta_concepto)',
            ['pago_concepto' => $id, 'cuenta_concepto' => $id]
        ) !== null;
    }

    public function eliminar(int $id): void
    {
        $this->db->ejecutar('DELETE FROM concepto_pago WHERE id = :id', ['id' => $id]);
    }

    private static function parametros(array $datos): array
    {
        $monto = Dinero::texto($datos['monto']);
        return [
            'descripcion' => $datos['descripcion'],
            'monto_total' => $monto,
            'monto_default' => $monto,
            'tipo_cobro' => $datos['tipo_cobro'],
        ];
    }
}
