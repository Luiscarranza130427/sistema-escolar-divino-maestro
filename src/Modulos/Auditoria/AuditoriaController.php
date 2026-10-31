<?php
declare(strict_types=1);

namespace App\Modulos\Auditoria;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Validacion\Validador;

final class AuditoriaController
{
    public function __construct(private AuditoriaRepository $auditoria)
    {
    }

    public function listar(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $filtros = [
            'fecha_inicio' => $v->fecha('fecha_inicio', false, 'Fecha de inicio'),
            'fecha_fin' => $v->fecha('fecha_fin', false, 'Fecha de fin'),
            'usuario_id' => $v->id('usuario_id', false),
            'tipo_accion' => $v->crudo('tipo_accion') === '' ? null : $v->opcion('tipo_accion', ['INSERT', 'UPDATE', 'DELETE']),
        ];
        $paginacion = $v->paginacion();
        $v->validar();
        return Response::pagina($this->auditoria->listar($filtros, $paginacion));
    }

    public function resumen(Request $peticion): Response
    {
        return Response::ok($this->auditoria->resumenPorUsuario());
    }

    public function eliminar(Request $peticion): Response
    {
        if (!$this->auditoria->eliminar($peticion->parametro('id'))) {
            throw HttpException::noEncontrado('No se encontró el registro de auditoría.');
        }
        return Response::sinContenido();
    }
}
