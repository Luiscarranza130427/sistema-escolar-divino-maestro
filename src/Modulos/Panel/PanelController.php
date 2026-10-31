<?php
declare(strict_types=1);

namespace App\Modulos\Panel;

use App\Http\Request;
use App\Http\Response;

final class PanelController
{
    public function __construct(private PanelRepository $panel)
    {
    }

    public function metricas(Request $peticion): Response
    {
        return Response::ok($this->panel->metricas());
    }

    public function analiticas(Request $peticion): Response
    {
        return Response::ok($this->panel->analiticas());
    }
}
