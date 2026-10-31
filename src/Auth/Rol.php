<?php
declare(strict_types=1);

namespace App\Auth;

final class Rol
{
    public const ASISTENTE = 'asistente';
    public const DIRECTOR = 'director';
    public const HIPERADMIN = 'hiperadmin';

    /** Roles con todos los permisos. */
    public const ADMINISTRADORES = [self::DIRECTOR, self::HIPERADMIN];
    public const TODOS = [self::ASISTENTE, self::DIRECTOR, self::HIPERADMIN];
}
