<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Enrutador. Cada ruta apunta a [Controlador::class, 'metodo'] y declara sus middlewares,
 * por ejemplo 'auth', 'rol:director' o 'permiso:p_cobrar'. Los {parametros} solo aceptan números.
 */
final class Router
{
    /** @var list<array{metodo: string, patron: string, regex: string, accion: array{0: class-string, 1: string}, middleware: list<string>}> */
    private array $rutas = [];
    private string $prefijo = '';
    /** @var list<string> */
    private array $middlewareGrupo = [];

    public function get(string $ruta, array $accion, array $middleware = []): void
    {
        $this->agregar('GET', $ruta, $accion, $middleware);
    }

    public function post(string $ruta, array $accion, array $middleware = []): void
    {
        $this->agregar('POST', $ruta, $accion, $middleware);
    }

    public function put(string $ruta, array $accion, array $middleware = []): void
    {
        $this->agregar('PUT', $ruta, $accion, $middleware);
    }

    public function patch(string $ruta, array $accion, array $middleware = []): void
    {
        $this->agregar('PATCH', $ruta, $accion, $middleware);
    }

    public function delete(string $ruta, array $accion, array $middleware = []): void
    {
        $this->agregar('DELETE', $ruta, $accion, $middleware);
    }

    /** Agrupa rutas bajo un prefijo y middlewares comunes. */
    public function grupo(string $prefijo, array $middleware, callable $definir): void
    {
        [$prefijoAnterior, $middlewareAnterior] = [$this->prefijo, $this->middlewareGrupo];
        $this->prefijo .= $prefijo;
        $this->middlewareGrupo = [...$this->middlewareGrupo, ...$middleware];
        $definir($this);
        [$this->prefijo, $this->middlewareGrupo] = [$prefijoAnterior, $middlewareAnterior];
    }

    private function agregar(string $metodo, string $ruta, array $accion, array $middleware): void
    {
        $patron = rtrim($this->prefijo . $ruta, '/') ?: '/';
        $this->rutas[] = [
            'metodo' => $metodo,
            'patron' => $patron,
            'regex' => '#^' . preg_replace('#[{](\w+)[}]#', '(?P<$1>[0-9]+)', $patron) . '$#',
            'accion' => $accion,
            'middleware' => [...$this->middlewareGrupo, ...$middleware],
        ];
    }

    /**
     * @return array{ruta: array<string, mixed>|null, parametros: array<string, string>, permitidos: list<string>}
     */
    public function buscar(string $metodo, string $ruta): array
    {
        $ruta = rtrim($ruta, '/') ?: '/';
        $permitidos = [];
        foreach ($this->rutas as $definicion) {
            if (preg_match($definicion['regex'], $ruta, $coincidencias) !== 1) {
                continue;
            }
            if ($definicion['metodo'] !== $metodo) {
                $permitidos[] = $definicion['metodo'];
                continue;
            }
            $parametros = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
            return ['ruta' => $definicion, 'parametros' => $parametros, 'permitidos' => []];
        }
        return ['ruta' => null, 'parametros' => [], 'permitidos' => array_values(array_unique($permitidos))];
    }

    /** @return list<array{metodo: string, patron: string, middleware: list<string>}> */
    public function listar(): array
    {
        return array_map(
            static fn (array $r): array => ['metodo' => $r['metodo'], 'patron' => $r['patron'], 'middleware' => $r['middleware']],
            $this->rutas
        );
    }
}
