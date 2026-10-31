<?php
declare(strict_types=1);

namespace App\Modulos\Responsables;

use App\Validacion\Validador;

/**
 * Lectura de un contacto familiar (padre, madre o emergencia) enviado como objeto:
 * {id?, relacion_id?, dni, nombres, apellido_paterno, apellido_materno, parentesco, celular}
 */
final class Contacto
{
    private const CAMPOS = ['dni', 'nombres', 'apellido_paterno', 'apellido_materno', 'parentesco', 'celular'];

    /**
     * Devuelve null si el objeto llegó vacío.
     * @return array{id: ?int, relacion_id: ?int, dni: string, nombres: string, apellido_paterno: string, apellido_materno: ?string, parentesco: string, celular: ?string}|null
     */
    public static function leer(Validador $v, bool $parentescoObligatorio = false, bool $celularObligatorio = false): ?array
    {
        $vacio = !$v->presente('id');
        foreach (self::CAMPOS as $campo) {
            $vacio = $vacio && $v->crudo($campo) === '';
        }
        if ($vacio) {
            return null;
        }

        $contacto = [
            'id' => $v->id('id', false),
            'relacion_id' => $v->id('relacion_id', false),
            'dni' => $v->dni('dni'),
            'nombres' => $v->texto('nombres', 100, true, true),
            'apellido_paterno' => $v->texto('apellido_paterno', 100, true, true),
            'apellido_materno' => $v->textoONull('apellido_materno', 100, true),
            'parentesco' => $v->texto('parentesco', 60, $parentescoObligatorio, true),
            'celular' => $v->celular('celular'),
        ];
        if ($celularObligatorio && $contacto['celular'] === null) {
            $v->error('celular', 'Indica el celular del contacto.');
        }
        return $contacto;
    }
}
