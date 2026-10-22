<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Fábrica de la conexión PDO a MySQL/MariaDB.
 *
 * Lee la configuración de variables de entorno (DB_HOST, DB_NAME,
 * DB_USER, DB_PASSWORD y, opcionalmente, DB_PORT) y reutiliza
 * la misma conexión durante toda la petición.
 */
final class Database
{
    private const DEFAULT_PORT = 3306;

    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = self::getRequiredEnv('DB_HOST');
        $database = self::getRequiredEnv('DB_NAME');
        $username = self::getRequiredEnv('DB_USER');
        $password = self::getRequiredEnv('DB_PASSWORD');
        $port = self::getPort();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        try {
            self::$connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            // El detalle técnico solo se registra en el log del servidor.
            // No se pasa $e como "previous" para que ni el mensaje ni la
            // traza (que puede incluir credenciales) lleguen al cliente.
            error_log(
                sprintf(
                    'Error de conexión a la base de datos (código %s): %s',
                    (string) $e->getCode(),
                    $e->getMessage()
                )
            );

            throw new RuntimeException(
                'No se pudo establecer la conexión con la base de datos.'
            );
        }

        return self::$connection;
    }

    private static function getPort(): int
    {
        $port = filter_var(
            self::readEnv('DB_PORT') ?? (string) self::DEFAULT_PORT,
            FILTER_VALIDATE_INT,
            ['options' => [
                'min_range' => 1,
                'max_range' => 65535,
            ]]
        );

        if ($port === false) {
            throw new RuntimeException(
                'La variable de entorno DB_PORT debe ser un número entre 1 y 65535.'
            );
        }

        return $port;
    }

    private static function getRequiredEnv(string $name): string
    {
        $value = self::readEnv($name);

        if ($value === null || trim($value) === '') {
            throw new RuntimeException(
                sprintf('La variable de entorno %s falta o está vacía.', $name)
            );
        }

        return $value;
    }

    /**
     * Busca la variable en $_ENV, $_SERVER y getenv(), porque según
     * la configuración de PHP (variables_order) y del hosting,
     * $_ENV puede llegar vacío.
     */
    private static function readEnv(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) ? $value : null;
    }
}