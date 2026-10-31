<?php
declare(strict_types=1);

namespace App\Modulos\Academico;

use App\Http\Request;
use App\Http\Response;
use App\Validacion\Validador;

final class AcademicoController
{
    public function __construct(private AcademicoService $servicio, private AcademicoRepository $academico)
    {
    }

    public function cursos(): Response
    {
        return Response::ok($this->academico->cursos());
    }

    public function crearCurso(Request $peticion): Response
    {
        return Response::creado(['id' => $this->academico->crearCurso(self::leerCurso($peticion))]);
    }

    public function actualizarCurso(Request $peticion): Response
    {
        $this->servicio->actualizarCurso($peticion->parametro('id'), self::leerCurso($peticion));
        return Response::ok(['id' => $peticion->parametro('id')]);
    }

    public function desactivarCurso(Request $peticion): Response
    {
        $this->servicio->desactivarCurso($peticion->parametro('id'));
        return Response::sinContenido();
    }

    public function periodos(): Response
    {
        return Response::ok($this->academico->periodos());
    }

    public function crearPeriodo(Request $peticion): Response
    {
        return Response::creado(['id' => $this->academico->crearPeriodo(self::leerPeriodo($peticion))]);
    }

    public function actualizarPeriodo(Request $peticion): Response
    {
        $this->servicio->actualizarPeriodo($peticion->parametro('id'), self::leerPeriodo($peticion));
        return Response::ok(['id' => $peticion->parametro('id')]);
    }

    public function desactivarPeriodo(Request $peticion): Response
    {
        $this->servicio->desactivarPeriodo($peticion->parametro('id'));
        return Response::sinContenido();
    }

    public function calificaciones(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $cursoId = (int) $v->id('curso_id');
        $periodoId = (int) $v->id('periodo_id');
        $gradoSeccionId = $v->id('grado_seccion_id', false);
        $v->validar();
        return Response::ok($this->servicio->calificaciones($cursoId, $periodoId, $gradoSeccionId));
    }

    /** Cuerpo: {curso_id, periodo_id, notas: {"<matricula_id>": "15.5", ...}}. Las notas vacías se ignoran. */
    public function guardarCalificaciones(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $cursoId = (int) $v->id('curso_id');
        $periodoId = (int) $v->id('periodo_id');
        $notas = [];
        foreach ($v->arreglo('notas', 'Envía las notas como un objeto {matricula_id: nota}.') as $matriculaId => $nota) {
            $campo = "notas.{$matriculaId}";
            $id = filter_var($matriculaId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $texto = is_scalar($nota) ? trim((string) $nota) : null;
            if ($id === false) {
                $v->error($campo, 'La matrícula no es válida.');
            } elseif ($texto === null || ($texto !== '' && !self::notaValida($texto))) {
                $v->error($campo, 'La nota debe estar entre 0 y 20, con hasta dos decimales.');
            } elseif ($texto !== '') {
                $notas[$id] = $texto;
            }
        }
        $v->validar();
        return Response::ok(['guardadas' => $this->servicio->guardarCalificaciones($cursoId, $periodoId, $notas)]);
    }

    private static function notaValida(string $nota): bool
    {
        return preg_match('/^[0-9]{1,2}([.][0-9]{1,2})?$/', $nota) === 1 && (float) $nota <= 20;
    }

    private static function leerCurso(Request $peticion): array
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'nombre' => $v->texto('nombre', 100, true, false, 'Nombre del curso'),
            'grado' => $v->texto('grado', 50, true, false, 'Grado'),
        ];
        $v->validar();
        return $datos;
    }

    private static function leerPeriodo(Request $peticion): array
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'nombre' => $v->texto('nombre', 50, true, false, 'Nombre del periodo'),
            'fecha_inicio' => $v->fecha('fecha_inicio', true, 'Fecha de inicio'),
            'fecha_fin' => $v->fecha('fecha_fin', true, 'Fecha de fin'),
        ];
        if ($datos['fecha_inicio'] !== null && $datos['fecha_fin'] !== null && $datos['fecha_inicio'] > $datos['fecha_fin']) {
            $v->error('fecha_fin', 'La fecha de fin debe ser igual o posterior a la de inicio.');
        }
        $v->validar();
        return $datos;
    }
}
