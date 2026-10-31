<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Configuración de la aplicación.
 * Prioridad: variables del servidor > archivo .env > valores por defecto.
 */
final class Config
{
    /** @param array<string, string> $valores */
    private function __construct(private array $valores)
    {
    }

    public static function desdeEntorno(string $raiz): self
    {
        $valores = [];
        $archivoEnv = $raiz . '/.env';
        if (is_readable($archivoEnv)) {
            foreach (file($archivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
                $linea = trim($linea);
                if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                    continue;
                }
                [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
                $valores[$clave] = trim($valor, " \t\"'");
            }
        }

        return new self($valores);
    }

    /** @param array<string, string> $valores */
    public static function desdeArreglo(array $valores): self
    {
        return new self($valores);
    }

    /** Las variables del servidor tienen prioridad sobre .env. */
    public function texto(string $clave, string $porDefecto = ''): string
    {
        $servidor = getenv($clave);
        if ($servidor !== false) {
            return $servidor;
        }
        return $this->valores[$clave] ?? $porDefecto;
    }

    public function entero(string $clave, int $porDefecto): int
    {
        $valor = filter_var($this->texto($clave), FILTER_VALIDATE_INT);
        return $valor === false ? $porDefecto : $valor;
    }

    public function booleano(string $clave, bool $porDefecto = false): bool
    {
        return filter_var($this->texto($clave), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $porDefecto;
    }

    /** @return list<string> */
    public function lista(string $clave): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->texto($clave)))));
    }
}
