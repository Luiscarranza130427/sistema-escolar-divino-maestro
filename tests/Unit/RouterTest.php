<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->grupo('/api/v1', ['auth'], static function (Router $r): void {
            $r->get('/estudiantes/buscar', ['C', 'buscar']);
            $r->get('/estudiantes/{id}', ['C', 'ver'], ['permiso:p_editar']);
            $r->delete('/responsables/{id}/estudiantes/{estudiante}', ['C', 'desvincular']);
        });
    }

    public function testResuelveParametrosYMiddlewares(): void
    {
        $resultado = $this->router->buscar('GET', '/api/v1/estudiantes/42/');
        self::assertSame(['id' => '42'], $resultado['parametros']);
        self::assertSame(['auth', 'permiso:p_editar'], $resultado['ruta']['middleware']);
    }

    public function testRutaFijaNoChocaConParametroNumerico(): void
    {
        self::assertSame('buscar', $this->router->buscar('GET', '/api/v1/estudiantes/buscar')['ruta']['accion'][1]);
        self::assertNull($this->router->buscar('GET', '/api/v1/estudiantes/abc')['ruta']);
    }

    public function testVariosParametros(): void
    {
        $resultado = $this->router->buscar('DELETE', '/api/v1/responsables/3/estudiantes/7');
        self::assertSame(['id' => '3', 'estudiante' => '7'], $resultado['parametros']);
    }

    public function testMetodoNoPermitidoInformaLosPermitidos(): void
    {
        $resultado = $this->router->buscar('POST', '/api/v1/estudiantes/1');
        self::assertNull($resultado['ruta']);
        self::assertSame(['GET'], $resultado['permitidos']);
    }

    public function testRutasDeLaAplicacionSonUnicas(): void
    {
        $router = new Router();
        (require dirname(__DIR__, 2) . '/config/rutas.php')($router);
        $claves = array_map(static fn (array $r): string => $r['metodo'] . ' ' . $r['patron'], $router->listar());
        self::assertSame($claves, array_unique($claves));
        foreach ($router->listar() as $ruta) {
            if ($ruta['patron'] !== '/api/v1/auth/login') {
                self::assertSame('auth', $ruta['middleware'][0] ?? null, "La ruta {$ruta['patron']} debe exigir token.");
            }
        }
    }
}
