<?php
declare(strict_types=1);

namespace App\Modulos\Pagos;

use App\Core\Database;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;
use App\Soporte\Dinero;

final class ConceptoService
{
    /** estudiante: una cuenta por matrícula; familia: una cuenta compartida por apoderado y año. */
    public const TIPOS_COBRO = ['estudiante', 'familia'];

    public function __construct(private Database $db, private ConceptoRepository $conceptos, private Auditor $auditor)
    {
    }

    /** @param array{descripcion: string, monto: int, tipo_cobro: string} $datos */
    public function crear(array $datos): int
    {
        if ($this->conceptos->duplicado($datos['descripcion'])) {
            throw HttpException::conflicto('Ese concepto ya existe.');
        }
        $id = $this->conceptos->crear($datos);
        $this->auditor->registrar('crear_concepto_pago', "Creó el concepto de pago {$datos['descripcion']} (S/ " . Dinero::texto($datos['monto']) . ').');
        return $id;
    }

    public function actualizar(int $id, array $datos): void
    {
        if ($this->conceptos->duplicado($datos['descripcion'], $id)) {
            throw HttpException::conflicto('Ya existe otro concepto con ese nombre.');
        }
        if (!$this->conceptos->actualizar($id, $datos)) {
            throw HttpException::noEncontrado('No se encontró el concepto.');
        }
        $this->auditor->registrar('editar_concepto_pago', "Actualizó el concepto de pago ID {$id} a {$datos['descripcion']} (S/ " . Dinero::texto($datos['monto']) . ').');
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        if (!$this->conceptos->cambiarEstado($id, $activo)) {
            throw HttpException::noEncontrado('No se encontró el concepto.');
        }
        $this->auditor->registrar('editar_concepto_pago', ($activo ? 'Activó' : 'Desactivó') . " el concepto de pago ID {$id}.");
    }

    public function eliminar(int $id): void
    {
        $this->db->transaccion(function () use ($id): void {
            $descripcion = $this->conceptos->bloquearDescripcion($id) ?? throw HttpException::noEncontrado('No se encontró el concepto.');
            if ($this->conceptos->enUso($id)) {
                throw HttpException::conflicto('No se puede eliminar: el concepto está en uso por pagos o deudas. Desactívalo en su lugar.');
            }
            $this->conceptos->eliminar($id);
            $this->auditor->registrar('eliminar_concepto_pago', "Eliminó el concepto de pago {$descripcion} (ID {$id}).");
        });
    }
}
