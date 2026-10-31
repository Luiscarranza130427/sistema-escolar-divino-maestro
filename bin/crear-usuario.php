<?php
declare(strict_types=1);

/**
 * Crea un usuario desde la consola (por ejemplo, el primer director).
 * Uso: php bin/crear-usuario.php correo@colegio.pe "Nombres" director|asistente|hiperadmin [contraseña]
 * Sin contraseña se genera una aleatoria y se muestra una sola vez.
 */

use App\Auth\Rol;
use App\Core\Config;
use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$raiz = dirname(__DIR__);
require $raiz . '/vendor/autoload.php';

[, $correo, $nombres, $rol, $password] = $argv + ['', '', '', '', ''];
$correo = mb_strtolower(trim($correo));
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || trim($nombres) === '' || !in_array($rol, Rol::TODOS, true)) {
    fwrite(STDERR, 'Uso: php bin/crear-usuario.php correo "Nombres" ' . implode('|', Rol::TODOS) . " [contraseña]\n");
    exit(1);
}
$generada = $password === '';
if ($generada) {
    $password = bin2hex(random_bytes(8));
} elseif (strlen($password) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}

$db = Database::conectar(Config::desdeEntorno($raiz));
if ($db->valor('SELECT 1 FROM usuario WHERE LOWER(correo) = :correo', ['correo' => $correo]) !== null) {
    fwrite(STDERR, "Ya existe un usuario con ese correo.\n");
    exit(1);
}
$id = $db->insertar(
    'INSERT INTO usuario (nombres, correo, password_hash, rol) VALUES (:nombres, :correo, :hash, :rol) RETURNING id',
    ['nombres' => trim($nombres), 'correo' => $correo, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'rol' => $rol]
);

echo "Usuario creado (ID {$id}, rol {$rol}).\n";
if ($generada) {
    echo "Contraseña generada: {$password}\n";
}
