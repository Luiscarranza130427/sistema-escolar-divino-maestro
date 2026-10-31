<?php
declare(strict_types=1);

namespace App\Modulos\Matriculas;

use App\Http\Request;
use App\Http\Response;
use App\Modulos\Responsables\Contacto;
use App\Soporte\FiltroAcademico;
use App\Validacion\Validador;

final class MatriculaController
{
    public function __construct(private MatriculaService $servicio, private MatriculaRepository $matriculas)
    {
    }

    public function listar(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $anio = $v->presente('anio') && $v->crudo('anio') !== '' ? $v->entero('anio', 2020, 2100, 'El año no es válido.') : null;
        $filtro = FiltroAcademico::leer($v);
        $paginacion = $v->paginacion();
        $v->validar();
        return Response::pagina($this->matriculas->listar($v->crudo('q'), $anio, $filtro, $paginacion));
    }

    /**
     * Cuerpo: {estudiante_id? | estudiante{dni, nombres, apellido_paterno, apellido_materno}, hermano_id?,
     *          padre?, madre?, emergencia?, apoderado_principal: padre|madre|emergencia, anio, grado_seccion_id}
     */
    public function crear(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $estudianteId = $v->id('estudiante_id', false, 'Selecciona un estudiante existente válido.');
        $estudiante = null;
        if ($estudianteId === null) {
            $estudiante = $v->anidado('estudiante', static fn (Validador $e): array => [
                'dni' => $e->dni('dni', true, 'DNI del estudiante'),
                'nombres' => $e->texto('nombres', 100, true, true),
                'apellido_paterno' => $e->texto('apellido_paterno', 100, true, true),
                'apellido_materno' => $e->texto('apellido_materno', 100, true, true),
            ]);
            if ($estudiante === null) {
                $v->error('estudiante', 'Envía el estudiante_id de un estudiante existente o los datos del estudiante nuevo.');
            }
        }

        $contactos = array_filter([
            'padre' => $v->anidado('padre', static fn (Validador $h) => Contacto::leer($h)),
            'madre' => $v->anidado('madre', static fn (Validador $h) => Contacto::leer($h)),
            'emergencia' => $v->anidado('emergencia', static fn (Validador $h) => Contacto::leer($h, true, true)),
        ]);

        $datos = [
            'estudiante_id' => $estudianteId,
            'estudiante' => $estudiante,
            'hermano_id' => $v->id('hermano_id', false, 'Selecciona un hermano registrado válido.'),
            'contactos' => $contactos,
            'apoderado_principal' => (string) $v->opcion('apoderado_principal', array_keys(MatriculaService::PARENTESCOS)),
            'anio' => (int) $v->entero('anio', 2020, 2100, 'El año de matrícula no es válido.'),
            'grado_seccion_id' => (int) $v->id('grado_seccion_id', true, 'Selecciona el grado y la sección.'),
        ];
        $v->validar();
        return Response::creado(['id' => $this->servicio->registrar($datos)]);
    }
}
