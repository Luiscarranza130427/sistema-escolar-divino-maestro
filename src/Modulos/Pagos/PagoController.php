<?php
declare(strict_types=1);

namespace App\Modulos\Pagos;

use App\Http\Request;
use App\Http\Response;
use App\Validacion\Validador;

final class PagoController
{
    public function __construct(private PagoService $servicio, private PagoRepository $pagos)
    {
    }

    public function listar(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $paginacion = $v->paginacion();
        $v->validar();
        return Response::pagina($this->pagos->listar($v->crudo('q'), $paginacion));
    }

    public function buscarEstudiantes(Request $peticion): Response
    {
        $termino = (new Validador($peticion->consulta()))->crudo('q');
        return Response::ok(mb_strlen($termino) < 2 ? [] : $this->pagos->buscarEstudiantes($termino));
    }

    public function apoderado(Request $peticion): Response
    {
        return Response::ok($this->servicio->apoderado($peticion->parametro('id')));
    }

    public function saldo(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $matriculaId = $v->id('matricula_id');
        $conceptoId = $v->id('concepto_id');
        $v->validar();
        return Response::ok($this->servicio->saldo((int) $matriculaId, (int) $conceptoId));
    }

    public function registrar(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'matricula_id' => (int) $v->id('matricula_id', true, 'Selecciona un estudiante matriculado.'),
            'concepto_id' => (int) $v->id('concepto_id', true, 'Selecciona un concepto válido.'),
            'abono' => $v->centimos('abono_hoy', 'Abono'),
            'entregado_por' => $v->texto('entregado_por', 150, true, false, 'Quién entrega el dinero'),
            'fecha_pago' => (string) $v->fecha('fecha_pago', true, 'Fecha de pago'),
            'observacion' => $v->textoONull('observacion', 250),
        ];
        $v->validar();
        return Response::creado($this->servicio->registrar($datos));
    }
}
