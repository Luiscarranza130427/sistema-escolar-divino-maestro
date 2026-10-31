<?php
declare(strict_types=1);

namespace App\Modulos\Estudiantes;

use App\Core\Database;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;
use PDOException;

final class EstudianteService
{
    public const ESTADOS = ['Activo', 'Retirado', 'Trasladado'];

    public function __construct(private Database $db, private EstudianteRepository $estudiantes, private Auditor $auditor)
    {
    }

    public function obtener(int $id): array
    {
        return $this->estudiantes->buscar($id) ?? throw HttpException::noEncontrado('No se encontró al estudiante.');
    }

    /** Ficha completa: datos, responsables, matrículas, hermanos y pagos. */
    public function ficha(int $id): array
    {
        return [
            'estudiante' => $this->obtener($id),
            'responsables' => $this->estudiantes->responsables($id),
            'matriculas' => $this->estudiantes->matriculas($id),
            'hermanos' => $this->estudiantes->hermanos($id),
            'pagos' => $this->estudiantes->pagos($id),
        ];
    }

    /** Datos para precargar el formulario de una nueva matrícula del estudiante. */
    public function datosMatricula(int $id): array
    {
        $estudiante = $this->obtener($id);
        $ultima = $this->estudiantes->ultimaMatricula($id);
        $apoderadoId = isset($ultima['apoderado_principal_id']) ? (int) $ultima['apoderado_principal_id'] : null;

        return [
            'estudiante' => array_intersect_key($estudiante, array_flip(['id', 'dni', 'nombres', 'apellido_paterno', 'apellido_materno'])),
            'familia' => [
                'padre' => $this->estudiantes->contactoPorTipos($id, ['padre'], $apoderadoId),
                'madre' => $this->estudiantes->contactoPorTipos($id, ['madre'], $apoderadoId),
                'emergencia' => $this->estudiantes->contactoPorTipos($id, ['emergencia', 'otro'], $apoderadoId),
            ],
            'ultima_matricula' => $ultima,
        ];
    }

    /** @param array{dni: string, nombres: string, apellido_paterno: string, apellido_materno: string, fecha_nacimiento: ?string} $datos */
    public function actualizar(int $id, array $datos): array
    {
        $this->obtener($id);
        if ($this->estudiantes->dniRegistrado($datos['dni'], $id)) {
            throw HttpException::conflicto('Ya existe otro estudiante con ese DNI.');
        }
        $this->estudiantes->actualizar($id, $datos);
        $this->auditor->registrar('editar_estudiante', "Actualizó los datos del estudiante ID {$id} (DNI {$datos['dni']}).");
        return $this->obtener($id);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        if (!$this->estudiantes->cambiarEstado($id, $estado)) {
            throw HttpException::noEncontrado('No se encontró al estudiante.');
        }
        $this->auditor->registrar('actualizar_estado_academico', "Actualizó estado del estudiante ID {$id} a {$estado}.");
    }

    /** Solo se elimina si no tiene pagos ni notas; sus matrículas y vínculos se borran en cascada. */
    public function eliminar(int $id): void
    {
        $this->db->transaccion(function () use ($id): void {
            $nombre = $this->estudiantes->bloquearNombre($id) ?? throw HttpException::noEncontrado('No se encontró al estudiante.');
            if ($this->estudiantes->tieneDependencias($id)) {
                throw HttpException::conflicto('No se puede eliminar: el estudiante tiene pagos o notas registradas. Cambia su estado a Retirado.');
            }
            try {
                $this->estudiantes->eliminar($id);
            } catch (PDOException $error) {
                if ((string) $error->getCode() === '23503') {
                    throw HttpException::conflicto('No se puede eliminar: el estudiante tiene datos vinculados.');
                }
                throw $error;
            }
            $this->auditor->registrar('eliminar_estudiante', "Eliminó al estudiante: {$nombre} (ID {$id}).");
        });
    }
}
