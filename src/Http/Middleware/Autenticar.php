<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Contexto;
use App\Auth\ServicioTokens;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/** Exige un token Bearer válido y deja al usuario en el Contexto de la petición. */
final class Autenticar implements Middleware
{
    public function __construct(private ServicioTokens $tokens, private Contexto $contexto)
    {
    }

    public function procesar(Request $peticion, callable $siguiente, array $argumentos): Response
    {
        $token = $peticion->tokenBearer();
        $usuario = $token === null ? null : $this->tokens->resolver($token);
        if ($usuario === null) {
            throw HttpException::noAutenticado('Debes iniciar sesión (token ausente, inválido o vencido).');
        }
        $this->contexto->autenticar($usuario, $token);
        return $siguiente($peticion);
    }
}
