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

$pdo = null;
$failed = false;

try {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

    $pdo = Database::getConnection();

    $repository = new ReservaRepository($pdo);

    // Inicia una transacción para evitar modificar los datos reales.
    $pdo->beginTransaction();

    echo "Prueba de creación de reservas:\n";

    $id = $repository->create([
        'nombre_huesped' => 'Usuario de prueba',
        'email_huesped' => 'test@example.com',
        'nombre_alojamiento' => 'Alojamiento de prueba',
        'fecha_entrada' => '2030-05-10',
        'fecha_salida' => '2030-05-15',
        'importe' => '250.00',
        'notas' => null,
    ]);

    if ($id <= 0) {
        throw new RuntimeException(
            'No se ha generado un identificador válido.'
        );
    }

    $reserva = $repository->findById($id);

    if ($reserva === null) {
        throw new RuntimeException(
            'No se ha encontrado la reserva creada.'
        );
    }

    if ($reserva['estado'] !== 'pendiente') {
        throw new RuntimeException(
            'El estado inicial de la reserva no es correcto.'
        );
    }

    echo "[OK] Reserva creada correctamente.\n";
    echo "[OK] Estado inicial pendiente verificado.\n";

    echo PHP_EOL . "Prueba de actualización del estado:\n";

    $updated = $repository->updateStatus(
        $id,
        'confirmada'
    );

    if (!$updated) {
        throw new RuntimeException(
            'No se ha podido actualizar el estado.'
        );
    }

    $reservaActualizada = $repository->findById($id);

    if (
        $reservaActualizada === null
        || $reservaActualizada['estado'] !== 'confirmada'
    ) {
        throw new RuntimeException(
            'El estado actualizado no coincide con el esperado.'
        );
    }

    echo "[OK] Estado actualizado correctamente.\n";

    echo PHP_EOL . "Prueba de actualización de una reserva inexistente:\n";

    $updatedNonexistent = $repository->updateStatus(
        PHP_INT_MAX,
        'cancelada'
    );

    if ($updatedNonexistent !== false) {
        throw new RuntimeException(
            'Se ha actualizado una reserva inexistente.'
        );
    }

    echo "[OK] Reserva inexistente no modificada.\n";
} catch (Throwable $exception) {
    $failed = true;

    fwrite(
        STDERR,
        "Error durante las pruebas: {$exception->getMessage()}\n"
    );
} finally {
    // Revierte todas las operaciones realizadas durante las pruebas.
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();

        echo PHP_EOL;
        echo "Transacción revertida: no se han conservado los datos de prueba.\n";
    }
}

if ($failed) {
    exit(1);
}

echo PHP_EOL . "Todas las pruebas han finalizado correctamente.\n";
