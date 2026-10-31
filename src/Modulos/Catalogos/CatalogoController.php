<?php
declare(strict_types=1);

namespace App\Modulos\Catalogos;

use App\Http\Request;
use App\Http\Response;
use App\Validacion\Validador;

final class CatalogoController
{
    public function __construct(private CatalogoRepository $catalogos)
    {
    }

    public function anios(Request $peticion): Response
    {
        return Response::ok($this->catalogos->anios());
    }

    public function niveles(Request $peticion): Response
    {
        return Response::ok($this->catalogos->niveles());
    }

    public function grados(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $nivelId = $v->id('nivel_id', false);
        $v->validar();
        return Response::ok($this->catalogos->grados($nivelId));
    }

    public function secciones(Request $peticion): Response
    {
        $v = new Validador($peticion->consulta());
        $gradoId = $v->id('grado_id', false);
        $v->validar();
        return Response::ok($this->catalogos->secciones($gradoId));
    }
}
