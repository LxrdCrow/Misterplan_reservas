<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;

// Script de diagnóstico: solo se puede ejecutar desde la línea de comandos.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

    $pdo = Database::getConnection();

    $reservas = (int) $pdo
        ->query('SELECT COUNT(*) FROM reserva')
        ->fetchColumn();

    $eventos = (int) $pdo
        ->query('SELECT COUNT(*) FROM reserva_evento')
        ->fetchColumn();

    echo "Conexión PDO correcta.\n";
    echo "Reservas encontradas: {$reservas}\n";
    echo "Eventos encontrados: {$eventos}\n";
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Error durante la prueba: {$exception->getMessage()}\n"
    );

    exit(1);
}