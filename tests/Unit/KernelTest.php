<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Aplicacion;
use App\Core\Config;
use App\Http\Kernel;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

/** Comportamiento HTTP que no necesita base de datos (la conexión se abre solo si se usa). */
final class KernelTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $config = Config::desdeArreglo(['APP_CORS_ORIGINS' => 'http://localhost:5173', 'DB_HOST' => 'host-inexistente.invalid']);
        $this->kernel = Aplicacion::kernel(dirname(__DIR__, 2), $config);
    }

    private function pedir(string $metodo, string $ruta, string $cuerpo = '', array $cabeceras = []): array
    {
        $respuesta = $this->kernel->manejar(new Request($metodo, $ruta, [], $cuerpo, $cabeceras, '127.0.0.1'));
        return [$respuesta->estado, json_decode($respuesta->cuerpoJson() ?: 'null', true), $respuesta->cabeceras];
    }

    public function testRutaInexistenteDevuelve404(): void
    {
        [$estado, $cuerpo] = $this->pedir('GET', '/api/v1/no-existe');
        self::assertSame(404, $estado);
        self::assertSame('Ruta no encontrada.', $cuerpo['error']['mensaje']);
    }

    public function testMetodoNoPermitidoDevuelve405ConAllow(): void
    {
        [$estado, , $cabeceras] = $this->pedir('GET', '/api/v1/auth/login');
        self::assertSame(405, $estado);
        self::assertSame('POST', $cabeceras['Allow']);
    }

    public function testJsonInvalidoDevuelve400(): void
    {
        [$estado] = $this->pedir('POST', '/api/v1/auth/login', '{malo', ['content-type' => 'application/json']);
        self::assertSame(400, $estado);
    }

    public function testPreflightCorsDeOrigenPermitido(): void
    {
        [$estado, , $cabeceras] = $this->pedir('OPTIONS', '/api/v1/estudiantes', '', ['origin' => 'http://localhost:5173']);
        self::assertSame(204, $estado);
        self::assertSame('http://localhost:5173', $cabeceras['Access-Control-Allow-Origin']);
        self::assertStringContainsString('Authorization', $cabeceras['Access-Control-Allow-Headers']);
    }

    public function testOrigenNoPermitidoNoRecibeCabecerasCors(): void
    {
        [, , $cabeceras] = $this->pedir('OPTIONS', '/api/v1/estudiantes', '', ['origin' => 'https://otro.example']);
        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $cabeceras);
    }
}
