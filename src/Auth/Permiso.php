<?php
declare(strict_types=1);

namespace App\Auth;

/** Códigos de la tabla permisos. Los asistentes solo tienen los que el director les asigna. */
final class Permiso
{
    public const EDITAR = 'p_editar';
    public const MATRICULAR = 'p_matricular';
    public const COBRAR = 'p_cobrar';
    public const AUDITORIA = 'p_auditoria';

    public const TODOS = [self::EDITAR, self::MATRICULAR, self::COBRAR, self::AUDITORIA];
}
