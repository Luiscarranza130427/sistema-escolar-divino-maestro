<?php
declare(strict_types=1);

namespace App\Modulos\Matriculas;

use App\Auth\Contexto;
use App\Core\Database;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;
use App\Modulos\Catalogos\CatalogoRepository;
use App\Modulos\Estudiantes\EstudianteRepository;
use App\Modulos\Responsables\ResponsableRepository;

/**
 * Registro de matrícula: estudiante nuevo o existente, su familia y el apoderado principal.
 * Todo ocurre en una sola transacción.
 */
final class MatriculaService
{
    /** Parentesco fijo por tipo de contacto (el de emergencia lo indica el usuario). */
    public const PARENTESCOS = ['padre' => 'PADRE', 'madre' => 'MADRE', 'emergencia' => null];

    public function __construct(
        private Database $db,
        private MatriculaRepository $matriculas,
        private EstudianteRepository $estudiantes,
        private ResponsableRepository $responsables,
        private CatalogoRepository $catalogos,
        private Contexto $contexto,
        private Auditor $auditor
    ) {
    }

    /**
     * @param array{estudiante_id: ?int, estudiante: ?array, hermano_id: ?int, contactos: array<string, array>, apoderado_principal: string, anio: int, grado_seccion_id: int} $datos
     */
    public function registrar(array $datos): int
    {
        $estudianteId = $datos['estudiante_id'];
        if ($datos['hermano_id'] !== null && $estudianteId !== null) {
            throw HttpException::regla('El vínculo con un hermano solo se usa al registrar un estudiante nuevo.', 'hermano_id');
        }
        if ($estudianteId === null && $datos['contactos'] === []) {
            throw HttpException::regla('Registra al menos un padre, madre o contacto de emergencia.', 'padre');
        }
        if (!isset($datos['contactos'][$datos['apoderado_principal']])) {
            throw HttpException::regla('El apoderado principal debe ser uno de los contactos enviados (padre, madre o emergencia).', 'apoderado_principal');
        }

        $anioId = $this->catalogos->anioActivo($datos['anio']) ?? throw HttpException::regla('El año de matrícula no está habilitado.', 'anio');
        $academico = $this->catalogos->gradoSeccionActiva($datos['grado_seccion_id'])
            ?? throw HttpException::regla('El grado o la sección no están habilitados.', 'grado_seccion_id');

        $id = $this->db->transaccion(function () use ($datos, $estudianteId, $anioId, $academico): int {
            if ($estudianteId === null) {
                if ($this->estudiantes->dniRegistrado($datos['estudiante']['dni'])) {
                    throw HttpException::conflicto('Ya existe un estudiante con ese DNI. Búscalo y matricúlalo como estudiante existente.');
                }
                $estudianteId = $this->estudiantes->crear($datos['estudiante']);
            } elseif ($this->estudiantes->buscar($estudianteId) === null) {
                throw HttpException::noEncontrado('No se encontró al estudiante.');
            }

            if ($this->matriculas->existeEnAnio($estudianteId, $anioId)) {
                throw HttpException::conflicto('El estudiante ya tiene una matrícula registrada para ese año.');
            }

            $this->responsables->quitarApoderados($estudianteId);
            $apoderadoId = null;
            foreach ($datos['contactos'] as $tipo => $contacto) {
                $responsableId = $this->responsables->guardarPorDni($contacto);
                $esPrincipal = $tipo === $datos['apoderado_principal'];
                $parentesco = self::PARENTESCOS[$tipo] ?? ($contacto['parentesco'] ?: 'CONTACTO DE EMERGENCIA');
                $this->responsables->vincular($estudianteId, $responsableId, $tipo, $parentesco, $esPrincipal);
                if ($esPrincipal) {
                    $apoderadoId = $responsableId;
                }
            }

            if ($datos['hermano_id'] !== null) {
                if ($datos['hermano_id'] === $estudianteId) {
                    throw HttpException::regla('El estudiante no puede vincularse como su propio hermano.', 'hermano_id');
                }
                if (!$this->matriculas->compartenResponsable($datos['hermano_id'], (int) $apoderadoId)) {
                    throw HttpException::regla('El apoderado principal no coincide con la familia del hermano seleccionado.', 'hermano_id');
                }
            }

            return $this->matriculas->crear([
                'estudiante_id' => $estudianteId,
                'anio_id' => $anioId,
                'nivel_id' => (int) $academico['nivel_id'],
                'grado_seccion_id' => (int) $academico['id'],
                'apoderado_principal_id' => $apoderadoId,
                'registrado_por' => $this->contexto->usuarioId(),
            ]);
        });

        $dni = $datos['estudiante']['dni'] ?? "ID {$estudianteId}";
        $this->auditor->registrar('crear_matricula', "Registró matrícula para el estudiante {$dni} del año {$datos['anio']}.");
        return $id;
    }
}
