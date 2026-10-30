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

}