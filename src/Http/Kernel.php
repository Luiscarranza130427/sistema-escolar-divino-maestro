<?php
declare(strict_types=1);

namespace App\Http;

use App\Auth\Contexto;
use App\Core\Config;
use App\Core\Contenedor;
use App\Core\Database;
use App\Http\Middleware\Autenticar;
use App\Http\Middleware\Middleware;
use App\Http\Middleware\RequierePermiso;
use App\Http\Middleware\RequiereRol;
use PDOException;
use Throwable;

/**
 * Recibe la petición, aplica CORS, resuelve la ruta, ejecuta sus middlewares y el controlador,
 * y convierte cualquier error en una respuesta JSON coherente.
 */
final class Kernel
{
    /** @var array<string, class-string<Middleware>> */
    private const MIDDLEWARES = [
        'auth' => Autenticar::class,
        'rol' => RequiereRol::class,
        'permiso' => RequierePermiso::class,
    ];

    public function __construct(private Contenedor $contenedor, private Router $router, private Config $config)
    {
    }

    public function manejar(Request $peticion): Response
    {
        $cors = $this->cabecerasCors($peticion);
        if ($peticion->metodo === 'OPTIONS') {
            return Response::sinContenido()->conCabeceras($cors);
        }

        try {
            $this->contenedor->obtener(Contexto::class)->ip = $peticion->ip;
            $respuesta = $this->despachar($peticion);
        } catch (HttpException $error) {
            $respuesta = Response::error($error->estado(), $error->getMessage(), $error->campos());
        } catch (PDOException $error) {
            $respuesta = $this->errorBaseDatos($peticion, $error);
        } catch (Throwable $error) {
            $this->revertir();
            error_log("Error inesperado en {$peticion->metodo} {$peticion->ruta}: {$error}");
            $mensaje = $this->config->booleano('APP_DEBUG') ? $error->getMessage() : 'Ocurrió un error inesperado.';
            $respuesta = Response::error(500, $mensaje);
        }

        return $respuesta->conCabeceras($cors);
    }

    private function despachar(Request $peticion): Response
    {
        $resultado = $this->router->buscar($peticion->metodo, $peticion->ruta);
        if ($resultado['ruta'] === null) {
            if ($resultado['permitidos'] !== []) {
                return Response::error(405, 'Método no permitido.')
                    ->conCabeceras(['Allow' => implode(', ', $resultado['permitidos'])]);
            }
            throw HttpException::noEncontrado('Ruta no encontrada.');
        }

        $ruta = $resultado['ruta'];
        $peticion = $peticion->conParametros($resultado['parametros']);
        [$clase, $metodo] = $ruta['accion'];

        $nucleo = function (Request $peticion) use ($clase, $metodo): Response {
            return $this->contenedor->obtener($clase)->{$metodo}($peticion);
        };

        // Se arma la cadena de middlewares de afuera hacia adentro.
        $cadena = array_reduce(
            array_reverse($ruta['middleware']),
            function (callable $siguiente, string $definicion): callable {
                [$alias, $argumentos] = array_pad(explode(':', $definicion, 2), 2, '');
                $middleware = $this->contenedor->obtener(self::MIDDLEWARES[$alias]);
                $lista = $argumentos === '' ? [] : explode(',', $argumentos);
                return static fn (Request $p): Response => $middleware->procesar($p, $siguiente, $lista);
            },
            $nucleo
        );

        return $cadena($peticion);
    }

    private function errorBaseDatos(Request $peticion, PDOException $error): Response
    {
        $this->revertir();
        error_log("Error de base de datos en {$peticion->metodo} {$peticion->ruta}: " . $error->getMessage());
        return match ((string) $error->getCode()) {
            '23505' => Response::error(409, 'Ya existe un registro con esos datos.'),
            '23503' => Response::error(409, 'No se puede completar: el registro está vinculado a otros datos.'),
            '23514', '22001', '22P02' => Response::error(422, 'Algún dato no cumple las reglas de la base de datos.'),
            default => Response::error(500, 'Ocurrió un error en la base de datos.'),
        };
    }

    private function revertir(): void
    {
        try {
            $this->contenedor->obtener(Database::class)->revertirSiHayTransaccion();
        } catch (Throwable) {
            // Sin conexión no hay nada que revertir.
        }
    }

    /** @return array<string, string> */
    private function cabecerasCors(Request $peticion): array
    {
        $origen = $peticion->cabecera('origin');
        $permitidos = $this->config->lista('APP_CORS_ORIGINS');
        if ($origen === null || ($permitidos !== ['*'] && !in_array($origen, $permitidos, true))) {
            return [];
        }
        return [
            'Access-Control-Allow-Origin' => $permitidos === ['*'] ? '*' : $origen,
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Max-Age' => '600',
            'Vary' => 'Origin',
        ];
    }
}
