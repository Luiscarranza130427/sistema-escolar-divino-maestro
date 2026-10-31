<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Contexto;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/** 'permiso:p_cobrar' — director e hiperadmin tienen todos los permisos. */
final class RequierePermiso implements Middleware
{
    public function __construct(private Contexto $contexto)
    {
    }

    public function procesar(Request $peticion, callable $siguiente, array $argumentos): Response
    {
        $usuario = $this->contexto->usuario();
        foreach ($argumentos as $permiso) {
            if (!$usuario->puede($permiso)) {
                throw HttpException::prohibido();
            }
        }
        return $siguiente($peticion);
    }
}
