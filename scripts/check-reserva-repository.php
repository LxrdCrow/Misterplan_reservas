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


    echo PHP_EOL . "Prueba de filtros:" . PHP_EOL;

    $reservasConfirmadas = $repository->findByFilters([
        'estado' => 'confirmada',
    ]);

    printf(
        "Reservas confirmadas: %d\n",
        count($reservasConfirmadas)
    );

    $reservasPorNombre = $repository->findByFilters([
        'nombre_huesped' => 'García',
    ]);

    printf(
        "Reservas cuyo nombre contiene García: %d\n",
        count($reservasPorNombre)
    );

    $reservasCombinadas = $repository->findByFilters([
        'estado' => 'confirmada',
        'nombre_huesped' => 'García',
    ]);

    printf(
        "Reservas confirmadas cuyo nombre contiene García: %d\n",
        count($reservasCombinadas)
    );

    }

    echo PHP_EOL . "Prueba de búsqueda por ID:" . PHP_EOL;

    $reserva = $repository->findById(2);

    if ($reserva === null) {
        throw new RuntimeException(
            'No se encontró la reserva de prueba con ID 2.'
        );
    }

    printf(
        "Reserva encontrada: #%d | %s | %s\n",
        (int) $reserva['id'],
        $reserva['nombre_huesped'],
        $reserva['estado']
    );

    $reservaInexistente = $repository->findById(9999);

    if ($reservaInexistente !== null) {
        throw new RuntimeException(
            'Se encontró una reserva inesperada con ID 9999.'
        );
    }

    echo "Reserva inexistente: resultado null correcto.\n";
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Error durante la prueba del repositorio: {$exception->getMessage()}\n"
    );

    exit(1);
}