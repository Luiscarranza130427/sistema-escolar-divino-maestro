<?php
declare(strict_types=1);

namespace App;

use App\Core\Config;
use App\Core\Contenedor;
use App\Core\Database;
use App\Http\Kernel;
use App\Http\Router;
use App\Soporte\FotosPerfil;

/** Arma el contenedor de dependencias, las rutas y el kernel HTTP. */
final class Aplicacion
{
    public static function contenedor(string $raiz, ?Config $config = null): Contenedor
    {
        $config ??= Config::desdeEntorno($raiz);
        $contenedor = new Contenedor();
        $contenedor->instancia(Contenedor::class, $contenedor);
        $contenedor->instancia(Config::class, $config);
        // La conexión se abre solo cuando algún componente la necesita.
        $contenedor->definir(Database::class, static fn (Contenedor $c): Database => Database::conectar($c->obtener(Config::class)));
        $contenedor->definir(FotosPerfil::class, static fn (): FotosPerfil => new FotosPerfil($raiz . '/public/uploads/perfiles'));
        $contenedor->definir(Router::class, static function () use ($raiz): Router {
            $router = new Router();
            (require $raiz . '/config/rutas.php')($router);
            return $router;
        });
        return $contenedor;
    }

    public static function kernel(string $raiz, ?Config $config = null): Kernel
    {
        return self::contenedor($raiz, $config)->obtener(Kernel::class);
    }
}
