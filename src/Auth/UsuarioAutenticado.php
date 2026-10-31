<?php
declare(strict_types=1);

namespace App\Auth;

use App\Soporte\FotosPerfil;

final class UsuarioAutenticado
{
    /** @param list<string> $permisos */
    public function __construct(
        public readonly int $id,
        public readonly string $nombres,
        public readonly string $correo,
        public readonly string $rol,
        public readonly array $permisos,
        public readonly ?string $foto = null
    ) {
    }

    public function esAdministrador(): bool
    {
        return in_array($this->rol, Rol::ADMINISTRADORES, true);
    }

    public function puede(string $permiso): bool
    {
        return $this->esAdministrador() || in_array($permiso, $this->permisos, true);
    }

    /** @return array{id: int, nombres: string, correo: string, rol: string, permisos: list<string>, foto: ?string} */
    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'nombres' => $this->nombres,
            'correo' => $this->correo,
            'rol' => $this->rol,
            'permisos' => $this->permisos,
            'foto' => $this->foto === null ? null : FotosPerfil::URL . $this->foto,
        ];
    }
}
