<?php
declare(strict_types=1);

namespace App\Auth;

use App\Http\HttpException;

/** Datos de la petición en curso: usuario autenticado, su token e IP de origen. */
final class Contexto
{
    public ?string $ip = null;
    private ?UsuarioAutenticado $usuario = null;
    private ?string $token = null;

    public function autenticar(UsuarioAutenticado $usuario, string $token): void
    {
        $this->usuario = $usuario;
        $this->token = $token;
    }

    public function usuario(): UsuarioAutenticado
    {
        return $this->usuario ?? throw HttpException::noAutenticado();
    }

    public function usuarioId(): ?int
    {
        return $this->usuario?->id;
    }

    public function token(): ?string
    {
        return $this->token;
    }
}
