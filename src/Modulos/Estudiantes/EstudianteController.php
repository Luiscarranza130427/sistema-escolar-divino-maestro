<?php
declare(strict_types=1);

namespace App\Modulos\Estudiantes;

use App\Http\Request;
use App\Http\Response;
use App\Soporte\FiltroAcademico;
use App\Validacion\Validador;

final class EstudianteController
{
    public function __construct(private EstudianteService $servicio, private EstudianteRepository $estudiantes)
    {
    }

    public function listar(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $estado = $v->opcion('estado', EstudianteService::ESTADOS, 'Activo');
        $filtro = FiltroAcademico::leer($v);
        $paginacion = $v->paginacion();
        $v->validar();
        return Response::pagina($this->estudiantes->listar((string) $estado, $v->crudo('q'), $filtro, $paginacion));
    }

    public function buscar(Request $peticion): Response
    {
        $termino = (new Validador($peticion->consulta()))->crudo('q');
        return Response::ok(mb_strlen($termino) < 2 ? [] : $this->estudiantes->buscarPorTexto($termino));
    }

    public function ver(Request $peticion): Response
    {
        return Response::ok($this->servicio->ficha($peticion->parametro('id')));
    }

    public function datosMatricula(Request $peticion): Response
    {
        return Response::ok($this->servicio->datosMatricula($peticion->parametro('id')));
    }

    public function actualizar(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'dni' => $v->dni('dni'),
            'nombres' => $v->texto('nombres', 100, true, true),
            'apellido_paterno' => $v->texto('apellido_paterno', 100, true, true),
            'apellido_materno' => $v->texto('apellido_materno', 100, true, true),
            'fecha_nacimiento' => $v->fecha('fecha_nacimiento', false, 'Fecha de nacimiento'),
        ];
        $v->validar();
        return Response::ok($this->servicio->actualizar($peticion->parametro('id'), $datos));
    }

    public function cambiarEstado(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $estado = $v->opcion('estado_academico', EstudianteService::ESTADOS);
        $v->validar();
        $this->servicio->cambiarEstado($peticion->parametro('id'), (string) $estado);
        return Response::ok(['estado_academico' => $estado]);
    }

    public function eliminar(Request $peticion): Response
    {
        $this->servicio->eliminar($peticion->parametro('id'));
        return Response::sinContenido();
    }
}
