<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReservaEventRepository;
use App\Repositories\ReservaRepository;
use App\Validators\ReservaFilterValidator;
use App\Validators\ReservaValidator;
use PDO;
use RuntimeException;
use Throwable;

class ReservaService
{
    private PDO $pdo;

    private ReservaRepository $reservaRepository;

    private ReservaEventRepository $eventRepository;

    private ReservaValidator $reservaValidator;

    private ReservaFilterValidator $filterValidator;

    /**
     * Inicializa las dependencias del servicio.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->reservaRepository = new ReservaRepository($pdo);

        $this->eventRepository = new ReservaEventRepository($pdo);

        $this->reservaValidator = new ReservaValidator();

        $this->filterValidator = new ReservaFilterValidator();
    }

    /**
     * Obtiene las reservas aplicando los filtros disponibles.
     */
    public function findAll(array $filters = []): array
    {
        $validation = $this->filterValidator->validate($filters);

        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'data' => [],
                'errors' => $validation['errors'],
            ];
        }

        $reservas = $this->reservaRepository->findByFilters(
            $validation['data']
        );

        return [
            'success' => true,
            'data' => $reservas,
            'errors' => [],
        ];
    }

    /**
     * Obtiene una reserva junto con su historial de eventos.
     */
    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $reserva = $this->reservaRepository->findById($id);

        if ($reserva === null) {
            return null;
        }

        $reserva['eventos'] = $this->eventRepository->findByReservaId($id);

        return $reserva;
    }

    /**
     * Crea una reserva y registra el evento correspondiente.
     */
    public function create(array $input): array
    {
        $validation = $this->reservaValidator->validateCreate($input);

        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'data' => null,
                'errors' => $validation['errors'],
            ];
        }

        return $this->executeInTransaction(
            function () use ($validation): array {
                $reservaId = $this->reservaRepository->create(
                    $validation['data']
                );

                $this->eventRepository->create(
                    $reservaId,
                    'creacion',
                    'Reserva creada.'
                );

                return [
                    'success' => true,
                    'data' => [
                        'id' => $reservaId,
                    ],
                    'errors' => [],
                ];
            }
        );
    }

    /**
     * Actualiza el estado de una reserva y registra el evento.
     */
    public function updateStatus(int $id, array $input): array
    {
        $validation = $this->reservaValidator->validateStatus($input);

        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'data' => null,
                'errors' => $validation['errors'],
            ];
        }

        $reserva = $this->reservaRepository->findById($id);

        if ($reserva === null) {
            return [
                'success' => false,
                'data' => null,
                'errors' => [
                    'reserva' => 'No se encontró la reserva solicitada.',
                ],
            ];
        }

        $estadoActual = $reserva['estado'];
        $estadoNuevo = $validation['data']['estado'];

        if ($estadoActual === $estadoNuevo) {
            return [
                'success' => true,
                'data' => [
                    'id' => $id,
                    'estado' => $estadoNuevo,
                    'changed' => false,
                ],
                'errors' => [],
            ];
        }

        $transicionesPermitidas = [
            'pendiente' => ['confirmada', 'cancelada'],
            'confirmada' => ['cancelada'],
            'cancelada' => [],
        ];

        if (
            !in_array(
                $estadoNuevo,
                $transicionesPermitidas[$estadoActual] ?? [],
                true
            )
        ) {
            return [
                'success' => false,
                'data' => null,
                'errors' => [
                    'estado' => 'La transición de estado solicitada no está permitida.',
                ],
            ];
        }

        $eventosPorEstado = [
            'confirmada' => [
                'tipo' => 'confirmacion',
                'descripcion' => 'Reserva confirmada.',
            ],
            'cancelada' => [
                'tipo' => 'cancelacion',
                'descripcion' => 'Reserva cancelada.',
            ],
        ];

        $evento = $eventosPorEstado[$estadoNuevo];

        return $this->executeInTransaction(
            function () use (
                $id,
                $estadoNuevo,
                $evento
            ): array {
                $actualizada = $this->reservaRepository->updateStatus(
                    $id,
                    $estadoNuevo
                );

                if (!$actualizada) {
                    throw new RuntimeException(
                        'No se pudo actualizar el estado de la reserva.'
                    );
                }

                $this->eventRepository->create(
                    $id,
                    $evento['tipo'],
                    $evento['descripcion']
                );

                return [
                    'success' => true,
                    'data' => [
                        'id' => $id,
                        'estado' => $estadoNuevo,
                        'changed' => true,
                    ],
                    'errors' => [],
                ];
            }
        );
    }

    /**
     * Ejecuta una operación dentro de una transacción.
     */
    private function executeInTransaction(callable $operation): array
    {
        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $result = $operation();

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if (
                $ownsTransaction
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}

