<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Validacion\ValidacionException;
use App\Validacion\Validador;
use PHPUnit\Framework\TestCase;

final class ValidadorTest extends TestCase
{
    public function testDatosValidosSeNormalizan(): void
    {
        $v = new Validador(['nombres' => '  josé  ', 'dni' => '12345678', 'monto' => '10.5', 'fecha' => '2026-02-28']);
        self::assertSame('JOSÉ', $v->texto('nombres', 100, true, true));
        self::assertSame('12345678', $v->dni('dni'));
        self::assertSame(1050, $v->centimos('monto'));
        self::assertSame('2026-02-28', $v->fecha('fecha'));
        $v->validar();
    }

    public function testAcumulaUnErrorPorCampo(): void
    {
        $v = new Validador(['dni' => '12', 'monto' => '0', 'fecha' => '2026-02-30']);
        $v->dni('dni');
        $v->centimos('monto');
        $v->fecha('fecha');
        $v->texto('nombres', 100, true);

        try {
            $v->validar();
            self::fail('Debió lanzar ValidacionException.');
        } catch (ValidacionException $error) {
            self::assertSame(422, $error->estado());
            self::assertSame(['dni', 'monto', 'fecha', 'nombres'], array_keys($error->campos()));
        }
    }

    public function testObjetoAnidadoUsaPrefijoEnLosErrores(): void
    {
        $v = new Validador(['padre' => ['dni' => 'abc']]);
        $v->anidado('padre', static fn (Validador $h) => $h->dni('dni'));
        $this->expectException(ValidacionException::class);
        try {
            $v->validar();
        } catch (ValidacionException $error) {
            self::assertArrayHasKey('padre.dni', $error->campos());
            throw $error;
        }
    }

    public function testAnidadoAusenteDevuelveNull(): void
    {
        $v = new Validador([]);
        self::assertNull($v->anidado('madre', static fn (Validador $h) => 'no debe llamarse'));
        $v->validar();
    }

    public function testOpcionYBooleano(): void
    {
        $v = new Validador(['tipo' => 'familia', 'activo' => 'false']);
        self::assertSame('familia', $v->opcion('tipo', ['estudiante', 'familia']));
        self::assertSame('estudiante', $v->opcion('otro', ['estudiante', 'familia'], 'estudiante'));
        self::assertFalse($v->booleano('activo'));
        $v->validar();
    }

    public function testPaginacionFueraDeRangoEsError(): void
    {
        $v = new Validador(['por_pagina' => '500']);
        $v->paginacion();
        $this->expectException(ValidacionException::class);
        $v->validar();
    }
}
