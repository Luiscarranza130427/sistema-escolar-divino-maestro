<?php
declare(strict_types=1);

namespace App\Modulos\Responsables;

use App\Core\Database;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;

/** Reglas de negocio de padres, madres, apoderados y contactos de emergencia. */
final class ResponsableService
{
    public const TIPOS_FAMILIARES = ['padre', 'madre'];
    public const TIPOS_EMERGENCIA = ['emergencia', 'otro'];

    public function __construct(private Database $db, private ResponsableRepository $responsables, private Auditor $auditor)
    {
    }

    public function obtener(int $id): array
    {
        return $this->responsables->buscar($id) ?? throw HttpException::noEncontrado('No se encontró al responsable.');
    }

    public function ficha(int $id): array
    {
        $responsable = $this->obtener($id);
        $nombre = trim(implode(' ', array_filter([$responsable['nombres'], $responsable['apellido_paterno'], $responsable['apellido_materno']])));
        return [
            'responsable' => $responsable,
            'estudiantes' => $this->responsables->estudiantesDe($id),
            'pagos' => $this->responsables->pagosDe($id, $nombre),
        ];
    }

    /** @param array<string, ?string> $datos */
    public function crear(array $datos): int
    {
        if ($this->responsables->existeDni((string) $datos['dni'])) {
            throw HttpException::conflicto('Ya existe un responsable con ese DNI.');
        }
        $id = $this->responsables->crear($datos);
        $this->auditor->registrar('crear_apoderado', "Registró el apoderado con DNI {$datos['dni']}.");
        return $id;
    }

    /**
     * Actualiza al responsable y, si se indica el estudiante y la relación, también al padre,
     * la madre y el contacto de emergencia de ese estudiante.
     * @param array{principal: array<string, ?string>, parentesco: string, relacion_id: ?int, estudiante_id: ?int, padre: ?array, madre: ?array, emergencia: ?array} $datos
     */
    public function actualizar(int $id, array $datos): array
    {
        $estudianteId = $datos['estudiante_id'];
        $tieneFamilia = $datos['padre'] !== null || $datos['madre'] !== null || $datos['emergencia'] !== null;
        if ($tieneFamilia && ($estudianteId === null || $datos['relacion_id'] === null)) {
            throw HttpException::regla('Para guardar la familia indica estudiante_id y relacion_id del apoderado.', 'estudiante_id');
        }
        if ($this->responsables->existeDni((string) $datos['principal']['dni'], $id)) {
            throw HttpException::conflicto('Ya existe otro responsable con ese DNI.');
        }

        $this->db->transaccion(function () use ($id, $datos, $estudianteId): void {
            if (!$this->responsables->actualizarPrincipal($id, $datos['principal'])) {
                throw HttpException::noEncontrado('No se encontró al responsable.');
            }
            if ($datos['relacion_id'] !== null
                && !$this->responsables->actualizarParentesco($datos['relacion_id'], $id, $datos['parentesco'], $estudianteId)) {
                throw HttpException::conflicto('La relación del apoderado con el estudiante ya no existe.');
            }
            foreach (self::TIPOS_FAMILIARES as $tipo) {
                if ($datos[$tipo] !== null) {
                    $this->guardarFamiliar((int) $estudianteId, $tipo, $datos[$tipo]);
                }
            }
            if ($datos['emergencia'] !== null) {
                $this->guardarEmergencia((int) $estudianteId, $datos['emergencia']);
            }
            $this->auditor->registrar('editar_apoderado', "Actualizó datos del apoderado ID {$id} y su familia.");
        });

        return $this->ficha($id);
    }

    /** Padre o madre: actualiza el existente (id + relacion_id) o crea/vincula por DNI. */
    public function guardarFamiliar(int $estudianteId, string $tipo, array $contacto): void
    {
        $parentesco = $contacto['parentesco'] !== '' ? $contacto['parentesco'] : mb_strtoupper($tipo, 'UTF-8');

        if ($contacto['id'] !== null) {
            if ($contacto['relacion_id'] === null) {
                throw HttpException::regla("El vínculo familiar del {$tipo} no es válido.", "{$tipo}.relacion_id");
            }
            if (!$this->responsables->actualizarContacto($contacto['id'], $contacto)) {
                throw HttpException::noEncontrado("No se encontró al {$tipo}.");
            }
            if (!$this->responsables->actualizarParentesco($contacto['relacion_id'], $contacto['id'], $parentesco, $estudianteId, [$tipo])) {
                throw HttpException::conflicto("El vínculo familiar del {$tipo} ya no existe.");
            }
            return;
        }

        $responsableId = $this->responsables->guardarPorDni($contacto);
        $tipoExistente = $this->responsables->tipoVinculo($estudianteId, $responsableId);
        if ($tipoExistente !== null && $tipoExistente !== $tipo) {
            throw HttpException::conflicto('Ese DNI ya está vinculado al estudiante con otro parentesco.');
        }
        $this->responsables->vincular($estudianteId, $responsableId, $tipo, $parentesco);
    }

