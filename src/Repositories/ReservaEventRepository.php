<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ReservaEventRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra un evento asociado a una reserva.
     */
    public function create(
        int $reservaId,
        string $tipo,
        string $descripcion
    ): int {
        $sql = '
            INSERT INTO reserva_evento (
                reserva_id,
                tipo,
                descripcion
            ) VALUES (
                :reserva_id,
                :tipo,
                :descripcion
            )
        ';

        $statement = $this->pdo->prepare($sql);

        $statement->bindValue(
            ':reserva_id',
            $reservaId,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':tipo',
            $tipo,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':descripcion',
            $descripcion,
            PDO::PARAM_STR
        );

        $statement->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Obtiene el historial de eventos de una reserva.
     */
    public function findByReservaId(int $reservaId): array
    {
        $sql = '
            SELECT
                id,
                reserva_id,
                tipo,
                descripcion,
                fecha_creacion
            FROM reserva_evento
            WHERE reserva_id = :reserva_id
            ORDER BY fecha_creacion ASC, id ASC
        ';

        $statement = $this->pdo->prepare($sql);

        $statement->bindValue(
            ':reserva_id',
            $reservaId,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }
}
