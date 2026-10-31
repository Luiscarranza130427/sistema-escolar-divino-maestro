<?php
declare(strict_types=1);

namespace App\Core;

final class Paginacion
{
    public const POR_PAGINA = 25;
    public const MAXIMO = 100;

    public function __construct(public readonly int $pagina = 1, public readonly int $porPagina = self::POR_PAGINA)
    {
    }

    public function desplazamiento(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }
}
