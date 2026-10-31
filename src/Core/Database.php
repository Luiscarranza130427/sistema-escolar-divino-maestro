<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use PDO;
use PDOStatement;

/**
 * Envoltorio de PDO: consultas con parámetros tipados, transacciones anidables y paginación.
 * Los repositorios solo usan esta clase y nunca concatenan valores en el SQL.
 * La conexión se abre en la primera consulta: una petición rechazada antes no toca la base.
 */
final class Database
{
    private ?PDO $pdo = null;

    /** @param PDO|Closure(): PDO $conexion */
    public function __construct(private PDO|Closure $conexion)
    {
    }

    public static function conectar(Config $config): self
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config->texto('DB_HOST', 'localhost'),
            $config->texto('DB_PORT', '5432'),
            $config->texto('DB_NAME', 'sistema_escolar')
        );

        return new self(static fn (): PDO => new PDO($dsn, $config->texto('DB_USER', 'postgres'), $config->texto('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]));
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= $this->conexion instanceof PDO ? $this->conexion : ($this->conexion)();
    }

    /** Ejecuta una sentencia enlazando cada parámetro con su tipo (int, bool, null o texto). */
    public function ejecutar(string $sql, array $parametros = []): PDOStatement
    {
        $sentencia = $this->pdo()->prepare($sql);
        foreach ($parametros as $nombre => $valor) {
            $tipo = match (true) {
                is_int($valor) => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                $valor === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $sentencia->bindValue(':' . ltrim((string) $nombre, ':'), $valor, $tipo);
        }
        $sentencia->execute();
        return $sentencia;
    }

    /** @return list<array<string, mixed>> */
    public function filas(string $sql, array $parametros = []): array
    {
        return $this->ejecutar($sql, $parametros)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function fila(string $sql, array $parametros = []): ?array
    {
        $fila = $this->ejecutar($sql, $parametros)->fetch();
        return $fila === false ? null : $fila;
    }

    public function valor(string $sql, array $parametros = []): mixed
    {
        $valor = $this->ejecutar($sql, $parametros)->fetchColumn();
        return $valor === false ? null : $valor;
    }

    /** @return list<mixed> */
    public function columna(string $sql, array $parametros = []): array
    {
        return $this->ejecutar($sql, $parametros)->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Devuelve la cantidad de filas afectadas. */
    public function afectar(string $sql, array $parametros = []): int
    {
        return $this->ejecutar($sql, $parametros)->rowCount();
    }

    /** Para INSERT ... RETURNING id. */
    public function insertar(string $sql, array $parametros = []): int
    {
        return (int) $this->ejecutar($sql, $parametros)->fetchColumn();
    }

    /**
     * Ejecuta $operacion en una transacción. Si ya hay una abierta, se une a ella.
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public function transaccion(callable $operacion): mixed
    {
        if ($this->pdo()->inTransaction()) {
            return $operacion();
        }

        $this->pdo()->beginTransaction();
        try {
            $resultado = $operacion();
            $this->pdo()->commit();
            return $resultado;
        } catch (\Throwable $error) {
            $this->revertirSiHayTransaccion();
            throw $error;
        }
    }

    public function revertirSiHayTransaccion(): void
    {
        // Sin conexión abierta no hay transacción que revertir.
        if ($this->pdo?->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /** Pagina una consulta SELECT completa (con su ORDER BY). */
    public function paginar(string $sql, array $parametros, Paginacion $paginacion): Pagina
    {
        $total = (int) $this->valor("SELECT COUNT(*) FROM ({$sql}) AS conteo", $parametros);
        $filas = $this->filas(
            "{$sql} LIMIT :limite_pagina OFFSET :desplazamiento_pagina",
            $parametros + ['limite_pagina' => $paginacion->porPagina, 'desplazamiento_pagina' => $paginacion->desplazamiento()]
        );
        return new Pagina($filas, $total, $paginacion);
    }
}
