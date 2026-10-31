<?php
declare(strict_types=1);

namespace App\Modulos\Auth;

use App\Auth\Contexto;
use App\Auth\ServicioTokens;
use App\Auth\UsuarioAutenticado;
use App\Http\HttpException;
use App\Modulos\Auditoria\Auditor;
use App\Modulos\Usuarios\UsuarioRepository;
use App\Soporte\FotosPerfil;

final class AuthService
{
    public function __construct(
        private UsuarioRepository $usuarios,
        private ServicioTokens $tokens,
        private Contexto $contexto,
        private Auditor $auditor,
        private FotosPerfil $fotos
    ) {
    }

    /** @return array{token: string, tipo: string, expira_en: string, usuario: array<string, mixed>} */
    public function iniciarSesion(string $correo, string $password, string $dispositivo): array
    {
        $usuario = $this->usuarios->buscarActivoPorCorreo($correo);
        // Se verifica siempre un hash para no revelar por tiempo de respuesta si el correo existe.
        $hash = $usuario['password_hash'] ?? password_hash('usuario-inexistente', PASSWORD_DEFAULT);
        if (!password_verify($password, (string) $hash) || $usuario === null) {
            throw HttpException::noAutenticado('Correo o contraseña incorrectos.');
        }

        $id = (int) $usuario['id'];
        if (password_needs_rehash((string) $hash, PASSWORD_DEFAULT)) {
            $this->usuarios->actualizarHash($id, password_hash($password, PASSWORD_DEFAULT));
        }

        $sesion = $this->tokens->emitir($id, $dispositivo);
        $this->auditor->registrar('inicio_sesion', 'Inicio de sesión correcto.', $id);

        $autenticado = new UsuarioAutenticado(
            $id,
            (string) $usuario['nombres'],
            (string) $usuario['correo'],
            (string) $usuario['rol'],
            $this->tokens->permisosDe($id, (string) $usuario['rol']),
            $usuario['foto'] === null ? null : (string) $usuario['foto']
        );
        return ['token' => $sesion['token'], 'tipo' => 'Bearer', 'expira_en' => $sesion['expira_en'], 'usuario' => $autenticado->aArreglo()];
    }

    public function cerrarSesion(): void
    {
        $token = $this->contexto->token();
        if ($token !== null) {
            $this->tokens->revocar($token);
        }
    }

    /** Cambia la contraseña y cierra las demás sesiones del usuario. */
    public function cambiarContrasena(string $actual, string $nueva): void
    {
        $usuario = $this->contexto->usuario();
        if (!password_verify($actual, (string) $this->usuarios->hashDe($usuario->id))) {
            throw HttpException::regla('La contraseña actual no es correcta.', 'password_actual');
        }
        $this->usuarios->actualizarHash($usuario->id, password_hash($nueva, PASSWORD_DEFAULT));
        $this->tokens->revocarTodos($usuario->id, $this->contexto->token());
        $this->auditor->registrar('cambio_contrasena', 'Cambió su contraseña.');
    }

    /** Actualiza el nombre y el correo del usuario que inició sesión. */
    public function actualizarPerfil(string $nombres, string $correo): void
    {
        $usuario = $this->contexto->usuario();
        if ($this->usuarios->correoDeOtro($correo, $usuario->id)) {
            throw HttpException::regla('Ese correo ya lo usa otra cuenta.', 'correo');
        }
        $this->usuarios->actualizarPerfil($usuario->id, $nombres, $correo);
        $this->auditor->registrar('editar_perfil', 'Actualizó los datos de su perfil.');
    }

    /** Reemplaza la foto de perfil y borra la anterior. Devuelve el nombre del archivo nuevo. */
    public function cambiarFoto(string $dataUrl): string
    {
        $id = $this->contexto->usuario()->id;
        $anterior = $this->usuarios->fotoDe($id);
        $nueva = $this->fotos->guardar($dataUrl);
        $this->usuarios->actualizarFoto($id, $nueva);
        $this->fotos->eliminar($anterior);
        $this->auditor->registrar('editar_perfil', 'Cambió su foto de perfil.');
        return $nueva;
    }

    public function quitarFoto(): void
    {
        $id = $this->contexto->usuario()->id;
        $anterior = $this->usuarios->fotoDe($id);
        $this->usuarios->actualizarFoto($id, null);
        $this->fotos->eliminar($anterior);
    }

    /**
     * Datos del usuario recién leídos de la base (tras editar el perfil).
     * @return array<string, mixed>
     */
    public function perfil(): array
    {
        $actual = $this->contexto->usuario();
        $fila = $this->usuarios->perfilDe($actual->id) ?? throw HttpException::noEncontrado('No se encontró tu cuenta.');
        return (new UsuarioAutenticado(
            $actual->id,
            (string) $fila['nombres'],
            (string) $fila['correo'],
            $actual->rol,
            $actual->permisos,
            $fila['foto'] === null ? null : (string) $fila['foto']
        ))->aArreglo();
    }
}
