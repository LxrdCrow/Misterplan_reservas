<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;

final class ReservaValidator
{
    private const ALLOWED_STATES = [
        'pendiente',
        'confirmada',
        'cancelada',
    ];

    private const ALLOWED_CREATE_FIELDS = [
        'nombre_huesped',
        'email_huesped',
        'nombre_alojamiento',
        'fecha_entrada',
        'fecha_salida',
        'importe',
        'notas',
    ];

    private const MAX_NOTES_LENGTH = 5000;

    /**
     * Valida los datos necesarios para crear una reserva.
     *
     * @param array<string, mixed> $input
     *
     * @return array{
     *     data: array<string, string|null>,
     *     errors: array<string, string>
     * }
     */
    public function validateCreate(array $input): array
    {
        $validatedData = [];
        $errors = [];

        // Comprueba que no se reciban campos no permitidos.
        foreach ($input as $field => $value) {
            if (!in_array($field, self::ALLOWED_CREATE_FIELDS, true)) {
                $errors[(string) $field] =
                    'El campo indicado no está permitido.';
            }
        }

        // Valida el nombre del huésped.
        $this->validateRequiredText(
            $input,
            'nombre_huesped',
            120,
            'El nombre del huésped',
            $validatedData,
            $errors
        );

        // Valida el nombre del alojamiento.
        $this->validateRequiredText(
            $input,
            'nombre_alojamiento',
            160,
            'El nombre del alojamiento',
            $validatedData,
            $errors
        );

        // Valida el correo electrónico.
        if (!array_key_exists('email_huesped', $input)) {
            $errors['email_huesped'] =
                'El correo electrónico es obligatorio.';
        } elseif (!is_string($input['email_huesped'])) {
            $errors['email_huesped'] =
                'El correo electrónico debe ser una cadena de texto.';
        } else {
            $email = trim($input['email_huesped']);

            if ($email === '') {
                $errors['email_huesped'] =
                    'El correo electrónico es obligatorio.';
            } elseif (strlen($email) > 254) {
                $errors['email_huesped'] =
                    'El correo electrónico no puede superar los 254 caracteres.';
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email_huesped'] =
                    'El correo electrónico no tiene un formato válido.';
            } else {
                $validatedData['email_huesped'] = $email;
            }
        }

        // Valida las fechas de entrada y salida.
        $this->validateDateField(
            $input,
            'fecha_entrada',
            'La fecha de entrada',
            $validatedData,
            $errors
        );

        $this->validateDateField(
            $input,
            'fecha_salida',
            'La fecha de salida',
            $validatedData,
            $errors
        );

        // Comprueba que la fecha de salida sea posterior a la de entrada.
        if (
            isset(
                $validatedData['fecha_entrada'],
                $validatedData['fecha_salida']
            )
            && $validatedData['fecha_salida']
                <= $validatedData['fecha_entrada']
        ) {
            $errors['fecha_salida'] =
                'La fecha de salida debe ser posterior a la fecha de entrada.';
        }

        // Valida el importe de la reserva.
        if (!array_key_exists('importe', $input)) {
            $errors['importe'] =
                'El importe es obligatorio.';
        } else {
            $importe = $input['importe'];

            if (is_int($importe)) {
                $importe = (string) $importe;
            } elseif (is_float($importe) && is_finite($importe)) {
                $importe = (string) $importe;
            } elseif (is_string($importe)) {
                $importe = trim($importe);
            } else {
                $importe = null;
            }

            if (
                $importe === null
                || !preg_match(
                    '/^\d{1,8}(?:\.\d{1,2})?$/D',
                    $importe
                )
            ) {
                $errors['importe'] =
                    'El importe debe ser un número positivo o cero, con un máximo de dos decimales y ocho cifras enteras.';
            } else {
                // Normaliza el importe a dos decimales.
                $parts = explode('.', $importe, 2);

                $integerPart = $parts[0];
                $decimalPart = str_pad(
                    $parts[1] ?? '',
                    2,
                    '0'
                );

                $validatedData['importe'] =
                    $integerPart . '.' . $decimalPart;
            }
        }

        // Valida las notas opcionales.
        if (!array_key_exists('notas', $input) || $input['notas'] === null) {
            $validatedData['notas'] = null;
        } elseif (!is_string($input['notas'])) {
            $errors['notas'] =
                'Las notas deben ser una cadena de texto.';
        } else {
            $notas = trim($input['notas']);

            if ($notas === '') {
                $validatedData['notas'] = null;
            } else {
                $length = $this->getUtf8Length($notas);

                if ($length === null) {
                    $errors['notas'] =
                        'Las notas contienen caracteres no válidos.';
                } elseif ($length > self::MAX_NOTES_LENGTH) {
                    $errors['notas'] =
                        'Las notas no pueden superar los 5000 caracteres.';
                } else {
                    $validatedData['notas'] = $notas;
                }
            }
        }

        return [
            'data' => $validatedData,
            'errors' => $errors,
        ];
    }

