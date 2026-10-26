<?php

declare(strict_types=1);

use App\Config\Database;
use App\Repositories\ReservaRepository;
use Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

    $repository = new ReservaRepository(
        Database::getConnection()
    );

    $reservas = $repository->findAll();

    echo "Reservas encontradas: " . count($reservas) . PHP_EOL;
    echo PHP_EOL;

    foreach (array_slice($reservas, 0, 3) as $reserva) {
        printf(
            "#%s | %s | %s | %s\n",
            (string) $reserva['id'],
            $reserva['nombre_huesped'],
            $reserva['estado'],
            $reserva['fecha_entrada']
        );
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Error al consultar las reservas: {$exception->getMessage()}\n"
    );

    exit(1);
}