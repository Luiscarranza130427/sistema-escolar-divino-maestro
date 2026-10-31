<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Soporte\Dinero;
use PHPUnit\Framework\TestCase;

final class DineroTest extends TestCase
{
    public function testConvierteSinErroresDeRedondeo(): void
    {
        self::assertSame(10010, Dinero::aCentimos('100.10'));
        self::assertSame(30, Dinero::aCentimos(0.1 + 0.2));
        self::assertSame(0, Dinero::aCentimos(null));
        self::assertSame('50.05', Dinero::texto(5005));
        self::assertSame(0.3, Dinero::decimal(30));
    }

    public function testSumarCentimosEsExacto(): void
    {
        $total = Dinero::aCentimos('100.10');
        $saldo = $total - Dinero::aCentimos('50.05') - Dinero::aCentimos('50.05');
        self::assertSame(0, $saldo);
    }
}
