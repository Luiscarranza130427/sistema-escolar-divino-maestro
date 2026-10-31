<?php
declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/** Error controlado que se devuelve al cliente con su código HTTP y, opcionalmente, errores por campo. */
class HttpException extends RuntimeException
{
    /** @param array<string, string> $campos */
    public function __construct(private int $estado, string $mensaje, private array $campos = [])
    {
        parent::__construct($mensaje);
    }

    public function estado(): int
    {
        return $this->estado;
    }

    /** @return array<string, string> */
    public function campos(): array
    {
        return $this->campos;
    }

    public static function solicitudInvalida(string $mensaje): self
    {
        return new self(400, $mensaje);
    }

    public static function noAutenticado(string $mensaje = 'Debes iniciar sesión.'): self
    {
        return new self(401, $mensaje);
    }

    public static function prohibido(string $mensaje = 'No tienes permiso para realizar esta acción.'): self
    {
        return new self(403, $mensaje);
    }

    public static function noEncontrado(string $mensaje): self
    {
        return new self(404, $mensaje);
    }

    public static function conflicto(string $mensaje): self
    {
        return new self(409, $mensaje);
    }

    /** Regla de negocio no cumplida (422). */
    public static function regla(string $mensaje, string $campo = ''): self
    {
        return new self(422, $mensaje, $campo === '' ? [] : [$campo => $mensaje]);
    }
}
