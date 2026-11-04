<?php

declare(strict_types=1);

use App\Validators\ReservaFilterValidator;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $validator = new ReservaFilterValidator();

    $tests = [
        [
            'name' => 'Sin filtros',
            'filters' => [],
            'valid' => true,
            'expected_data' => [],
            'expected_errors' => [],
        ],
        [
            'name' => 'Estado válido',
            'filters' => [
                'estado' => 'confirmada',
            ],
            'valid' => true,
            'expected_data' => [
                'estado' => 'confirmada',
            ],
            'expected_errors' => [],
        ],
        [
            'name' => 'Intervalo de fechas válido',
            'filters' => [
                'fecha_desde' => '2026-11-01',
                'fecha_hasta' => '2026-11-30',
            ],
            'valid' => true,
            'expected_data' => [
                'fecha_desde' => '2026-11-01',
                'fecha_hasta' => '2026-11-30',
            ],
            'expected_errors' => [],
        ],
        [
            'name' => 'Nombre con espacios adicionales',
            'filters' => [
                'nombre_huesped' => '  Daniel García  ',
            ],
            'valid' => true,
            'expected_data' => [
                'nombre_huesped' => 'Daniel García',
            ],
            'expected_errors' => [],
        ],
        [
            'name' => 'Combinación de todos los filtros',
            'filters' => [
                'estado' => 'confirmada',
                'fecha_desde' => '2026-11-01',
                'fecha_hasta' => '2026-11-30',
                'nombre_huesped' => 'Daniel',
            ],
            'valid' => true,
            'expected_data' => [
                'estado' => 'confirmada',
                'fecha_desde' => '2026-11-01',
                'fecha_hasta' => '2026-11-30',
                'nombre_huesped' => 'Daniel',
            ],
            'expected_errors' => [],
        ],
        [
            'name' => 'Estado no permitido',
            'filters' => [
                'estado' => 'desconocido',
            ],
            'valid' => false,
            'expected_data' => [],
            'expected_errors' => ['estado'],
        ],
        [
            'name' => 'Formato de fecha incorrecto',
            'filters' => [
                'fecha_desde' => '04-11-2026',
            ],
            'valid' => false,
            'expected_data' => [],
            'expected_errors' => ['fecha_desde'],
        ],
        [
            'name' => 'Fecha inexistente',
            'filters' => [
                'fecha_desde' => '2026-02-30',
            ],
            'valid' => false,
            'expected_data' => [],
            'expected_errors' => ['fecha_desde'],
        ],
        [
            'name' => 'Intervalo de fechas invertido',
            'filters' => [
                'fecha_desde' => '2026-11-30',
                'fecha_hasta' => '2026-11-01',
            ],
            'valid' => false,
            'expected_data' => [
                'fecha_desde' => '2026-11-30',
                'fecha_hasta' => '2026-11-01',
            ],
            'expected_errors' => ['fecha_desde'],
        ],
        [
            'name' => 'Filtro desconocido',
            'filters' => [
                'admin' => 'true',
            ],
            'valid' => false,
            'expected_data' => [],
            'expected_errors' => ['admin'],
        ],
        [
            'name' => 'Tipo de dato incorrecto',
            'filters' => [
                'estado' => ['confirmada'],
            ],
            'valid' => false,
            'expected_data' => [],
            'expected_errors' => ['estado'],
        ],
    ];

    $passed = 0;
    $failed = 0;

    echo "Pruebas del validador de filtros:\n\n";

    foreach ($tests as $test) {
        $result = $validator->validate($test['filters']);

        $dataMatches = $result['data'] === $test['expected_data'];

        $errorsMatch = $test['valid']
            ? $result['errors'] === $test['expected_errors']
            : array_keys($result['errors']) === $test['expected_errors'];

        if ($dataMatches && $errorsMatch) {
            printf("[OK] %s\n", $test['name']);
            $passed++;
        } else {
            printf("[ERROR] %s\n", $test['name']);

            print_r([
                'expected_data' => $test['expected_data'],
                'actual_data' => $result['data'],
                'expected_errors' => $test['expected_errors'],
                'actual_errors' => $result['errors'],
            ]);

            $failed++;
        }
    }

    echo PHP_EOL;
    printf("Pruebas superadas: %d\n", $passed);
    printf("Pruebas fallidas: %d\n", $failed);

    if ($failed > 0) {
        exit(1);
    }

    echo "\nTodas las pruebas han finalizado correctamente.\n";
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Error durante las pruebas: {$exception->getMessage()}\n"
    );

    exit(1);
}