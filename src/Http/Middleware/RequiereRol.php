<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Contexto;
use App\Auth\Rol;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/** 'rol:director' — el hiperadmin pasa cualquier restricción de rol. */
final class RequiereRol implements Middleware
{
    public function __construct(private Contexto $contexto)
    {
    }

    public function procesar(Request $peticion, callable $siguiente, array $argumentos): Response
    {
        $rol = $this->contexto->usuario()->rol;
        if ($rol !== Rol::HIPERADMIN && !in_array($rol, $argumentos, true)) {
            throw HttpException::prohibido('No tienes permisos para acceder a este recurso.');
        }
        return $siguiente($peticion);
    }
}
