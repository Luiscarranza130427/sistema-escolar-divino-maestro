<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

interface Middleware
{
    /**
     * @param callable(Request): Response $siguiente
     * @param list<string> $argumentos valores tras los dos puntos, p. ej. 'rol:director,hiperadmin'
     */
    public function procesar(Request $peticion, callable $siguiente, array $argumentos): Response;
}
