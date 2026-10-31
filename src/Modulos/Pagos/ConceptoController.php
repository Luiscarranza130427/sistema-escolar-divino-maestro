<?php
declare(strict_types=1);

namespace App\Modulos\Pagos;

use App\Http\Request;
use App\Http\Response;
use App\Validacion\Validador;

final class ConceptoController
{
    public function __construct(private ConceptoService $servicio, private ConceptoRepository $conceptos)
    {
    }

    public function listar(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $todos = (bool) $v->booleano('todos', false);
        $v->validar();
        return Response::ok($this->conceptos->listar($todos));
    }

    public function crear(Request $peticion): Response
    {
        return Response::creado(['id' => $this->servicio->crear(self::leer($peticion))]);
    }

    public function actualizar(Request $peticion): Response
    {
        $this->servicio->actualizar($peticion->parametro('id'), self::leer($peticion));
        return Response::ok(['id' => $peticion->parametro('id')]);
    }

    public function cambiarEstado(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $activo = $v->booleano('activo');
        $v->validar();
        $this->servicio->cambiarEstado($peticion->parametro('id'), (bool) $activo);
        return Response::ok(['activo' => $activo]);
    }

    public function eliminar(Request $peticion): Response
    {
        $this->servicio->eliminar($peticion->parametro('id'));
        return Response::sinContenido();
    }

    private static function leer(Request $peticion): array
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'descripcion' => $v->texto('descripcion', 80, true, false, 'Concepto'),
            'monto' => $v->centimos('monto', 'Monto del concepto'),
            'tipo_cobro' => (string) $v->opcion('tipo_cobro', ConceptoService::TIPOS_COBRO, 'estudiante'),
        ];
        $v->validar();
        return $datos;
    }
}
