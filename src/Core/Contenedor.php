<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Contenedor de dependencias mínimo.
 * Construye las clases leyendo los tipos de su constructor; cada servicio se crea una sola vez.
 */
final class Contenedor
{
    /** @var array<string, callable(self): object> */
    private array $fabricas = [];
    /** @var array<string, object> */
    private array $instancias = [];

    public function definir(string $id, callable $fabrica): void
    {
        $this->fabricas[$id] = $fabrica;
        unset($this->instancias[$id]);
    }

    public function instancia(string $id, object $objeto): void
    {
        $this->instancias[$id] = $objeto;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function obtener(string $id): object
    {
        if (isset($this->instancias[$id])) {
            return $this->instancias[$id];
        }
        $objeto = isset($this->fabricas[$id]) ? ($this->fabricas[$id])($this) : $this->construir($id);
        return $this->instancias[$id] = $objeto;
    }

    private function construir(string $clase): object
    {
        if (!class_exists($clase)) {
            throw new RuntimeException("No existe la clase {$clase}.");
        }
        $reflexion = new ReflectionClass($clase);
        $constructor = $reflexion->getConstructor();
        if ($constructor === null) {
            return new $clase();
        }

        $argumentos = [];
        foreach ($constructor->getParameters() as $parametro) {
            $tipo = $parametro->getType();
            if ($tipo instanceof ReflectionNamedType && !$tipo->isBuiltin()) {
                $argumentos[] = $this->obtener($tipo->getName());
            } elseif ($parametro->isDefaultValueAvailable()) {
                $argumentos[] = $parametro->getDefaultValue();
            } else {
                throw new RuntimeException('No se puede resolver el parámetro ' . $parametro->getName() . " de {$clase}.");
            }
        }
        return $reflexion->newInstanceArgs($argumentos);
    }
}
