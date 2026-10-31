<?php
declare(strict_types=1);

namespace App\Soporte;

/** Los montos se calculan en céntimos (enteros) para evitar errores de redondeo. */
final class Dinero
{
    public static function aCentimos(string|int|float|null $monto): int
    {
        return (int) round((float) ($monto ?? 0) * 100);
    }

    public static function texto(int $centimos): string
    {
        return number_format($centimos / 100, 2, '.', '');
    }

    public static function decimal(int $centimos): float
    {
        return round($centimos / 100, 2);
    }
}
