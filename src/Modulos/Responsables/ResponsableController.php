<?php
declare(strict_types=1);

namespace App\Modulos\Responsables;

use App\Http\Request;
use App\Http\Response;
use App\Soporte\FiltroAcademico;
use App\Validacion\Validador;

final class ResponsableController
{
    public function __construct(private ResponsableService $servicio, private ResponsableRepository $responsables)
    {
    }

    public function listar(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $filtro = FiltroAcademico::leer($v);
        $paginacion = $v->paginacion();
        $v->validar();
        return Response::pagina($this->responsables->listar($v->crudo('q'), $filtro, $paginacion));
    }

    public function buscar(Request $peticion): Response
    {
        $termino = (new Validador($peticion->consulta()))->crudo('q');
        return Response::ok(mb_strlen($termino) < 2 ? [] : $this->responsables->buscarPorTexto($termino));
    }

    public function ver(Request $peticion): Response
    {
        return Response::ok($this->servicio->ficha($peticion->parametro('id')));
    }

    public function crear(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'dni' => $v->dni('dni'),
            'nombres' => $v->texto('nombres', 100, true, true),
            'apellido_paterno' => $v->texto('apellido_paterno', 100, true, true),
            'apellido_materno' => $v->textoONull('apellido_materno', 100, true),
            'nombre_padre_adicional' => $v->textoONull('nombre_padre_adicional', 150, true),
            'direccion' => $v->textoONull('direccion', 500, true),
            'celular_actual' => $v->celular('celular_actual', 'Celular actual'),
            'celular_padre' => $v->celular('celular_padre', 'Celular del padre'),
            'celular_madre' => $v->celular('celular_madre', 'Celular de la madre'),
            'nro_tarjeta_apafa' => $v->textoONull('nro_tarjeta_apafa', 30, true),
        ];
        $v->validar();
        return Response::creado(['id' => $this->servicio->crear($datos)]);
    }

    public function actualizar(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'principal' => [
                'dni' => $v->dni('dni'),
                'nombres' => $v->texto('nombres', 100, true, true),
                'apellido_paterno' => $v->texto('apellido_paterno', 100, true, true),
                'apellido_materno' => $v->textoONull('apellido_materno', 100, true),
                'direccion' => $v->textoONull('direccion', 500, true),
                'celular' => $v->celular('celular'),
            ],
            'parentesco' => $v->texto('parentesco', 60, false, true),
            'relacion_id' => $v->id('relacion_id', false),
            'estudiante_id' => $v->id('estudiante_id', false),
            'padre' => $v->anidado('padre', static fn (Validador $h) => Contacto::leer($h)),
            'madre' => $v->anidado('madre', static fn (Validador $h) => Contacto::leer($h)),
            'emergencia' => $v->anidado('emergencia', static fn (Validador $h) => Contacto::leer($h, true)),
        ];
        $v->validar();
        return Response::ok($this->servicio->actualizar($peticion->parametro('id'), $datos));
    }

    public function candidatosApoderado(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $estudianteId = $v->id('estudiante_id');
        $v->validar();
        return Response::ok($this->servicio->candidatosApoderado($peticion->parametro('id'), (int) $estudianteId));
    }

    public function eliminar(Request $peticion): Response
    {
        $this->servicio->eliminar($peticion->parametro('id'));
        return Response::sinContenido();
    }

    /** DELETE /responsables/{id}/estudiantes/{estudiante}?reemplazo_id= */
    public function desvincular(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $reemplazoId = $v->id('reemplazo_id', false);
        $v->validar();
        $this->servicio->desvincular($peticion->parametro('id'), $peticion->parametro('estudiante'), $reemplazoId);
        return Response::sinContenido();
    }
}
