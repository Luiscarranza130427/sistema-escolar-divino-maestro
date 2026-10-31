<?php
declare(strict_types=1);

/**
 * Aplica el esquema y las migraciones pendientes. Uso: php bin/migrar.php
 * Cada archivo se ejecuta una sola vez y queda registrado en la tabla schema_migracion.
 */

use App\Core\Config;
use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$raiz = dirname(__DIR__);
require $raiz . '/vendor/autoload.php';

$pdo = Database::conectar(Config::desdeEntorno($raiz))->pdo();
$carpeta = $raiz . '/database';
$archivos = array_merge(
    [$carpeta . '/schema.sql', $carpeta . '/permissions.sql'],
    glob($carpeta . '/migraciones/*.sql') ?: []
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migracion (
        archivo VARCHAR(255) PRIMARY KEY,
        aplicado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )'
);
$aplicados = array_flip($pdo->query('SELECT archivo FROM schema_migracion')->fetchAll(PDO::FETCH_COLUMN));
$registrar = $pdo->prepare('INSERT INTO schema_migracion (archivo) VALUES (:archivo)');

$nuevos = 0;
foreach ($archivos as $ruta) {
    $archivo = str_replace(chr(92), '/', substr($ruta, strlen($carpeta) + 1));
    if (isset($aplicados[$archivo])) {
        continue;
    }
    echo "Aplicando {$archivo}... ";
    try {
        $pdo->beginTransaction();
        $pdo->exec((string) file_get_contents($ruta));
        $registrar->execute(['archivo' => $archivo]);
        $pdo->commit();
        echo "OK\n";
        $nuevos++;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, "ERROR\n" . $error->getMessage() . "\n");
        exit(1);
    }
}

echo $nuevos === 0 ? "La base de datos ya está al día.\n" : "Migraciones aplicadas: {$nuevos}.\n";
