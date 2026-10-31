<?php
declare(strict_types=1);

namespace App\Core;

/** Resultado paginado: elementos de la página actual y metadatos. */
final class Pagina
{
    /** @param list<array<string, mixed>> $elementos */
    public function __construct(
        public readonly array $elementos,
        public readonly int $total,
        public readonly Paginacion $paginacion
    ) {
    }

    /** Aplica una transformación a cada elemento conservando los metadatos. */
    public function mapear(callable $transformar): self
    {
        return new self(array_map($transformar, $this->elementos), $this->total, $this->paginacion);
    }

    /** @return array{pagina: int, por_pagina: int, total: int, paginas: int} */
    public function meta(): array
    {
        return [
            'pagina' => $this->paginacion->pagina,
            'por_pagina' => $this->paginacion->porPagina,
            'total' => $this->total,
            'paginas' => (int) max(1, ceil($this->total / $this->paginacion->porPagina)),
        ];
    }
}
