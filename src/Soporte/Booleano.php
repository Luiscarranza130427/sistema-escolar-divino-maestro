<?php
declare(strict_types=1);

namespace App\Soporte;

/** PostgreSQL vía PDO puede devolver booleanos como bool, 't'/'f' o '1'/'0'. */
final class Booleano
{
    public static function desdeBd(mixed $valor): bool
    {
        return $valor === true || $valor === 't' || $valor === 1 || $valor === '1' || $valor === 'true';
    }

    public static function desdeBdONull(mixed $valor): ?bool
    {
        return $valor === null ? null : self::desdeBd($valor);
    }

    /**
     * @param array<string, mixed> $fila
     * @param list<string> $campos
     * @return array<string, mixed>
     */
    public static function normalizar(array $fila, array $campos): array
    {
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $fila)) {
                $fila[$campo] = self::desdeBdONull($fila[$campo]);
            }
        }
        return $fila;
    }
}
