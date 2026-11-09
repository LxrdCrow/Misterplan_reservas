<?php

declare(strict_types=1);

use App\Config\Database;
use App\Services\ReservaService;
use Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$pdo = null;
$createdId = null;
$testPassed = false;

try {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

    $pdo = Database::getConnection();

    $service = new ReservaService($pdo);

    $pdo->beginTransaction();

    echo "Prueba de consulta de reservas:" . PHP_EOL;

    $resultado = $service->findAll();

    if (
        !$resultado['success']
        || !is_array($resultado['data'])
    ) {
        throw new RuntimeException(
            'No se pudieron recuperar las reservas.'
        );
    }

    printf(
        "[OK] Se encontraron %d reservas.\n",
        count($resultado['data'])
    );

    echo PHP_EOL . "Prueba de consulta por ID:" . PHP_EOL;

    $reservaExistente = $service->findById(2);

    if (
        $reservaExistente === null
        || !isset($reservaExistente['eventos'])
        || !is_array($reservaExistente['eventos'])
    ) {
        throw new RuntimeException(
            'No se pudo recuperar la reserva con su historial.'
        );
    }

    echo "[OK] Reserva recuperada con su historial de eventos." . PHP_EOL;

    echo PHP_EOL . "Prueba de validación de filtros:" . PHP_EOL;

    $filtrosInvalidos = $service->findAll([
        'campo_inexistente' => 'valor',
    ]);

    if ($filtrosInvalidos['success'] !== false) {
        throw new RuntimeException(
            'Se aceptaron filtros no permitidos.'
        );
    }

    echo "[OK] Los filtros no permitidos fueron rechazados." . PHP_EOL;

    echo PHP_EOL . "Prueba de creación de reservas:" . PHP_EOL;

    $resultadoCreacion = $service->create([
        'nombre_huesped' => 'Huesped de prueba',
        'email_huesped' => 'service-test@example.com',
        'nombre_alojamiento' => 'Alojamiento de prueba',
        'fecha_entrada' => '2030-05-10',
        'fecha_salida' => '2030-05-15',
        'importe' => '250.00',
        'notas' => 'Reserva temporal para pruebas del servicio.',
    ]);

    if (
        !$resultadoCreacion['success']
        || !isset($resultadoCreacion['data']['id'])
    ) {
        throw new RuntimeException(
            'No se pudo crear la reserva de prueba.'
        );
    }

    $createdId = (int) $resultadoCreacion['data']['id'];

    echo "[OK] Reserva creada correctamente." . PHP_EOL;

    $reservaCreada = $service->findById($createdId);

    if (
        $reservaCreada === null
        || $reservaCreada['estado'] !== 'pendiente'
    ) {
        throw new RuntimeException(
            'La reserva creada no tiene el estado inicial esperado.'
        );
    }

    $eventoCreacionEncontrado = false;

    foreach ($reservaCreada['eventos'] as $evento) {
        if ($evento['tipo'] === 'creacion') {
            $eventoCreacionEncontrado = true;
            break;
        }
    }

    if (!$eventoCreacionEncontrado) {
        throw new RuntimeException(
            'No se registró el evento de creación.'
        );
    }

    echo "[OK] Estado inicial y evento de creación verificados." . PHP_EOL;

    echo PHP_EOL . "Prueba de actualización del estado:" . PHP_EOL;

    $resultadoActualizacion = $service->updateStatus(
        $createdId,
        ['estado' => 'confirmada']
    );

    if (
        !$resultadoActualizacion['success']
        || $resultadoActualizacion['data']['changed'] !== true
    ) {
        throw new RuntimeException(
            'No se pudo confirmar la reserva.'
        );
    }

    $reservaConfirmada = $service->findById($createdId);

    if (
        $reservaConfirmada === null
        || $reservaConfirmada['estado'] !== 'confirmada'
    ) {
        throw new RuntimeException(
            'El estado de la reserva no se actualizó correctamente.'
        );
    }

    echo "[OK] Reserva confirmada correctamente." . PHP_EOL;

    echo PHP_EOL . "Prueba de actualización al mismo estado:" . PHP_EOL;

    $cantidadEventos = count($reservaConfirmada['eventos']);

    $resultadoSinCambios = $service->updateStatus(
        $createdId,
        ['estado' => 'confirmada']
    );

    if (
        !$resultadoSinCambios['success']
        || $resultadoSinCambios['data']['changed'] !== false
    ) {
        throw new RuntimeException(
            'La actualización del mismo estado produjo un resultado inesperado.'
        );
    }

    $reservaSinCambios = $service->findById($createdId);

    if (count($reservaSinCambios['eventos']) !== $cantidadEventos) {
        throw new RuntimeException(
            'Se registró un evento duplicado para el mismo estado.'
        );
    }

    echo "[OK] No se generaron cambios ni eventos duplicados." . PHP_EOL;

    echo PHP_EOL . "Prueba de transición no permitida:" . PHP_EOL;

    $resultadoTransicionInvalida = $service->updateStatus(
        $createdId,
        ['estado' => 'pendiente']
    );

    if ($resultadoTransicionInvalida['success'] !== false) {
        throw new RuntimeException(
            'Se permitió una transición de estado no autorizada.'
        );
    }

    echo "[OK] La transición confirmada -> pendiente fue rechazada." . PHP_EOL;

    echo PHP_EOL . "Prueba de estado inválido:" . PHP_EOL;

    $resultadoEstadoInvalido = $service->updateStatus(
        $createdId,
        ['estado' => 'desconocido']
    );

    if ($resultadoEstadoInvalido['success'] !== false) {
        throw new RuntimeException(
            'Se aceptó un estado no válido.'
        );
    }

    echo "[OK] El estado no válido fue rechazado." . PHP_EOL;

    echo PHP_EOL . "Prueba de reserva inexistente:" . PHP_EOL;

    $resultadoReservaInexistente = $service->updateStatus(
        PHP_INT_MAX,
        ['estado' => 'cancelada']
    );

    if ($resultadoReservaInexistente['success'] !== false) {
        throw new RuntimeException(
            'Se actualizó una reserva inexistente.'
        );
    }

    echo "[OK] La reserva inexistente fue rechazada." . PHP_EOL;

    $pdo->rollBack();

    echo PHP_EOL;
    echo "Transacción revertida: no se han conservado "
        . "los datos de prueba."
        . PHP_EOL;

    $reservaTrasRollback = $service->findById($createdId);

    if ($reservaTrasRollback !== null) {
        throw new RuntimeException(
            'La reserva de prueba sigue existiendo después del rollback.'
        );
    }

    echo "[OK] Se verificó la eliminación de los datos temporales." . PHP_EOL;

    $testPassed = true;
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Error durante las pruebas del servicio: "
        . $exception->getMessage()
        . PHP_EOL
    );
} finally {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();

        echo PHP_EOL;
        echo "Transacción de prueba revertida." . PHP_EOL;
    }
}

if (!$testPassed) {
    exit(1);
}

echo PHP_EOL . "Todas las pruebas del servicio han finalizado correctamente." . PHP_EOL;
