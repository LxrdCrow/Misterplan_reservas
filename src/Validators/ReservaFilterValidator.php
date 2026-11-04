<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;

final class ReservaFilterValidator
{
    private const ALLOWED_STATES = [
        'pendiente',
        'confirmada',
        'cancelada',
    ];

    private const ALLOWED_FILTERS = [
        'estado',
        'fecha_desde',
        'fecha_hasta',
        'nombre_huesped',
    ];

    /**
     * Valida y normaliza los filtros de búsqueda de reservas.
     *
     * @param array<string, mixed> $filters
     *
     * @return array{
     *     data: array{
     *         estado?: string,
     *         fecha_desde?: string,
     *         fecha_hasta?: string,
     *         nombre_huesped?: string
     *     },
     *     errors: array<string, string>
     * }
     */
    public function validate(array $filters): array
    {
        $validatedData = [];
        $errors = [];

        // Comprueba que no se hayan recibido filtros desconocidos.
        foreach ($filters as $name => $value) {
            if (!in_array($name, self::ALLOWED_FILTERS, true)) {
                $errors[(string) $name] =
                    'El filtro indicado no está permitido.';
            }
        }

        // Valida el estado de la reserva.
        if (array_key_exists('estado', $filters)) {
            $estado = $filters['estado'];

            if (!is_string($estado)) {
                $errors['estado'] =
                    'El estado debe ser una cadena de texto.';
            } else {
                $estado = trim($estado);

                if ($estado !== '') {
                    if (!in_array(
                        $estado,
                        self::ALLOWED_STATES,
                        true
                    )) {
                        $errors['estado'] =
                            'El estado indicado no es válido.';
                    } else {
                        $validatedData['estado'] = $estado;
                    }
                }
            }
        }

        // Valida la fecha inicial.
        if (array_key_exists('fecha_desde', $filters)) {
            $fechaDesde = $filters['fecha_desde'];

            if (!is_string($fechaDesde)) {
                $errors['fecha_desde'] =
                    'La fecha inicial debe ser una cadena de texto.';
            } else {
                $fechaDesde = trim($fechaDesde);

                if ($fechaDesde !== '') {
                    if (!$this->isValidDate($fechaDesde)) {
                        $errors['fecha_desde'] =
                            'La fecha inicial debe tener el formato YYYY-MM-DD y ser válida.';
                    } else {
                        $validatedData['fecha_desde'] = $fechaDesde;
                    }
                }
            }
        }

        // Valida la fecha final.
        if (array_key_exists('fecha_hasta', $filters)) {
            $fechaHasta = $filters['fecha_hasta'];

            if (!is_string($fechaHasta)) {
                $errors['fecha_hasta'] =
                    'La fecha final debe ser una cadena de texto.';
            } else {
                $fechaHasta = trim($fechaHasta);

                if ($fechaHasta !== '') {
                    if (!$this->isValidDate($fechaHasta)) {
                        $errors['fecha_hasta'] =
                            'La fecha final debe tener el formato YYYY-MM-DD y ser válida.';
                    } else {
                        $validatedData['fecha_hasta'] = $fechaHasta;
                    }
                }
            }
        }

        // Comprueba que el intervalo de fechas sea coherente.
        if (
            isset(
                $validatedData['fecha_desde'],
                $validatedData['fecha_hasta']
            )
            && $validatedData['fecha_desde']
                > $validatedData['fecha_hasta']
        ) {
            $errors['fecha_desde'] =
                'La fecha inicial no puede ser posterior a la fecha final.';
        }

        // Valida el nombre del huésped.
        if (array_key_exists('nombre_huesped', $filters)) {
            $nombre = $filters['nombre_huesped'];

            if (!is_string($nombre)) {
                $errors['nombre_huesped'] =
                    'El nombre del huésped debe ser una cadena de texto.';
            } else {
                $nombre = trim($nombre);

                if ($nombre !== '') {
                    $validatedData['nombre_huesped'] = $nombre;
                }
            }
        }

        return [
            'data' => $validatedData,
            'errors' => $errors,
        ];
    }

    /**
     * Comprueba que una fecha tenga el formato YYYY-MM-DD
     * y represente una fecha válida.
     */
    private function isValidDate(string $date): bool
    {
        $dateObject = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        $dateErrors = DateTimeImmutable::getLastErrors();

        return $dateObject !== false
            && (
                $dateErrors === false
                || (
                    $dateErrors['warning_count'] === 0
                    && $dateErrors['error_count'] === 0
                )
            )
            && $dateObject->format('Y-m-d') === $date;
    }
}