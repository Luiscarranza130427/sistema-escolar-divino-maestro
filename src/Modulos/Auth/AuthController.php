<?php
declare(strict_types=1);

namespace App\Modulos\Auth;

use App\Auth\Contexto;
use App\Http\Request;
use App\Http\Response;
use App\Validacion\Validador;

final class AuthController
{
    public function __construct(private AuthService $servicio, private Contexto $contexto)
    {
    }

    public function login(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $correo = $v->crudo('correo');
        $password = is_string($peticion->cuerpo()['password'] ?? null) ? $peticion->cuerpo()['password'] : '';
        if ($correo === '') {
            $v->error('correo', 'Ingresa tu correo.');
        }
        if ($password === '') {
            $v->error('password', 'Ingresa tu contraseña.');
        }
        $v->validar();

        $dispositivo = $v->crudo('dispositivo') ?: (string) ($peticion->cabecera('user-agent') ?? 'desconocido');
        return Response::ok($this->servicio->iniciarSesion($correo, $password, $dispositivo));
    }

    public function logout(Request $peticion): Response
    {
        $this->servicio->cerrarSesion();
        return Response::sinContenido();
    }

    public function yo(Request $peticion): Response
    {
        return Response::ok($this->contexto->usuario()->aArreglo());
    }

    public function cambiarContrasena(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $actual = is_string($peticion->cuerpo()['password_actual'] ?? null) ? $peticion->cuerpo()['password_actual'] : '';
        $nueva = $v->contrasena('password_nueva');
        if ($actual === '') {
            $v->error('password_actual', 'Ingresa tu contraseña actual.');
        }
        $v->validar();
        $this->servicio->cambiarContrasena($actual, $nueva);
        return Response::sinContenido();
    }

    /** Cuerpo: {nombres, correo}. Devuelve el usuario actualizado. */
    public function actualizarPerfil(Request $peticion): Response
    {
        $v = new Validador($peticion->cuerpo());
        $nombres = $v->texto('nombres', 150, true, false, 'nombre');
        $correo = $v->correo('correo');
        $v->validar();
        $this->servicio->actualizarPerfil($nombres, $correo);
        return Response::ok($this->servicio->perfil());
    }

    /** Cuerpo: {imagen: "data:image/jpeg;base64,..."} (JPG, PNG o WebP de hasta 3 MB). */
    public function cambiarFoto(Request $peticion): Response
    {
        $imagen = $peticion->cuerpo()['imagen'] ?? null;
        if (!is_string($imagen) || $imagen === '') {
            $v = new Validador([]);
            $v->error('imagen', 'Elige una imagen.');
            $v->validar();
        }
        $this->servicio->cambiarFoto((string) $imagen);
        return Response::ok($this->servicio->perfil());
    }

    public function quitarFoto(Request $peticion): Response
    {
        $this->servicio->quitarFoto();
        return Response::ok($this->servicio->perfil());
    }
}
