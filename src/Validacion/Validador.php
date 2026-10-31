<?php
declare(strict_types=1);

namespace App\Validacion;

use App\Core\Paginacion;
use DateTime;

/**
 * Lee y valida datos de entrada acumulando un error por campo.
 * Uso: $v = new Validador($datos); $dni = $v->dni('dni'); ...; $v->validar();
 * Los objetos anidados usan prefijo: $v->anidado('padre', fn) informa errores como 'padre.dni'.
 */
final class Validador
{
    /** @var array<string, string> */
    private array $errores = [];

    /** @param array<string, mixed> $datos */
    public function __construct(private array $datos, private string $prefijo = '')
    {
    }

    public function validar(): void
    {
        if ($this->errores !== []) {
            throw new ValidacionException($this->errores);
        }
    }

    public function error(string $campo, string $mensaje): void
    {
        $this->errores[$this->prefijo . $campo] ??= $mensaje;
    }

    public function presente(string $campo): bool
    {
        return array_key_exists($campo, $this->datos) && $this->datos[$campo] !== null;
    }

    /** Texto recortado sin validar. Los números se convierten a texto; otros tipos dan cadena vacía. */
    public function crudo(string $campo): string
    {
        $valor = $this->datos[$campo] ?? null;
        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }
        return is_string($valor) ? trim($valor) : '';
    }

    /** Valida un objeto anidado con su propio validador; devuelve null si no se envió. */
    public function anidado(string $campo, callable $leer): mixed
    {
        $valor = $this->datos[$campo] ?? null;
        if ($valor === null) {
            return null;
        }
        if (!is_array($valor)) {
            $this->error($campo, "El campo {$campo} debe ser un objeto.");
            return null;
        }
        $hijo = new self($valor, $this->prefijo . $campo . '.');
        $resultado = $leer($hijo);
        foreach ($hijo->errores as $clave => $mensaje) {
            $this->errores[$clave] ??= $mensaje;
        }
        return $resultado;
    }

    public function texto(string $campo, int $maximo, bool $requerido = false, bool $mayusculas = false, ?string $etiqueta = null): string
    {
        $valor = $this->crudo($campo);
        $etiqueta ??= str_replace('_', ' ', $campo);
        if ($requerido && $valor === '') {
            $this->error($campo, "Completa el campo {$etiqueta}.");
        } elseif (mb_strlen($valor, 'UTF-8') > $maximo) {
            $this->error($campo, "El campo {$etiqueta} admite como máximo {$maximo} caracteres.");
        }
        return $mayusculas ? mb_strtoupper($valor, 'UTF-8') : $valor;
    }

    /** Igual que texto() pero devuelve null si está vacío. */
    public function textoONull(string $campo, int $maximo, bool $mayusculas = false, ?string $etiqueta = null): ?string
    {
        $valor = $this->texto($campo, $maximo, false, $mayusculas, $etiqueta);
        return $valor === '' ? null : $valor;
    }

    public function id(string $campo, bool $requerido = true, ?string $mensaje = null): ?int
    {
        $valor = $this->crudo($campo);
        if ($valor === '') {
            if ($requerido) {
                $this->error($campo, $mensaje ?? "Falta el campo {$campo}.");
            }
            return null;
        }
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $this->error($campo, $mensaje ?? "El campo {$campo} no es válido.");
            return null;
        }
        return $id;
    }

    public function entero(string $campo, int $minimo, int $maximo, ?string $mensaje = null): ?int
    {
        $opciones = ['options' => ['min_range' => $minimo, 'max_range' => $maximo]];
        $numero = filter_var($this->crudo($campo), FILTER_VALIDATE_INT, $opciones);
        if ($numero === false) {
            $this->error($campo, $mensaje ?? "El campo {$campo} debe ser un número entre {$minimo} y {$maximo}.");
            return null;
        }
        return $numero;
    }

    public function dni(string $campo, bool $requerido = true, string $etiqueta = 'DNI'): string
    {
        $valor = $this->crudo($campo);
        if ($valor === '' && !$requerido) {
            return $valor;
        }
        if (preg_match('/^[0-9]{8}$/', $valor) !== 1) {
            $this->error($campo, "{$etiqueta}: debe tener 8 números.");
        }
        return $valor;
    }

    /** Celular opcional de 9 dígitos; null si está vacío. */
    public function celular(string $campo, string $etiqueta = 'Celular'): ?string
    {
        $valor = $this->crudo($campo);
        if ($valor === '') {
            return null;
        }
        if (preg_match('/^[0-9]{9}$/', $valor) !== 1) {
            $this->error($campo, "{$etiqueta}: debe tener exactamente 9 dígitos.");
        }
        return $valor;
    }

    public function correo(string $campo): string
    {
        $valor = mb_strtolower($this->crudo($campo), 'UTF-8');
        if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false || mb_strlen($valor) > 150) {
            $this->error($campo, 'Ingresa un correo válido.');
        }
        return $valor;
    }

    /** Fecha AAAA-MM-DD. */
    public function fecha(string $campo, bool $requerido = true, ?string $etiqueta = null): ?string
    {
        $valor = $this->crudo($campo);
        if ($valor === '' && !$requerido) {
            return null;
        }
        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            $this->error($campo, ($etiqueta ?? $campo) . ': usa el formato AAAA-MM-DD.');
            return null;
        }
        return $valor;
    }

    /** Monto mayor a cero con hasta 2 decimales; devuelve céntimos. */
    public function centimos(string $campo, string $etiqueta = 'Monto'): int
    {
        $valor = $this->crudo($campo);
        if (preg_match('/^[0-9]{1,8}([.][0-9]{1,2})?$/', $valor) !== 1 || (float) $valor <= 0) {
            $this->error($campo, "{$etiqueta}: ingresa un monto mayor a 0 con hasta 2 decimales.");
            return 0;
        }
        return (int) round((float) $valor * 100);
    }

    /** @param list<string> $opciones */
    public function opcion(string $campo, array $opciones, ?string $porDefecto = null): ?string
    {
        $valor = $this->crudo($campo);
        if ($valor === '' && $porDefecto !== null) {
            return $porDefecto;
        }
        if (!in_array($valor, $opciones, true)) {
            $this->error($campo, "El campo {$campo} debe ser uno de: " . implode(', ', $opciones) . '.');
            return null;
        }
        return $valor;
    }

    public function booleano(string $campo, ?bool $porDefecto = null): ?bool
    {
        if (!$this->presente($campo) && $porDefecto !== null) {
            return $porDefecto;
        }
        $valor = filter_var($this->datos[$campo] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($valor === null) {
            $this->error($campo, "El campo {$campo} debe ser verdadero o falso.");
        }
        return $valor;
    }

    /** Contraseña (no se recortan espacios). */
    public function contrasena(string $campo, int $minimo = 8): string
    {
        $valor = $this->datos[$campo] ?? null;
        if (!is_string($valor) || strlen($valor) < $minimo || strlen($valor) > 200) {
            $this->error($campo, "La contraseña debe tener entre {$minimo} y 200 caracteres.");
            return '';
        }
        return $valor;
    }

    /** @return list<int> */
    public function listaIds(string $campo): array
    {
        $valor = $this->datos[$campo] ?? [];
        if (!is_array($valor)) {
            $this->error($campo, "El campo {$campo} debe ser una lista de IDs.");
            return [];
        }
        $ids = [];
        foreach ($valor as $elemento) {
            $id = filter_var($elemento, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                $this->error($campo, "El campo {$campo} contiene IDs no válidos.");
                return [];
            }
            $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }

    /** @return array<mixed> */
    public function arreglo(string $campo, string $mensaje): array
    {
        $valor = $this->datos[$campo] ?? null;
        if (!is_array($valor)) {
            $this->error($campo, $mensaje);
            return [];
        }
        return $valor;
    }

    public function paginacion(): Paginacion
    {
        $pagina = $this->presente('pagina') ? $this->entero('pagina', 1, PHP_INT_MAX, 'La página debe ser un número mayor a 0.') : 1;
        $porPagina = $this->presente('por_pagina')
            ? $this->entero('por_pagina', 1, Paginacion::MAXIMO, 'por_pagina debe estar entre 1 y ' . Paginacion::MAXIMO . '.')
            : Paginacion::POR_PAGINA;
        return new Paginacion($pagina ?? 1, $porPagina ?? Paginacion::POR_PAGINA);
    }
}
