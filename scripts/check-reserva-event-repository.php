<?php

declare(strict_types=1);

use App\Config\Database;
use App\Repositories\ReservaEventRepository;
use Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$pdo = null;
$testPassed = false;

try {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

    $pdo = Database::getConnection();

    $repository = new ReservaEventRepository($pdo);

    $pdo->beginTransaction();

    echo "Prueba de creación de eventos:" . PHP_EOL;

    $reservaId = 2;
    $tipo = 'confirmacion';
    $descripcion = 'Evento de prueba del repositorio.';

    $eventoId = $repository->create(
        $reservaId,
        $tipo,
        $descripcion
    );

    if ($eventoId <= 0) {
        throw new RuntimeException(
            'No se pudo obtener el ID del evento creado.'
        );
    }

    echo "[OK] Evento creado correctamente." . PHP_EOL;

    echo PHP_EOL . "Prueba de consulta del historial:" . PHP_EOL;

    $eventos = $repository->findByReservaId($reservaId);

    $eventoEncontrado = null;

    foreach ($eventos as $evento) {
        if ((int) $evento['id'] === $eventoId) {
            $eventoEncontrado = $evento;
            break;
        }
    }

    if ($eventoEncontrado === null) {
        throw new RuntimeException(
            'El evento creado no aparece en el historial.'
        );
    }

    if (
        (int) $eventoEncontrado['reserva_id'] !== $reservaId
        || $eventoEncontrado['tipo'] !== $tipo
        || $eventoEncontrado['descripcion'] !== $descripcion
    ) {
        throw new RuntimeException(
            'Los datos del evento recuperado no coinciden.'
        );
    }

    echo "[OK] Evento encontrado en el historial." . PHP_EOL;

    printf(
        "Evento #%d | Reserva #%d | %s | %s\n",
        (int) $eventoEncontrado['id'],
        (int) $eventoEncontrado['reserva_id'],
        $eventoEncontrado['tipo'],
        $eventoEncontrado['descripcion']
    );

    printf(
        "Total de eventos asociados a la reserva: %d\n",
        count($eventos)
    );

    $testPassed = true;
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Error durante la prueba del repositorio de eventos: "
        . $exception->getMessage()
        . PHP_EOL
    );
} finally {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();

        echo PHP_EOL;
        echo "Transacción revertida: no se han conservado "
            . "los datos de prueba."
            . PHP_EOL;
    }
}

if (!$testPassed) {
    exit(1);
}

echo "Todas las pruebas han finalizado correctamente." . PHP_EOL;