    public function guardarEmergencia(int $estudianteId, array $contacto): void
    {
        if ($contacto['id'] !== null) {
            if ($contacto['relacion_id'] === null) {
                throw HttpException::regla('El contacto de emergencia no pertenece a esta familia.', 'emergencia.relacion_id');
            }
            if (!$this->responsables->actualizarContacto($contacto['id'], $contacto)) {
                throw HttpException::noEncontrado('No se encontró el contacto de emergencia.');
            }
            if (!$this->responsables->actualizarParentesco($contacto['relacion_id'], $contacto['id'], $contacto['parentesco'], $estudianteId, self::TIPOS_EMERGENCIA)) {
                throw HttpException::conflicto('La relación del contacto de emergencia con el estudiante ya no existe.');
            }
            return;
        }

        $responsableId = $this->responsables->guardarPorDni($contacto);
        if (in_array($this->responsables->tipoVinculo($estudianteId, $responsableId), self::TIPOS_FAMILIARES, true)) {
            throw HttpException::conflicto('Ese contacto ya está registrado como padre o madre del estudiante.');
        }
        $this->responsables->vincular($estudianteId, $responsableId, 'emergencia', $contacto['parentesco']);
    }

    public function candidatosApoderado(int $id, int $estudianteId): array
    {
        $this->obtener($id);
        return $this->responsables->candidatosApoderado($estudianteId, $id);
    }

    /** Elimina un responsable sin estudiantes vinculados ni historial (pagos o matrículas). */
    public function eliminar(int $id): void
    {
        $this->db->transaccion(function () use ($id): void {
            $nombre = $this->responsables->nombreCompleto($id) ?? throw HttpException::noEncontrado('No se encontró al responsable.');
            if ($this->responsables->tieneEstudiantes($id)) {
                throw HttpException::conflicto('No se puede eliminar: tiene estudiantes vinculados. Desvincúlalo primero de cada estudiante.');
            }
            if ($this->responsables->tieneHistorial($id)) {
                throw HttpException::conflicto('No se puede eliminar: figura en pagos o matrículas registradas.');
            }
            $this->responsables->eliminar($id);
            $this->auditor->registrar('eliminar_apoderado', "Eliminó al responsable {$nombre} (ID {$id}).");
        });
    }

    /**
     * Desvincula al responsable de un estudiante. Si era el apoderado principal exige un reemplazo
     * de la misma familia. Si ya no le quedan estudiantes, el responsable se elimina.
     */
    public function desvincular(int $id, int $estudianteId, ?int $reemplazoId): void
    {
        $this->db->transaccion(function () use ($id, $estudianteId, $reemplazoId): void {
            $nombre = $this->responsables->nombreCompleto($id) ?? throw HttpException::noEncontrado('No se encontró al responsable.');
            if (!$this->responsables->bloquearEstudiante($estudianteId)) {
                throw HttpException::noEncontrado('No se encontró al estudiante.');
            }
            $vinculo = $this->responsables->vinculoBloqueado($estudianteId, $id)
                ?? throw HttpException::noEncontrado('El responsable no está vinculado a ese estudiante.');
            if ($this->responsables->cantidadResponsables($estudianteId) === 1) {
                throw HttpException::conflicto('No se puede quitar: es el único responsable del estudiante.');
            }

            if ($vinculo['es_apoderado']) {
                if ($reemplazoId === null || $reemplazoId === $id) {
                    throw HttpException::regla('Es el apoderado principal: elige quién lo reemplaza (reemplazo_id).', 'reemplazo_id');
                }
                if ($this->responsables->vinculoBloqueado($estudianteId, $reemplazoId) === null) {
                    throw HttpException::regla('El reemplazo elegido no pertenece a la familia del estudiante.', 'reemplazo_id');
                }
                $this->responsables->traspasarApoderado($estudianteId, $id, $reemplazoId);
            } elseif ($reemplazoId !== null) {
                throw HttpException::regla('El reemplazo solo aplica si el responsable es el apoderado principal.', 'reemplazo_id');
            }

            $this->responsables->desvincular($estudianteId, $id);
            $this->responsables->eliminarSiNoTieneVinculos($id);
            $this->auditor->registrar('eliminar_apoderado', "Desvinculó al apoderado {$nombre} (ID {$id}) del estudiante ID {$estudianteId}.");
        });
    }
}
