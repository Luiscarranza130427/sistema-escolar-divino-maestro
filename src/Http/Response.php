<?php
declare(strict_types=1);

namespace App\Http;

use App\Core\Pagina;

/**
 * Respuesta JSON con formato uniforme:
 *   éxito → {"data": ...} (más "meta" en listados paginados)
 *   error → {"error": {"mensaje": "...", "campos": {...}}}
 */
final class Response
{
    /** @param array<string, string> $cabeceras */
    public function __construct(
        public readonly int $estado,
        public readonly mixed $contenido,
        public readonly array $cabeceras = []
    ) {
    }

    public static function ok(mixed $datos): self
    {
        return new self(200, ['data' => $datos]);
    }

    public static function creado(mixed $datos): self
    {
        return new self(201, ['data' => $datos]);
    }

    public static function pagina(Pagina $pagina): self
    {
        return new self(200, ['data' => $pagina->elementos, 'meta' => $pagina->meta()]);
    }

    public static function sinContenido(): self
    {
        return new self(204, null);
    }

    /** @param array<string, string> $campos */
    public static function error(int $estado, string $mensaje, array $campos = []): self
    {
        $error = ['mensaje' => $mensaje];
        if ($campos !== []) {
            $error['campos'] = $campos;
        }
        return new self($estado, ['error' => $error]);
    }

    /** @param array<string, string> $cabeceras */
    public function conCabeceras(array $cabeceras): self
    {
        return new self($this->estado, $this->contenido, $cabeceras + $this->cabeceras);
    }

    public function cuerpoJson(): string
    {
        if ($this->contenido === null) {
            return '';
        }
        return (string) json_encode($this->contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function enviar(): void
    {
        http_response_code($this->estado);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        foreach ($this->cabeceras as $nombre => $valor) {
            header("{$nombre}: {$valor}");
        }
        echo $this->cuerpoJson();
    }
}