    /**
     * Valida el estado de una reserva.
     *
     * @param array<string, mixed> $input
     *
     * @return array{
     *     data: array<string, string>,
     *     errors: array<string, string>
     * }
     */
    public function validateStatus(array $input): array
    {
        $validatedData = [];
        $errors = [];

        // Solo se permite modificar el campo estado.
        foreach ($input as $field => $value) {
            if ($field !== 'estado') {
                $errors[(string) $field] =
                    'El campo indicado no está permitido.';
            }
        }

        if (!array_key_exists('estado', $input)) {
            $errors['estado'] =
                'El estado de la reserva es obligatorio.';
        } elseif (!is_string($input['estado'])) {
            $errors['estado'] =
                'El estado debe ser una cadena de texto.';
        } else {
            $estado = trim($input['estado']);

            if (!in_array($estado, self::ALLOWED_STATES, true)) {
                $errors['estado'] =
                    'El estado indicado no es válido.';
            } else {
                $validatedData['estado'] = $estado;
            }
        }

        return [
            'data' => $validatedData,
            'errors' => $errors,
        ];
    }

    /**
     * Valida un campo de texto obligatorio.
     *
     * @param array<string, mixed> $input
     * @param array<string, string|null> $validatedData
     * @param array<string, string> $errors
     */
    private function validateRequiredText(
        array $input,
        string $field,
        int $maxLength,
        string $label,
        array &$validatedData,
        array &$errors
    ): void {
        if (!array_key_exists($field, $input)) {
            $errors[$field] = $label . ' es obligatorio.';
            return;
        }

        if (!is_string($input[$field])) {
            $errors[$field] =
                $label . ' debe ser una cadena de texto.';
            return;
        }

        $value = trim($input[$field]);

        if ($value === '') {
            $errors[$field] = $label . ' es obligatorio.';
            return;
        }

        $length = $this->getUtf8Length($value);

        if ($length === null) {
            $errors[$field] =
                $label . ' contiene caracteres no válidos.';
            return;
        }

        if ($length > $maxLength) {
            $errors[$field] =
                sprintf(
                    '%s no puede superar los %d caracteres.',
                    $label,
                    $maxLength
                );
            return;
        }

        $validatedData[$field] = $value;
    }

    /**
     * Valida un campo de fecha obligatorio.
     *
     * @param array<string, mixed> $input
     * @param array<string, string|null> $validatedData
     * @param array<string, string> $errors
     */
    private function validateDateField(
        array $input,
        string $field,
        string $label,
        array &$validatedData,
        array &$errors
    ): void {
        if (!array_key_exists($field, $input)) {
            $errors[$field] = $label . ' es obligatoria.';
            return;
        }

        if (!is_string($input[$field])) {
            $errors[$field] =
                $label . ' debe ser una cadena de texto.';
            return;
        }

        $date = trim($input[$field]);

        if (!$this->isValidDate($date)) {
            $errors[$field] =
                $label . ' debe tener un formato válido (YYYY-MM-DD).';
            return;
        }

        $validatedData[$field] = $date;
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

    /**
     * Obtiene la longitud de una cadena UTF-8.
     *
     * Devuelve null si la cadena no contiene UTF-8 válido.
     */
    private function getUtf8Length(string $value): ?int
    {
        if (preg_match('//u', $value) !== 1) {
            return null;
        }

        $length = preg_match_all('/./us', $value);

        return $length === false ? null : $length;
    }
}
