<?php
declare(strict_types=1);

namespace App\Soporte;

use App\Validacion\Validador;

/** Filtro por nivel, grado y sección de la matrícula, común a varios listados. */
final class FiltroAcademico
{
    public function __construct(
        public readonly ?int $nivelId = null,
        public readonly ?int $gradoId = null,
        public readonly ?int $seccionId = null
    ) {
    }

    public static function leer(Validador $v): self
    {
        return new self($v->id('nivel_id', false), $v->id('grado_id', false), $v->id('seccion_id', false));
    }

    public function vacio(): bool
    {
        return $this->nivelId === null && $this->gradoId === null && $this->seccionId === null;
    }

    /**
     * Condiciones SQL sobre los alias m (matricula), g (grado) y s (seccion).
     * @param array<string, mixed> $parametros se completa con los valores
     * @return list<string>
     */
    public function condiciones(array &$parametros): array
    {
        $condiciones = [];
        foreach (['nivel_id' => [$this->nivelId, 'm.nivel_id'], 'grado_id' => [$this->gradoId, 'g.id'], 'seccion_id' => [$this->seccionId, 's.id']] as $clave => [$valor, $columna]) {
            if ($valor !== null) {
                $condiciones[] = "{$columna} = :{$clave}";
                $parametros[$clave] = $valor;
            }
        }
        return $condiciones;
    }
}
