<?php

declare(strict_types=1);

use App\Validators\ReservaValidator;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $validator = new ReservaValidator();

    $validCreate = [
        'nombre_huesped' => 'Daniel García',
        'email_huesped' => 'daniel@example.com',
        'nombre_alojamiento' => 'Casa Sol',
        'fecha_entrada' => '2026-11-10',
        'fecha_salida' => '2026-11-15',
        'importe' => '125.50',
    ];

    $tests = [
        [
            'name' => 'Creación válida y normalización',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'nombre_huesped' => '  Daniel García  ',
                'nombre_alojamiento' => ' Casa Sol ',
                'importe' => 125.5,
                'notas' => ' Llegada tardía ',
            ]),
            'expected_data' => [
                'nombre_huesped' => 'Daniel García',
                'nombre_alojamiento' => 'Casa Sol',
                'email_huesped' => 'daniel@example.com',
                'fecha_entrada' => '2026-11-10',
                'fecha_salida' => '2026-11-15',
                'importe' => '125.50',
                'notas' => 'Llegada tardía',
            ],
            'expected_errors' => [],
        ],
        [
            'name' => 'Notas opcionales',
            'method' => 'create',
            'input' => $validCreate,
            'expected_data' => [
                'nombre_huesped' => 'Daniel García',
                'nombre_alojamiento' => 'Casa Sol',
                'email_huesped' => 'daniel@example.com',
                'fecha_entrada' => '2026-11-10',
                'fecha_salida' => '2026-11-15',
                'importe' => '125.50',
                'notas' => null,
            ],
            'expected_errors' => [],
        ],
        [
            'name' => 'Campos obligatorios ausentes',
            'method' => 'create',
            'input' => [],
            'expected_errors' => [
                'nombre_huesped',
                'nombre_alojamiento',
                'email_huesped',
                'fecha_entrada',
                'fecha_salida',
                'importe',
            ],
        ],
        [
            'name' => 'Correo electrónico inválido',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'email_huesped' => 'correo-invalido',
            ]),
            'expected_errors' => ['email_huesped'],
        ],
        [
            'name' => 'Nombre del huésped demasiado largo',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'nombre_huesped' => str_repeat('A', 121),
            ]),
            'expected_errors' => ['nombre_huesped'],
        ],
        [
            'name' => 'Nombre del alojamiento demasiado largo',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'nombre_alojamiento' => str_repeat('A', 161),
            ]),
            'expected_errors' => ['nombre_alojamiento'],
        ],
        [
            'name' => 'Fecha inexistente',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'fecha_entrada' => '2026-02-30',
            ]),
            'expected_errors' => ['fecha_entrada'],
        ],
        [
            'name' => 'Fecha de salida no posterior a la entrada',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'fecha_salida' => '2026-11-10',
            ]),
            'expected_errors' => ['fecha_salida'],
        ],
        [
            'name' => 'Importe negativo',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'importe' => '-10.00',
            ]),
            'expected_errors' => ['importe'],
        ],
        [
            'name' => 'Importe con demasiados decimales',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'importe' => '125.555',
            ]),
            'expected_errors' => ['importe'],
        ],
        [
            'name' => 'Importe superior al límite permitido',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'importe' => '100000000.00',
            ]),
            'expected_errors' => ['importe'],
        ],
        [
            'name' => 'Campo no permitido durante la creación',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'estado' => 'confirmada',
            ]),
            'expected_errors' => ['estado'],
        ],
        [
            'name' => 'Notas demasiado largas',
            'method' => 'create',
            'input' => array_merge($validCreate, [
                'notas' => str_repeat('A', 5001),
            ]),
            'expected_errors' => ['notas'],
        ],
        [
            'name' => 'Estado válido',
            'method' => 'status',
            'input' => [
                'estado' => 'confirmada',
            ],
            'expected_data' => [
                'estado' => 'confirmada',
            ],
            'expected_errors' => [],
        ],
        [
            'name' => 'Estado no permitido',
            'method' => 'status',
            'input' => [
                'estado' => 'desconocido',
            ],
            'expected_errors' => ['estado'],
        ],
        [
            'name' => 'Estado ausente',
            'method' => 'status',
            'input' => [],
            'expected_errors' => ['estado'],
        ],
        [
            'name' => 'Tipo de estado incorrecto',
            'method' => 'status',
            'input' => [
                'estado' => ['confirmada'],
            ],
            'expected_errors' => ['estado'],
        ],
        [
            'name' => 'Campo adicional en la actualización del estado',
            'method' => 'status',
            'input' => [
                'estado' => 'confirmada',
                'admin' => true,
            ],
            'expected_errors' => ['admin'],
        ],
    ];

    $passed = 0;
    $failed = 0;

    echo "Pruebas del validador de reservas:\n\n";

    foreach ($tests as $test) {
        $result = $test['method'] === 'create'
            ? $validator->validateCreate($test['input'])
            : $validator->validateStatus($test['input']);

        $errorsMatch = array_keys($result['errors'])
            === $test['expected_errors'];

        $dataMatches = !array_key_exists('expected_data', $test)
            || $result['data'] === $test['expected_data'];

        if ($errorsMatch && $dataMatches) {
            printf("[OK] %s\n", $test['name']);
            $passed++;
        } else {
            printf("[ERROR] %s\n", $test['name']);

            print_r([
                'expected_data' => $test['expected_data'] ?? null,
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
