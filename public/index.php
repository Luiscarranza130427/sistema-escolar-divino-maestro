<?php
declare(strict_types=1);

/** Punto de entrada único de la API: /api/v1/... */

$raiz = dirname(__DIR__);
require $raiz . '/vendor/autoload.php';

// Servidor de desarrollo de PHP: los archivos estáticos existentes se sirven tal cual.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH))) {
    return false;
}

App\Aplicacion::kernel($raiz)->manejar(App\Http\Request::desdeGlobales())->enviar();
