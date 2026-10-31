<?php
declare(strict_types=1);

namespace App\Modulos\Usuarios;

use App\Http\Request;
use App\Http\Response;
use App\Validacion\Validador;

final class UsuarioController
{
    public function __construct(private UsuarioRepository $usuarios, private UsuarioService $servicio)
    {
    }

    public function listar(Request $peticion): Response
    {
        return Response::ok($this->usuarios->listarParaFiltro());
    }

    public function permisos(Request $peticion): Response
    {
        return Response::ok($this->usuarios->permisosActivos());
    }

    public function asistentes(Request $peticion): Response
    {
        return Response::ok($this->usuarios->listarAsistentes());
    }

    public function crearAsistente(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $datos = [
            'nombres' => $v->texto('nombres', 150, true, false, 'nombre'),
            'correo' => $v->correo('correo'),
            'password' => $v->contrasena('password'),
            'permisos' => $v->listaIds('permisos'),
        ];
        $v->validar();
        return Response::creado(['id' => $this->servicio->crearAsistente($datos)]);
    }

    public function actualizarPermisos(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $permisos = $v->listaIds('permisos');
        $v->validar();
        $this->servicio->actualizarPermisos($peticion->parametro('id'), $permisos);
        return Response::ok(['permisos' => $permisos]);
    }

    public function revocar(Request $peticion): Response
    {
        $this->servicio->cambiarAcceso($peticion->parametro('id'), false);
        return Response::ok(['estado' => false]);
    }

    public function restaurar(Request $peticion): Response
    {
        $this->servicio->cambiarAcceso($peticion->parametro('id'), true);
        return Response::ok(['estado' => true]);
    }
}
