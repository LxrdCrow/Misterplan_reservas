<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReservaRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Obtiene todas las reservas ordenadas por fecha de entrada.
     *
     * @return list<array<string, mixed>>
     */
    public function findAll(): array
    {
        $sql = '
            SELECT
                id,
                nombre_huesped,
                email_huesped,
                nombre_alojamiento,
                fecha_entrada,
                fecha_salida,
                estado,
                importe,
                notas,
                fecha_creacion
            FROM reserva
            ORDER BY fecha_entrada ASC, id ASC
        ';

        $statement = $this->pdo->query($sql);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una reserva por su identificador.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = '
            SELECT
                id,
                nombre_huesped,
                email_huesped,
                nombre_alojamiento,
                fecha_entrada,
                fecha_salida,
                estado,
                importe,
                notas,
                fecha_creacion
            FROM reserva
            WHERE id = :id
            LIMIT 1
        ';

        $statement = $this->pdo->prepare($sql);

        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        $statement->execute();

        $reserva = $statement->fetch(PDO::FETCH_ASSOC);

        return $reserva === false ? null : $reserva;
    }


    /**
     * Busca reservas aplicando filtros opcionales combinables.
     *
     * @param array{
     *     estado?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string,
     *     nombre_huesped?: string
     * } $filtros
     *
     * @return list<array<string, mixed>>
     */
    public function findByFilters(array $filtros = []): array
    {
        $sql = '
            SELECT
                id,
                nombre_huesped,
                email_huesped,
                nombre_alojamiento,
                fecha_entrada,
                fecha_salida,
                estado,
                importe,
                notas,
                fecha_creacion
            FROM reserva
        ';

        $conditions = [];
        $parameters = [];

        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $conditions[] = 'estado = :estado';
            $parameters['estado'] = $filtros['estado'];
        }

        if (
            isset($filtros['fecha_desde'])
            && $filtros['fecha_desde'] !== ''
        ) {
            $conditions[] = 'fecha_entrada >= :fecha_desde';
            $parameters['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (
            isset($filtros['fecha_hasta'])
            && $filtros['fecha_hasta'] !== ''
        ) {
            $conditions[] = 'fecha_entrada <= :fecha_hasta';
            $parameters['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (
            isset($filtros['nombre_huesped'])
            && $filtros['nombre_huesped'] !== ''
        ) {
            $conditions[] = 'nombre_huesped LIKE :nombre_huesped';
            $parameters['nombre_huesped'] =
                '%' . $filtros['nombre_huesped'] . '%';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY fecha_entrada ASC, id ASC';

        $statement = $this->pdo->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Crea una nueva reserva.
     *
     * @param array{
     *     nombre_huesped: string,
     *     email_huesped: string,
     *     nombre_alojamiento: string,
     *     fecha_entrada: string,
     *     fecha_salida: string,
     *     importe: string,
     *     notas: string|null
     * } $data
     */
    public function create(array $data): int
    {
        $sql = '
            INSERT INTO reserva (
                nombre_huesped,
                email_huesped,
                nombre_alojamiento,
                fecha_entrada,
                fecha_salida,
                importe,
                notas
            ) VALUES (
                :nombre_huesped,
                :email_huesped,
                :nombre_alojamiento,
                :fecha_entrada,
                :fecha_salida,
                :importe,
                :notas
            )
        ';

        $statement = $this->pdo->prepare($sql);

        $statement->bindValue(
            ':nombre_huesped',
            $data['nombre_huesped'],
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':email_huesped',
            $data['email_huesped'],
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':nombre_alojamiento',
            $data['nombre_alojamiento'],
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':fecha_entrada',
            $data['fecha_entrada'],
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':fecha_salida',
            $data['fecha_salida'],
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':importe',
            $data['importe'],
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':notas',
            $data['notas'],
            $data['notas'] === null
                ? PDO::PARAM_NULL
                : PDO::PARAM_STR
        );

        $statement->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza el estado de una reserva.
     *
     * Devuelve true si se ha modificado una fila.
     */
    public function updateStatus(int $id, string $estado): bool
    {
        $sql = '
            UPDATE reserva
            SET estado = :estado
            WHERE id = :id
        ';

        $statement = $this->pdo->prepare($sql);

        $statement->bindValue(
            ':estado',
            $estado,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->rowCount() > 0;
    }


}