<?php
declare(strict_types=1);

namespace App\Validacion;

use App\Http\HttpException;

/** 422 con el detalle de cada campo inválido. El mensaje general es el del primer error. */
final class ValidacionException extends HttpException
{
    /** @param array<string, string> $campos */
    public function __construct(array $campos)
    {
        parent::__construct(422, (string) reset($campos), $campos);
    }
}
